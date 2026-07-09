<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_are_listed_as_json_with_search_and_pagination(): void
    {
        $targetCategory = Category::create(['content' => '商品のお届けについて']);
        $otherCategory = Category::create(['content' => 'その他']);

        foreach (range(1, 3) as $index) {
            $this->createContact($targetCategory, [
                'first_name' => "検索{$index}",
                'last_name' => '対象',
                'gender' => 2,
                'email' => "matched{$index}@example.com",
            ], '2026-07-08 10:00:00');
        }

        $this->createContact($otherCategory, [
            'first_name' => '対象外',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'other@example.com',
        ], '2026-07-07 10:00:00');

        $response = $this->getJson('/api/v1/contacts?keyword=検索&gender=2&category_id='.$targetCategory->id.'&date=2026-07-08&per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('data.0.category.id', $targetCategory->id);
        $response->assertJsonMissing(['email' => 'other@example.com']);

        $secondPageResponse = $this->getJson('/api/v1/contacts?keyword=検索&gender=2&category_id='.$targetCategory->id.'&date=2026-07-08&per_page=2&page=2');

        $secondPageResponse->assertOk();
        $secondPageResponse->assertJsonCount(1, 'data');
        $secondPageResponse->assertJsonPath('meta.total', 3);
    }

    public function test_contact_index_validation_error_returns_json(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=0&per_page=101&page=0');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['gender', 'per_page', 'page']);
        $this->assertSame('性別の値が不正です', $response->json('errors.gender.0'));
    }

    public function test_contact_detail_is_returned_as_json(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $tag = Tag::create(['name' => '不具合報告']);
        $contact = $this->createContact($category, [
            'first_name' => '詳細',
            'last_name' => '太郎',
            'email' => 'detail@example.com',
        ]);
        $contact->tags()->sync([$tag->id]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.email', 'detail@example.com');
        $response->assertJsonPath('data.category.content', $category->content);
        $response->assertJsonPath('data.tags.0.name', $tag->name);
    }

    public function test_contact_detail_not_found_returns_json(): void
    {
        $response = $this->getJson('/api/v1/contacts/999999');

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
    }

    public function test_contact_is_created_and_returns_json(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $tag = Tag::create(['name' => '質問']);

        $response = $this->postJson('/api/v1/contacts', $this->validPayload($category, [$tag->id]));

        $response->assertCreated();
        $response->assertJsonPath('data.first_name', '山田');
        $response->assertJsonPath('data.category.id', $category->id);
        $response->assertJsonPath('data.tags.0.id', $tag->id);
        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'email' => 'taro@example.com',
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contact_create_validation_error_returns_json(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '',
            'last_name' => '',
            'gender' => 4,
            'email' => 'invalid-email',
            'tel' => '090-1234-5678',
            'address' => '',
            'category_id' => 999999,
            'detail' => str_repeat('a', 121),
            'tag_ids' => [999999],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
            'tag_ids.0',
        ]);
        $errors = $response->json('errors');
        $this->assertSame('性別の値が不正です', $errors['gender'][0]);
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $errors['tel'][0]);
        $this->assertSame('選択されたカテゴリーが存在しません', $errors['category_id'][0]);
        $this->assertSame('選択されたタグが存在しません', $errors['tag_ids.0'][0]);
    }

    public function test_contact_is_updated_and_returns_json(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $updatedCategory = Category::create(['content' => 'その他']);
        $tag = Tag::create(['name' => '質問']);
        $updatedTag = Tag::create(['name' => '対応希望']);
        $contact = $this->createContact($category);
        $contact->tags()->sync([$tag->id]);

        $payload = $this->validPayload($updatedCategory, [$updatedTag->id], [
            'first_name' => '更新',
            'email' => 'updated@example.com',
        ]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.first_name', '更新');
        $response->assertJsonPath('data.email', 'updated@example.com');
        $response->assertJsonPath('data.category.id', $updatedCategory->id);
        $response->assertJsonPath('data.tags.0.id', $updatedTag->id);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '更新',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_contact_update_validation_error_returns_json(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $contact = $this->createContact($category);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'first_name' => '',
            'last_name' => '',
            'gender' => 4,
            'email' => 'invalid-email',
            'tel' => '090-1234-5678',
            'address' => '',
            'category_id' => 999999,
            'detail' => str_repeat('a', 121),
            'tag_ids' => [999999],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
            'tag_ids.0',
        ]);
        $errors = $response->json('errors');
        $this->assertSame('性別の値が不正です', $errors['gender'][0]);
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $errors['tel'][0]);
        $this->assertSame('選択されたカテゴリーが存在しません', $errors['category_id'][0]);
        $this->assertSame('選択されたタグが存在しません', $errors['tag_ids.0'][0]);
    }

    public function test_contact_update_not_found_returns_json(): void
    {
        $category = Category::create(['content' => '商品トラブル']);

        $response = $this->putJson('/api/v1/contacts/999999', $this->validPayload($category));

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
    }

    public function test_contact_is_deleted(): void
    {
        $category = Category::create(['content' => 'その他']);
        $contact = $this->createContact($category);

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_contact_delete_not_found_returns_json(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/999999');

        $response->assertNotFound();
        $response->assertJsonStructure(['message']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createContact(Category $category, array $overrides = [], ?string $createdAt = null): Contact
    {
        $contact = Contact::create(array_merge([
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

        if ($createdAt !== null) {
            $contact->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
        }

        return $contact;
    }

    /**
     * @param  array<int, int>  $tagIds
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Category $category, array $tagIds = [], array $overrides = []): array
    {
        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tagIds,
        ], $overrides);
    }
}
