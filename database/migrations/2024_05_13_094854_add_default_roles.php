<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = [
            'admin','doctor','receptionist','patient'
        ];
        $rolesValues = [];
        
        foreach($roles as $role) {
            $rolesValues[] = ['name' => $role];
        }
        
        DB::table('roles')->insert($rolesValues);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        DB::table('roles')->truncate();
    }
};
