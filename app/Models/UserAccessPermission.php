<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccessPermission extends Model
{
    protected $table = 'user_access_permissions';

    protected $fillable = [
        'user_id',
        'perm_type',
        'module',
        'perm_key',
    ];
}
