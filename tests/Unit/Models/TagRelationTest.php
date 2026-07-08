<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_contacts(): void
    {
        $category = Category::create(['content' => 'その他']);
        $tag = Tag::create(['name' => 'ご意見']);
        $contacts = collect(range(1, 3))->map(fn (int $index): Contact => Contact::create([
            'category_id' => $category->id,
            'first_name' => "姓{$index}",
            'last_name' => "名{$index}",
            'gender' => 1,
            'email' => "tagged{$index}@example.com",
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'detail' => "お問い合わせ{$index}",
        ]));

        $tag->contacts()->sync($contacts->pluck('id')->all());

        $this->assertInstanceOf(BelongsToMany::class, $tag->contacts());
        $this->assertCount(3, $tag->contacts);
        $this->assertEqualsCanonicalizing(
            $contacts->pluck('id')->all(),
            $tag->contacts->pluck('id')->all()
        );
    }
}
