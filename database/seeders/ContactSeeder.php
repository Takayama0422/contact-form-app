<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Faker\Factory;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create('ja_JP');
        $categoryIds = Category::query()->pluck('id');
        $tagIds = Tag::query()->pluck('id');

        for ($contactCount = 0; $contactCount < 20; $contactCount++) {
            $contact = Contact::forceCreate([
                'category_id' => $categoryIds->random(),
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->unique()->safeEmail(),
                'tel' => $faker->numerify('090########'),
                'address' => $faker->address(),
                'building' => $faker->optional()->secondaryAddress(),
                'detail' => $faker->realText(80),
            ]);

            $selectedTagIds = $tagIds->random($faker->numberBetween(1, 3));
            $attachedTags = $selectedTagIds->mapWithKeys(fn (int $tagId): array => [
                $tagId => [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $contact->tags()->attach($attachedTags->all());
        }
    }
}
