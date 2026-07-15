<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGenreRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:genres,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'ジャンルは必須です。',
            'name.string' => 'ジャンルは文字列で入力してください。',
            'name.max' => 'ジャンルは255文字以内で入力してください。',
            'name.unique' => 'このジャンルは既に登録されています。',
        ];
    }
}
