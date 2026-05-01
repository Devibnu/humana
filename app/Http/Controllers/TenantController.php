<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tenants');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $selectedStatus = $request->filled('status') ? (string) $request->query('status') : null;
        $statuses = $this->statuses();

        $baseQuery = Tenant::query()
            ->withCount(['users', 'employees', 'departments'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('domain', 'like', '%'.$search.'%')
                        ->orWhere('address', 'like', '%'.$search.'%')
                        ->orWhere('contact', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($selectedStatus && array_key_exists($selectedStatus, $statuses), fn ($query) => $query->where('status', $selectedStatus));

        $tenants = (clone $baseQuery)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'inactive')->count(),
        ];

        return view('tenants.index', compact('tenants', 'search', 'selectedStatus', 'statuses', 'summary'));
    }

    public function create()
    {
        return view('tenants.create', [
            'tenant' => new Tenant(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request)
    {
        if (Tenant::query()->exists()) {
            return redirect()->route('tenants.index')->withErrors([
                'tenant' => 'Hanya satu tenant yang diperbolehkan.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['nullable', Rule::in(array_keys($this->statuses()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'contact' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'login_footer_text' => ['nullable', 'string', 'max:255'],
            'branding' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg,ico', 'max:2048'],
        ]);

        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['code'] = $this->generateUniqueCode($data['name']);
        $data['status'] = $data['status'] ?? 'active';
        $data['description'] = $data['description'] ?? null;
        $data['login_footer_text'] = isset($data['login_footer_text']) && trim((string) $data['login_footer_text']) !== ''
            ? trim((string) $data['login_footer_text'])
            : null;

        if ($request->hasFile('branding')) {
            $data['branding_path'] = $this->storeBrandingAsset($request->file('branding'), 'branding');
        }

        Tenant::create($data);

        return redirect()->route('tenants.index')->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function edit(Tenant $tenant)
    {
        return view('tenants.edit', [
            'tenant' => $tenant,
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->loadCount(['users', 'employees', 'departments']);

        return view('tenants.show', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')->ignore($tenant->id)],
            'domain' => ['required', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant->id)],
            'status' => ['nullable', Rule::in(array_keys($this->statuses()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'contact' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'login_footer_text' => ['nullable', 'string', 'max:255'],
            'branding' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg,ico', 'max:2048'],
        ]);

        if ($tenant->name !== $data['name']) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $tenant->id);
            $data['code'] = $this->generateUniqueCode($data['name'], $tenant->id);
        }

        $data['status'] = $data['status'] ?? $tenant->status ?? 'active';
        $data['description'] = $data['description'] ?? null;
        $data['login_footer_text'] = isset($data['login_footer_text']) && trim((string) $data['login_footer_text']) !== ''
            ? trim((string) $data['login_footer_text'])
            : null;

        if ($request->hasFile('branding')) {
            $this->deleteBrandingAsset($tenant->branding_path);
            $data['branding_path'] = $this->storeBrandingAsset($request->file('branding'), 'branding');
        }

        $tenant->update($data);

        return redirect()->route('tenants.index')->with('success', 'Tenant berhasil diperbarui.');
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->users()->exists()) {
            return redirect()->route('tenants.index')->withErrors([
                'tenant' => 'Tenant tidak dapat dihapus karena masih memiliki user terdaftar.',
            ]);
        }

        $this->deleteBrandingAsset($tenant->branding_path);

        $tenant->delete();

        return redirect()->route('tenants.index')->with('success', 'Tenant berhasil dihapus.');
    }

    public function destroyBranding(Tenant $tenant)
    {
        $this->deleteBrandingAsset($tenant->branding_path);

        $tenant->update([
            'branding_path' => null,
        ]);

        return redirect()
            ->route('tenants.edit', $tenant)
            ->with('success', 'Branding tenant berhasil dihapus.');
    }

    protected function statuses()
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
        ];
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreTenantId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'tenant';
        $counter = 1;

        while (Tenant::query()
            ->when($ignoreTenantId, function ($query) use ($ignoreTenantId) {
                $query->where('id', '!=', $ignoreTenantId);
            })
            ->where('slug', $slug)
            ->exists()) {
            $slug = ($baseSlug !== '' ? $baseSlug : 'tenant').'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function generateUniqueCode(string $name, ?int $ignoreTenantId = null): string
    {
        $baseCode = Str::upper(Str::substr(Str::slug($name, ''), 0, 10));
        $code = $baseCode !== '' ? $baseCode : 'TENANT';
        $counter = 1;

        while (Tenant::query()
            ->when($ignoreTenantId, function ($query) use ($ignoreTenantId) {
                $query->where('id', '!=', $ignoreTenantId);
            })
            ->where('code', $code)
            ->exists()) {
            $suffix = (string) $counter;
            $code = Str::substr($baseCode !== '' ? $baseCode : 'TENANT', 0, 10 - strlen($suffix)).$suffix;
            $counter++;
        }

        return $code;
    }

    protected function storeBrandingAsset($file, string $directory): string
    {
        $path = $file->store("tenant-branding/{$directory}", 'public');

        return 'storage/'.$path;
    }

    protected function deleteBrandingAsset(?string $assetPath): void
    {
        if (! $assetPath) {
            return;
        }

        $relativePath = Str::startsWith($assetPath, 'storage/')
            ? Str::after($assetPath, 'storage/')
            : $assetPath;

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }

}