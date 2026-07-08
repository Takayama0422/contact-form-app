<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_filter_values_are_accepted(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);

        $validator = Validator::make([
            'keyword' => '山田',
            'gender' => 0,
            'category_id' => $category->id,
            'date' => '2026-07-08',
        ], (new ExportContactRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $validator = Validator::make([
            'keyword' => str_repeat('a', 256),
            'gender' => 4,
            'category_id' => 999999,
            'date' => 'invalid-date',
        ], (new ExportContactRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertSame(
            ['keyword', 'gender', 'category_id', 'date'],
            $validator->errors()->keys()
        );
    }
}
