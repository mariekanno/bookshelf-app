<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'author' => fake()->name(),
            'isbn' => fake()->unique->numerify('#############'),
            'published_date' => fake()->date(),
            'description' => fake()->optional()->paragraph(),
            'image_url' => fake()->optional()->imageUrl(200, 300, 'books'),
            'created_by' => User::factory(),
        ];
    }
}
