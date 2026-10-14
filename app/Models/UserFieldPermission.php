<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFieldPermission extends Model
{
    protected $table = 'user_field_permissions';

    protected $fillable = [
        'user_id',
        'table_name',
        'field_name',
        'can_view',
    ];
}