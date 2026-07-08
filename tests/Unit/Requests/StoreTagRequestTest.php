<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_tag_name_is_accepted(): void
    {
        $validator = Validator::make([
            'name' => '新機能の要望',
        ], (new StoreTagRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_tag_name_is_rejected(): void
    {
        Tag::create(['name' => '質問']);

        $validator = Validator::make([
            'name' => '質問',
        ], (new StoreTagRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertSame(['name'], $validator->errors()->keys());

        $emptyValidator = Validator::make(['name' => ''], (new StoreTagRequest)->rules());
        $this->assertFalse($emptyValidator->passes());

        $longValidator = Validator::make([
            'name' => str_repeat('a', 51),
        ], (new StoreTagRequest)->rules());
        $this->assertFalse($longValidator->passes());
    }
}
