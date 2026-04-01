<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class JobRoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_family_id' => null,
            'name' => $this->faker->jobTitle,
            'description' => $this->faker->sentence(8),
        ];
    }
}
