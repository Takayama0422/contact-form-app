<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_api_index_contact_values_are_accepted(): void
    {
        $category = Category::create(['content' => '商品トラブル']);

        $validator = Validator::make([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-07-08',
            'per_page' => 50,
            'page' => 2,
        ], (new IndexContactRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_api_index_contact_values_are_rejected(): void
    {
        $validator = Validator::make([
            'keyword' => str_repeat('a', 256),
            'gender' => 0,
            'category_id' => 999999,
            'date' => 'invalid-date',
            'per_page' => 101,
            'page' => 0,
        ], (new IndexContactRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertSame(
            [
                'keyword',
                'gender',
                'category_id',
                'date',
                'per_page',
                'page',
            ],
            $validator->errors()->keys()
        );
    }
}
