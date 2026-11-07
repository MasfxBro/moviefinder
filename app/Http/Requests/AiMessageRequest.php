<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:600'],
            'conversation_id' => ['nullable', 'uuid'],
            'mode' => ['required', Rule::in(['global', 'title'])],
            'title_id' => ['required_if:mode,title', 'prohibited_unless:mode,title', 'integer', 'exists:titles,id'],
        ];
    }
}
