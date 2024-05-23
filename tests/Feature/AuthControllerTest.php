<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jude Paul',
            'email' => 'jude@paul.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas(
            'users',
            [
                'name' => 'Jude Paul',
                'email' => 'jude@paul.com'
            ]
        );
        
        $userId = \Illuminate\Support\Facades\DB::table('users')
                ->where('name', 'Jude Paul')->first()->id;
        $adminRoleId = \Illuminate\Support\Facades\DB::table('roles')
                ->where('name', 'admin')->first()->id;
        
        $this->assertDatabaseHas(
            'user_roles',
            [
                'user_id' => $userId,
                'role_id' => $adminRoleId
            ]
        );
    }
    
    public function test_can_login()
    {   
        $user = User::create([
            'name' => 'Jude Paul',
            'email' => 'jude@paul.com',
            'password' => Hash::make('password'),
        ]);
        
        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Auth::check());
    }
    
    public function test_can_logout()
    {   
        $user = User::create([
            'name' => 'Jude Paul',
            'email' => 'jude@paul.com',
            'password' => Hash::make('password'),
        ]);
        
        $this->assertFalse(Auth::check());

        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Auth::check());
    }
}
