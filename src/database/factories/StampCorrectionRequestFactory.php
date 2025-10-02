<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class StampCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'attendance_id' => Attendance::factory(),
            'requested_clock_in' => $this->faker->dateTimeThisMonth(),
            'requested_clock_out' => null,
            'remarks' => $this->faker->realText(50),
            'status' => 'pending', 
        ];
    }
}
