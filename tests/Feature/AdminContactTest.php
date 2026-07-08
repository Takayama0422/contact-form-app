<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
        $response->assertSee('Admin');
    }

    public function test_admin_searches_contacts_and_paginates_seven_per_page(): void
    {
        $user = User::factory()->create();
        $targetCategory = Category::create(['content' => '商品のお届けについて']);
        $otherCategory = Category::create(['content' => 'その他']);

        foreach (range(1, 8) as $index) {
            $this->createContact($targetCategory, [
                'first_name' => "検索{$index}",
                'last_name' => '対象',
                'gender' => 2,
                'email' => "matched{$index}@example.com",
                'created_at' => '2026-07-08 10:00:00',
                'updated_at' => '2026-07-08 10:00:00',
            ]);
        }

        $this->createContact($otherCategory, [
            'first_name' => '対象外',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'other@example.com',
            'created_at' => '2026-07-07 10:00:00',
            'updated_at' => '2026-07-07 10:00:00',
        ]);

        $response = $this->actingAs($user)->get('/admin?keyword=検索&gender=2&category_id='.$targetCategory->id.'&date=2026-07-08');

        $response->assertOk();
        $response->assertViewHas('contacts', function ($contacts): bool {
            return $contacts->total() === 8 && $contacts->count() === 7;
        });
        $response->assertSee('検索1 対象');
        $response->assertDontSee('対象外 太郎');

        $secondPageResponse = $this->actingAs($user)->get('/admin?keyword=検索&gender=2&category_id='.$targetCategory->id.'&date=2026-07-08&page=2');
        $secondPageResponse->assertOk();
        $secondPageResponse->assertViewHas('contacts', function ($contacts): bool {
            return $contacts->total() === 8 && $contacts->count() === 1;
        });
    }

    public function test_contact_detail_is_displayed(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['content' => '商品トラブル']);
        $tag = Tag::create(['name' => '不具合報告']);
        $contact = $this->createContact($category, [
            'first_name' => '詳細',
            'last_name' => '太郎',
            'email' => 'detail@example.com',
        ]);
        $contact->tags()->sync([$tag->id]);

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertSee('詳細 太郎');
        $response->assertSee('detail@example.com');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    public function test_contact_is_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['content' => 'その他']);
        $contact = $this->createContact($category);

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_csv_is_downloaded_with_filters(): void
    {
        $user = User::factory()->create();
        $targetCategory = Category::create(['content' => '商品のお届けについて']);
        $otherCategory = Category::create(['content' => 'その他']);

        $this->createContact($targetCategory, [
            'first_name' => 'CSV',
            'last_name' => '対象',
            'gender' => 1,
            'email' => 'csv@example.com',
            'created_at' => '2026-07-08 10:00:00',
            'updated_at' => '2026-07-08 10:00:00',
        ]);
        $this->createContact($otherCategory, [
            'first_name' => 'CSV',
            'last_name' => '対象外',
            'gender' => 2,
            'email' => 'csv-other@example.com',
            'created_at' => '2026-07-08 10:00:00',
            'updated_at' => '2026-07-08 10:00:00',
        ]);

        $response = $this->actingAs($user)->get('/contacts/export?keyword=CSV&gender=1&category_id='.$targetCategory->id.'&date=2026-07-08');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload();

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('CSV 対象', $content);
        $this->assertStringNotContainsString('CSV 対象外', $content);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createContact(Category $category, array $overrides = []): Contact
    {
        return Contact::create(array_merge([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'contact'.uniqid().'@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'detail' => 'お問い合わせ内容です。',
        ], $overrides));
    }
}
