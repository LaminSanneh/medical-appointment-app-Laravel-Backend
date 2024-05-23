<?php

namespace Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use function fake;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\=Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = fake();
        $startTime = CarbonImmutable::now()->startOfHour();
        $endTime = $startTime->addMinutes(60);

        return [
            'confirmed' => $faker->boolean(50),
            'appointment_date_time' => $startTime,
            'end_time' => $endTime,
        ];
    }
}
