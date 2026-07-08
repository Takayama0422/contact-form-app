<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_page_is_displayed(): void
    {
        $category = Category::create(['content' => '商品のお届けについて']);
        $tag = Tag::create(['name' => '質問']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
        $response->assertViewHasAll(['categories', 'tags']);
        $response->assertSee('Contact');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    public function test_thanks_page_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();
        $response->assertViewIs('contact.thanks');
        $response->assertSee('お問い合わせありがとうございました');
    }

    public function test_confirm_page_is_displayed_when_validation_passes(): void
    {
        $category = Category::create(['content' => '商品トラブル']);
        $tag = Tag::create(['name' => '不具合報告']);

        $response = $this->post('/contacts/confirm', $this->validContactData($category, [$tag]));

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田 太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee($category->content);
        $response->assertSee($tag->name);
    }

    public function test_confirm_page_redirects_with_errors_when_validation_fails(): void
    {
        $response = $this->from('/')->post('/contacts/confirm', []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    public function test_contact_is_stored_and_redirects_to_thanks(): void
    {
        $category = Category::create(['content' => 'ショップへのお問い合わせ']);
        $tag = Tag::create(['name' => '要望']);

        $response = $this->post('/contacts', $this->validContactData($category, [$tag]));

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $contact = Contact::firstOrFail();
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contact_store_redirects_with_errors_when_validation_fails(): void
    {
        $response = $this->from('/')->post('/contacts', []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    /**
     * @param  array<int, Tag>  $tags
     * @return array<string, mixed>
     */
    private function validContactData(Category $category, array $tags = []): array
    {
        return [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => collect($tags)->pluck('id')->all(),
        ];
    }
}
