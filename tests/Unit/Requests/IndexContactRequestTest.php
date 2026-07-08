<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_filter_values_are_accepted(): void
    {
        $category = Category::create(['content' => '商品の交換について']);

        $validator = Validator::make([
            'keyword' => 'customer@example.com',
            'gender' => 2,
            'category_id' => $category->id,
            'date' => '2026-07-08',
        ], (new IndexContactRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $validator = Validator::make([
            'keyword' => str_repeat('a', 256),
            'gender' => 9,
            'category_id' => 999999,
            'date' => 'invalid-date',
        ], (new IndexContactRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertSame(
            ['keyword', 'gender', 'category_id', 'date'],
            $validator->errors()->keys()
        );
    }
}
