<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_tag_routes_to_login(): void
    {
        $tag = Tag::create(['name' => '質問']);

        $this->get("/admin/tags/{$tag->id}/edit")->assertRedirect('/login');
        $this->post('/admin/tags', ['name' => '新規タグ'])->assertRedirect('/login');
        $this->put("/admin/tags/{$tag->id}", ['name' => '更新タグ'])->assertRedirect('/login');
        $this->delete("/admin/tags/{$tag->id}")->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', [
            'name' => '新機能の要望',
        ]);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新機能の要望']);
    }

    public function test_authenticated_user_can_view_edit_tag_page(): void
    {
        $user = User::factory()->create();
        $tag = Tag::create(['name' => '質問']);

        $response = $this->actingAs($user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertOk();
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('タグ編集');
        $response->assertSee($tag->name);
    }

    public function test_authenticated_user_can_update_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::create(['name' => '質問']);

        $response = $this->actingAs($user)->put("/admin/tags/{$tag->id}", [
            'name' => '更新後タグ',
        ]);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新後タグ',
        ]);
    }

    public function test_authenticated_user_can_delete_tag_and_related_pivot_records(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['content' => 'その他']);
        $tag = Tag::create(['name' => '削除対象']);
        $contact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'delete-tag@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => null,
            'detail' => 'お問い合わせ内容です。',
        ]);
        $contact->tags()->sync([$tag->id]);

        $response = $this->actingAs($user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('contact_tag', ['tag_id' => $tag->id]);
    }
}
