<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->count() <= 1) {
            $doctorIds = User::factory()->count(10)->create();
            $patientIds = User::factory()->count(10)->create([
                'doctor_id_number' => '',
                'specialization' => '',
            ]);
        }

        $this->seedDifferentApplicationUserTypesWithRoles();
    }

    private function seedDifferentApplicationUserTypesWithRoles() {
        $userDatas = [
            [
                'email' => 'lamin.evra@gmail.com',
                'password' => 'password',
                'roles' => ['admin', 'patient'],
                'specialization' => ''
            ],
            [
                'email' => 'lamin.evra2@gmail.com',
                'password' => 'password2',
                'roles' => ['doctor'],
                'doctor_id_number' => fake()->randomNumber(9),
            ]
        ];

        foreach ($userDatas as $userData) {
            if (DB::table('users')->where('email', $userData['email'])->count() === 0) {
                $user = User::factory()->create([
                    'name' => $userData['email'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'doctor_id_number' => '',
                    'specialization' => '',
                ]);

                $rolesIds = Role::whereIn('name', $userData['roles'])->pluck('id')->toArray();
                $user->roles()->sync($rolesIds);
            }
        }
    }
}
