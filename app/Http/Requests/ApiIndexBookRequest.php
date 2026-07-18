<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApiIndexBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'genres' => 'nullable|array',
            'genres.*' => 'integer|exsists:genres,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genres.array' => 'ジャンルは正しく選択してください。',
            'genres.*.integer' => 'ジャンルは正しく選択してください。',
            'genres.*.exists' => '存在しないジャンルが選択されています。',
            'page.integer' => 'ページは整数で入力してください。',
            'page.min' => 'ページ番号は1以上で入力してください。',
            'per_page.integer' => 'ページは整数で入力してください。',
            'per_page.min' => '1ページあたりの件数は1以上で入力してください。',
            'per_page.max' => '1ページあたりの件数は100以下で入力してください。',
        ];
    }
}
