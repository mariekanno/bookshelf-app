<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReadingPlanRequest extends FormRequest
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
            'book_id' => 'required|integer|exists:books,id',
            'target_date' => 'required|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍は必須です。',
            'book_id.integer' => '書籍を正しく選択してください。',
            'book_id.exists' => '存在しない書籍です。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付で入力してください。',
        ];
    }
}
