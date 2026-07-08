<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_tag_name_is_accepted(): void
    {
        $tag = Tag::create(['name' => '質問']);

        $validator = Validator::make([
            'name' => '質問',
        ], $this->rulesFor($tag));

        $this->assertTrue($validator->passes());
    }

    public function test_name_used_by_another_tag_is_rejected(): void
    {
        $tag = Tag::create(['name' => '質問']);
        Tag::create(['name' => '要望']);

        $validator = Validator::make([
            'name' => '要望',
        ], $this->rulesFor($tag));

        $this->assertFalse($validator->passes());
        $this->assertSame(['name'], $validator->errors()->keys());
    }

    public function test_required_and_max_rules_are_applied(): void
    {
        $tag = Tag::create(['name' => '質問']);

        $emptyValidator = Validator::make(['name' => ''], $this->rulesFor($tag));
        $this->assertFalse($emptyValidator->passes());

        $longValidator = Validator::make([
            'name' => str_repeat('a', 51),
        ], $this->rulesFor($tag));
        $this->assertFalse($longValidator->passes());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(Tag $tag): array
    {
        $request = UpdateTagRequest::create("/admin/tags/{$tag->id}", 'PUT');
        $request->setRouteResolver(fn (): object => new class($tag)
        {
            public function __construct(private readonly Tag $tag) {}

            public function parameter(string $name, mixed $default = null): mixed
            {
                return $name === 'tag' ? $this->tag : $default;
            }
        });

        return $request->rules();
    }
}
