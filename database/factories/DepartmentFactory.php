<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Department',
            'subscription_type' => 'free',
            'slug' => $this->faker->unique()->slug(2),
        ];
    }
}
