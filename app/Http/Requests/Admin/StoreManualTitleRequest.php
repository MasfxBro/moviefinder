<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreManualTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'original_title' => ['nullable', 'string', 'max:255'],
            'media_type' => ['required', 'string', 'in:movie,tv'],
            'release_year' => ['nullable', 'integer', 'between:1880,2100'],
            'runtime_minutes' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'seasons_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'episodes_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'original_language' => ['nullable', 'string', 'max:10'],
            'overview' => ['nullable', 'string', 'max:5000'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'trailer_youtube_key' => ['nullable', 'string', 'max:30'],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
            'poster' => ['nullable', 'file', 'max:2048'],
        ];
    }
}
