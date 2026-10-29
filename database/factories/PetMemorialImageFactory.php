<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PetMemorial;
use App\Models\PetMemorialImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetMemorialImage>
 */
class PetMemorialImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_memorial_id' => PetMemorial::factory(),
            'path' => 'memorials/'.fake()->uuid().'.jpg',
        ];
    }
}
