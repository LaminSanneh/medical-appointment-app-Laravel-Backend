<?php

namespace Database\Seeders;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentsListSeeder extends Seeder
{
    public function run(): void
    {
        $numberOfAppointments = 100;
        $now = Carbon::now();
        $startDate = $now->startOfMonth();
        $appointmentDates = new Collection();

        $appointmentDates->push(Carbon::createFromTimeString($startDate->toString()));
        foreach (range(1, $numberOfAppointments) as $num) {
            $startDate = $startDate->addMinutes(60);
            $appointmentDates->push(\Carbon\CarbonImmutable::createFromTimeString($startDate->toString()));
        }

        DB::table('appointments')->truncate();
        if (DB::table('appointments')->count() === 0) {
            $users = DB::table('users')->where('doctor_id_number', '=', '')->get();
            $doctors = DB::table('users')->where('doctor_id_number', '!=', '')->get();

            foreach (range(1, $numberOfAppointments) AS $num) {
                $randomUser = $users->random();
                $randomDoctor = $doctors->random();
                $date = $appointmentDates->random();
                $endDate = $date->addMinutes(60);
                Appointment::factory()->create([
                    'doctor_id' => $randomDoctor->id,
                    'patient_id' => $randomUser->id,
                    'appointment_date_time' => $date,
                    'end_time' => $endDate,
                ]);
            }
        }
    }
}
