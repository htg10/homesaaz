<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_field_permissions')) {
            Schema::create('user_field_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('table_name', 150);
                $table->string('field_name', 150);
                $table->boolean('can_view')->default(true);
                $table->timestamps();

                $table->index(['user_id', 'table_name'], 'ix_user_field_permissions_user_table');
                $table->unique(['user_id', 'table_name', 'field_name'], 'ux_user_table_field');
            });
        }
    }

    public function down(): void
    {
        // Deliberately do not drop an existing production permission table.
    }
};
