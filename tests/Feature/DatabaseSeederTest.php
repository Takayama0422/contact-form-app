<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_required_initial_data(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password', $user->password));

        $this->assertSame([
            '商品のお届けについて',
            '商品の交換について',
            '商品トラブル',
            'ショップへのお問い合わせ',
            'その他',
        ], Category::query()->orderBy('id')->pluck('content')->all());

        $this->assertSame([
            '質問',
            '要望',
            '不具合報告',
            'ご意見',
            'その他',
        ], Tag::query()->orderBy('id')->pluck('name')->all());

        $this->assertSame(20, Contact::query()->count());

        $tagCounts = Contact::query()
            ->withCount('tags')
            ->pluck('tags_count');

        $this->assertTrue($tagCounts->every(fn (int $count): bool => $count >= 1 && $count <= 3));
    }
}
