<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:1,2,3'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'gender.integer' => '性別は整数で入力してください。',
            'gender.in' => '性別の値が不正です',
            'category_id.integer' => 'カテゴリは整数で入力してください。',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
            'date.date' => '日付は有効な日付を指定してください。',
            'per_page.integer' => '表示件数は整数で入力してください。',
            'per_page.min' => '表示件数は1以上で入力してください。',
            'per_page.max' => '表示件数は100以下で入力してください。',
            'page.integer' => 'ページ番号は整数で入力してください。',
            'page.min' => 'ページ番号は1以上で入力してください。',
        ];
    }
}
