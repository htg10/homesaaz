<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'Online_Tbl_Users';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $fillable = [
        'FirstName',
        'LastName',
        'EmailID',
        'Pwd',
        'IsActive',
        'LastLoginDate',
        'profileImage',
        'user_role',
    ];

    protected $hidden = ['Pwd',];
    public function getAuthPassword()
    {
        return $this->Pwd;
    }

    public function fieldPermissions()
    {
        return $this->hasMany(
            \App\Models\UserFieldPermission::class,
            'user_id'
        );
    }

    public static function profileImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        if (!str_contains($path, '/')) {
            $path = 'uploads/users/' . $path;
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        $fallback = (string) config('user_images.fallback_base_url', '');
        if ($fallback !== '') {
            return $fallback . '/' . $path;
        }

        return null;
    }
    public static function profileImageUrlOrDefault(?string $path): string
    {
        return self::profileImageUrl($path) ?? asset('assets/images/default-avatar.svg');
    }
}