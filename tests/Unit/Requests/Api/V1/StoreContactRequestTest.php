<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_api_store_contact_values_are_accepted(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $tag = Tag::create(['name' => '質問']);

        $validator = Validator::make([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ], (new StoreContactRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_api_store_contact_values_are_rejected(): void
    {
        $validator = Validator::make([
            'first_name' => '',
            'last_name' => '',
            'gender' => 4,
            'email' => 'invalid-email',
            'tel' => '090-1234-5678',
            'address' => '',
            'building' => str_repeat('a', 256),
            'category_id' => 999999,
            'detail' => str_repeat('a', 121),
            'tag_ids' => [999999],
        ], (new StoreContactRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertSame(
            [
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'building',
                'category_id',
                'detail',
                'tag_ids.0',
            ],
            $validator->errors()->keys()
        );
    }
}
