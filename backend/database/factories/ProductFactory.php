<?php

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->unique()->words(2, true), // Ensure unique product names
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'stock' => fake()->numberBetween(1, 100),
            'image' => 'default.jpg',
        ];
    }
}
