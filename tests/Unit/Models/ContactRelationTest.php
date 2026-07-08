<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_category_and_tags(): void
    {
        $category = Category::create(['content' => 'ショップへのお問い合わせ']);
        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'detail' => 'お問い合わせ内容です。',
        ]);
        $tags = collect([
            Tag::create(['name' => '質問']),
            Tag::create(['name' => '要望']),
        ]);

        $contact->tags()->sync($tags->pluck('id')->all());
        $contact->load(['category', 'tags']);

        $this->assertInstanceOf(BelongsTo::class, $contact->category());
        $this->assertTrue($contact->category->is($category));
        $this->assertInstanceOf(BelongsToMany::class, $contact->tags());
        $this->assertEqualsCanonicalizing(
            $tags->pluck('id')->all(),
            $contact->tags->pluck('id')->all()
        );
    }
}
