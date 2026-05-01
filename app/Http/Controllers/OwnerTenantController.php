<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OwnerTenantController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeOwner($request);

        $tenants = Tenant::query()
            ->withCount(['users', 'employees', 'departments'])
            ->latest()
            ->get();

        return view('owner.tenants.index', [
            'tenants' => $tenants,
            'statuses' => $this->statuses(),
            'subscriptionPlans' => $this->subscriptionPlans(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'domain' => ['required', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'subscription_plan' => ['required', Rule::in(array_keys($this->subscriptionPlans()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['code'] = $this->generateUniqueCode($data['name']);

        Tenant::create($data);

        return redirect()->route('owner.tenants.index')->with('success', 'Tenant berhasil ditambahkan.');
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeOwner($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')->ignore($tenant->id)],
            'domain' => ['required', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant->id)],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'subscription_plan' => ['required', Rule::in(array_keys($this->subscriptionPlans()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($tenant->name !== $data['name']) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $tenant->id);
            $data['code'] = $this->generateUniqueCode($data['name'], $tenant->id);
        }

        $tenant->update($data);

        return redirect()->route('owner.tenants.index')->with('success', 'Tenant berhasil diperbarui.');
    }

    public function destroy(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeOwner($request);

        if ($tenant->users()->exists() || $tenant->employees()->exists()) {
            return redirect()->route('owner.tenants.index')->withErrors([
                'tenant' => 'Tenant tidak dapat dihapus karena masih memiliki user atau karyawan terdaftar.',
            ]);
        }

        $tenant->delete();

        return redirect()->route('owner.tenants.index')->with('success', 'Tenant berhasil dihapus.');
    }

    protected function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->isOwner(), 403);
    }

    protected function statuses(): array
    {
        return [
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ];
    }

    protected function subscriptionPlans(): array
    {
        return [
            'basic' => 'Basic',
            'pro' => 'Pro',
            'enterprise' => 'Enterprise',
        ];
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreTenantId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'tenant';
        $counter = 1;

        while (Tenant::query()
            ->when($ignoreTenantId, fn ($query) => $query->where('id', '!=', $ignoreTenantId))
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
            ->when($ignoreTenantId, fn ($query) => $query->where('id', '!=', $ignoreTenantId))
            ->where('code', $code)
            ->exists()) {
            $suffix = (string) $counter;
            $code = Str::substr($baseCode !== '' ? $baseCode : 'TENANT', 0, 10 - strlen($suffix)).$suffix;
            $counter++;
        }

        return $code;
    }
}