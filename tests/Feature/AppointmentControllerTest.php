<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_create_appointment()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($user, ['*']);
        
        $appointmentDateTime = \Carbon\CarbonImmutable::now()->addDays(1);
        $appointmentEndDateTime =  $appointmentDateTime->addHours(1);
        $response = $this->postJson('/api/appointments', [
            'appointment_date_time' => $appointmentDateTime,
            'doctor_id' => $doctor->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('appointments', [
            'appointment_date_time' => $appointmentDateTime,
            'end_time' => $appointmentEndDateTime,
        ]);
    }

    public function test_patient_cannot_create_appointment_in_past()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($user, ['*']);
        
        $now = \Carbon\CarbonImmutable::now();
        $appointmentDateTime = $now->subDays(1);
        $response = $this->postJson('/api/appointments', [
            'appointment_date_time' => Carbon::now()->subDays(1),
            'doctor_id' => $doctor->id,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('appointments', [
            'appointment_date_time' => $appointmentDateTime,
        ]);
    }
    
    public function test_doctor_cannot_create_appointment_fails()
    {
        $user = $this->createDoctor([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($user, ['*']);
        
        $response = $this->postJson('/api/appointments', [
            'appointment_date_time' => '2024-05-20 10:00:00',
            'doctor_id' => $doctor->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('appointments', [
            'appointment_date_time' => '2024-05-20 10:00:00',
            'end_time' => '2024-05-20 11:00:00',
        ]);
    }

    public function test_patient_cannot_confirm_appointment()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($user, ['*']);
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'confirmed' => false,
        ]);

        $response = $this->putJson("/api/appointments/confirmAppointment/{$appointment->id}");

        $response->assertForbidden();
        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
            'confirmed' => true
        ]);
    }

    public function test_patient_cannot_unconfirm_appointment()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($user, ['*']);
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'confirmed' => true,
        ]);

        $response = $this->putJson("/api/appointments/unConfirmAppointment/{$appointment->id}");

        $response->assertForbidden();
        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
            'confirmed' => false
        ]);
    }

    public function test_doctor_can_confirm_appointment()
    {
        $user = $this->createDoctor([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($doctor, ['*']);
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'confirmed' =>false,
        ]);
        
        $response = $this->putJson("/api/appointments/confirmAppointment/{$appointment->id}");

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'confirmed' => true
        ]);
    }

    public function test_doctor_can_unconfirm_appointment()
    {
        $user = $this->createDoctor([]);
        $doctor = $this->createDoctor([]);
        
        Sanctum::actingAs($doctor, ['*']);
        
        $appointment = Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'confirmed' => true,
        ]);
        
        $response = $this->putJson("/api/appointments/unConfirmAppointment/{$appointment->id}");

        $response->assertOk();
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'confirmed' => false
        ]);
    }

    public function test_can_get_all_appointments_for_patient()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        $doctor2 = $this->createDoctor([]);
        $doctor3 = $this->createDoctor([]);
        Sanctum::actingAs($user, ['*']);
        Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id
        ]);

        Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor2->id
        ]);
        
        $response = $this->get('/api/appointments');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id
        ]);
        $response->assertJsonFragment([
            'patient_id' => $user->id,
            'doctor_id' => $doctor2->id
        ]);
        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_can_get_all_appointments_for_doctor_and_user()
    {
        $user = $this->createPatient();
        $user2 = $this->createPatient();
        Sanctum::actingAs($user, ['*']);
        $doctor = $this->createDoctor();
        $doctor2 = $this->createDoctor();
        
        Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'appointment_date_time' => Carbon::now()->addHours(1),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor2->id,
            'appointment_date_time' => Carbon::now()->addHours(2),
            'end_time' => Carbon::now()->addHours(3),
        ]);

        Appointment::factory()->create([
            'patient_id' => $user2->id,
            'doctor_id' => $doctor->id,
            'appointment_date_time' => Carbon::now()->addHours(3),
            'end_time' => Carbon::now()->addHours(4),
        ]);

        Appointment::factory()->create([
            'patient_id' => $user2->id,
            'doctor_id' => $doctor2->id,
            'appointment_date_time' => Carbon::now()->addHours(4),
            'end_time' => Carbon::now()->addHours(5),
        ]);
        
        $response = $this->getJson('/api/appointments/getForDoctorAndCurrentUser/' . $doctor->id);
        $response->assertStatus(200);
        $response->assertJsonCount(3);
        $response->assertJsonFragment([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id
        ]);

        $response->assertJsonFragment([
            'patient_id' => $user2->id,
            'doctor_id' => $doctor->id
        ]);

        $this->assertDatabaseCount('appointments', 4);
    }

    public function test_cannot_create_appointment_user_not_logged_in()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        $response = $this->postJson('/api/appointments', [
            'appointment_date_time' => '2024-05-20 10:00:00',
            'doctor_id' => $doctor->id,
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseEmpty('appointments');
        $this->assertDatabaseMissing('appointments', [
            'appointment_date_time' => '2024-05-20 10:00:00',
            'end_time' => '2024-05-20 11:00:00',
        ]);
    }

    public function test_cannot_get_patient_appointments_user_not_logged_in()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'appointment_date_time' => Carbon::now()->addHours(2),
            'end_time' => Carbon::now()->addHours(3),
        ]);
        
        $response = $this->getJson('/api/appointments');

        $response->assertStatus(401);
    }

    public function test_cannot_get_patient_and_doctor_appointments_user_not_logged_in()
    {
        $user = $this->createPatient([]);
        $doctor = $this->createDoctor([]);
        
        Appointment::factory()->create([
            'patient_id' => $user->id,
            'doctor_id' => $doctor->id,
            'appointment_date_time' => Carbon::now()->addHours(2),
            'end_time' => Carbon::now()->addHours(3),
        ]);

        $response = $this->getJson('/api/appointments/getForDoctorAndCurrentUser/' . $doctor->id);

        $response->assertStatus(401);
    }
}
