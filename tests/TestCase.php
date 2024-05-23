<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createPatient($attributes = []) {
        $user = User::factory()->create(
            array_merge(
                [
                    'doctor_id_number' => '',
                    'specialization' => ''
                ],
                $attributes
            )
        );

        $rolesIds = Role::whereIn('name', [
//            'admin',
            'patient'
        ])->pluck('id')->toArray();
        
        $user->roles()->sync($rolesIds);
        
        return $user;
    }

    protected function createDoctor($attributes = []) {
        $user = User::factory()->create($attributes);
        
        $rolesIds = Role::whereIn('name', [
            'doctor'
        ])->pluck('id')->toArray();
        
        $user->roles()->sync($rolesIds);
        
        return $user;
    }
}
