<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'integer', 'in:1,2,3'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'tel' => ['required', 'string', 'regex:/^[0-9]{10,11}$/'],
            'address' => ['required', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'detail' => ['required', 'string', 'max:120'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => '姓を入力してください。',
            'first_name.string' => '姓は文字列で入力してください。',
            'first_name.max' => '姓は255文字以内で入力してください。',
            'last_name.required' => '名を入力してください。',
            'last_name.string' => '名は文字列で入力してください。',
            'last_name.max' => '名は255文字以内で入力してください。',
            'gender.required' => '性別を入力してください。',
            'gender.integer' => '性別は整数で入力してください。',
            'gender.in' => '性別の値が不正です',
            'email.required' => 'メールアドレスを入力してください。',
            'email.string' => 'メールアドレスは文字列で入力してください。',
            'email.email' => 'メールアドレスはメール形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'tel.required' => '電話番号を入力してください。',
            'tel.string' => '電話番号は文字列で入力してください。',
            'tel.regex' => '電話番号はハイフンなしの10〜11桁で入力してください',
            'address.required' => '住所を入力してください。',
            'address.string' => '住所は文字列で入力してください。',
            'address.max' => '住所は255文字以内で入力してください。',
            'building.string' => '建物名は文字列で入力してください。',
            'building.max' => '建物名は255文字以内で入力してください。',
            'category_id.required' => 'お問い合わせの種類を入力してください。',
            'category_id.integer' => 'お問い合わせの種類は整数で入力してください。',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
            'detail.required' => 'お問い合わせ内容を入力してください。',
            'detail.string' => 'お問い合わせ内容は文字列で入力してください。',
            'detail.max' => 'お問い合わせ内容は120文字以内で入力してください。',
            'tag_ids.array' => 'タグは配列で入力してください。',
            'tag_ids.*.integer' => 'タグは整数で入力してください。',
            'tag_ids.*.exists' => '選択されたタグが存在しません',
        ];
    }
}
