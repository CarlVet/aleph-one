<?php

namespace Database\Factories;

use App\Models\Documents;
use App\Models\Projects;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentsFactory extends Factory
{
    protected $model = Documents::class;

    public function definition(): array
    {
        $documentTypes = [
            'Project Proposal',
            'Ethics Approval',
            'Progress Report',
            'Final Report',
            'Publication',
            'Presentation',
        ];

        $type = $this->faker->randomElement($documentTypes);

        return [
            'projects_id' => Projects::query()->inRandomOrder()->value('id') ?? Projects::factory(),
            'title' => $this->faker->sentence(3),
            'type' => $type,
            'file_path' => 'documents/'.$this->faker->uuid.'.pdf',
            'file_name' => $this->faker->word.'.pdf',
            'mime_type' => 'application/pdf',
            'description' => $this->faker->paragraph,
            'document_date' => fake()->date(),
            'parent_id' => null,
        ];
    }

    public function amendment(?Documents $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            if (! $parent) {
                $parent = Documents::query()->where('type', '!=', 'Amendment')->inRandomOrder()->first();
            }

            return [
                'type' => 'Amendment',
                'title' => 'Amendment to '.($parent ? $parent->title : 'Document'),
                'parent_id' => $parent ? $parent->id : null,
                'projects_id' => $parent ? $parent->projects_id : Projects::query()->inRandomOrder()->value('id'),
            ];
        });
    }
}
