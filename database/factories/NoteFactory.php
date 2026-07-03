<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'folder_id' => null,
            'title' => fake()->sentence(3),
            'blocks' => [
                [
                    'id' => fake()->uuid(),
                    'type' => 'paragraph',
                    'text' => fake()->paragraph(),
                ],
            ],
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inFolder(Folder $folder): static
    {
        return $this->state(fn () => [
            'user_id' => $folder->user_id,
            'folder_id' => $folder->id,
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn () => [
            'title' => 'Untitled',
            'blocks' => [
                [
                    'id' => fake()->uuid(),
                    'type' => 'paragraph',
                    'text' => '',
                ],
            ],
        ]);
    }
}
