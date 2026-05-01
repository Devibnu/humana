<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected static array $systemKeyIdCache = [];

    protected $fillable = [
        'name',
        'description',
    ];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getSystemKeyAttribute(): string
    {
        return self::toSystemKey($this->name);
    }

    public static function toSystemKey(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();
    }

    public static function idForSystemKey(string $systemKey): ?int
    {
        if (array_key_exists($systemKey, self::$systemKeyIdCache)) {
            return self::$systemKeyIdCache[$systemKey];
        }

        $role = self::query()->get()->first(fn (self $role) => $role->system_key === $systemKey);

        self::$systemKeyIdCache[$systemKey] = $role?->id;

        return self::$systemKeyIdCache[$systemKey];
    }
}