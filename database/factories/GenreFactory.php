<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Genre>
 */
class GenreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->umique()->randomElement([
                '小説',
                'ビジネス',
                '技術書',
                '自己啓発',
                'エッセイ',
                '歴史',
                '科学',
                '芸術',
                '料理',
                '旅行',
            ]),
        ];
    }
}
