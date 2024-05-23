<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase {

    use RefreshDatabase;

    public function test_get_user_profile_for_patient()
    {
        $user = $this->createPatient();
        $doctor = $this->createDoctor();
        
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/getUser');

        $response->assertStatus(200);
        
        $response->assertJsonFragment([
            'id' => $user->id,
            'name' => $user->name,
            'photoUrl' => null,
            'isDoctor' => false,
            'roles' => ['patient']
        ]);
    }
    
    public function test_get_user_profile_for_doctor()
    {
        $user = $this->createPatient();
        $doctor = $this->createDoctor();
        
        Sanctum::actingAs($doctor, ['*']);

        $response = $this->getJson('/api/getUser');

        $response->assertStatus(200);
        
        $response->assertJsonFragment([
            'id' => $doctor->id,
            'name' => $doctor->name,
            'photoUrl' => null,
            'isDoctor' => true,
            'roles' => ['doctor']
        ]);
    }
}
