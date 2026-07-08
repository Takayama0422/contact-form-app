<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_contacts(): void
    {
        $category = Category::create(['content' => 'その他']);
        $contacts = collect(range(1, 3))->map(fn (int $index): Contact => Contact::create([
            'category_id' => $category->id,
            'first_name' => "姓{$index}",
            'last_name' => "名{$index}",
            'gender' => 1,
            'email' => "customer{$index}@example.com",
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'detail' => "お問い合わせ{$index}",
        ]));

        $this->assertInstanceOf(HasMany::class, $category->contacts());
        $this->assertCount(3, $category->contacts);
        $this->assertEqualsCanonicalizing(
            $contacts->pluck('id')->all(),
            $category->contacts->pluck('id')->all()
        );
    }
}
