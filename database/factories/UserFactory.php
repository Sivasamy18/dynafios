<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $first_name = $this->faker->firstName;
        $last_name = $this->faker->lastName;
        return [
            'initials' => $first_name[0] . $last_name[0],
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'password_text' => 'password',
            'remember_token' => Str::random(60),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => '(000) 000-0000',
            'unsuccessful_login_attempts' => 0,
            'password_expiration_date' => now()->addYears(30),
            'title' => $this->faker->title,
            'group_id' => $this->faker->numberBetween(1,8),
            'locked' => false,
            'deleted_at' => null,
            'seen_at' => Carbon::now(),
        ];
    }
}
