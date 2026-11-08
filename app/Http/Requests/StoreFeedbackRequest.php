<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Feedback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->hasVerifiedEmail()) {
            return false;
        }

        $dailyCount = Feedback::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyCount >= 3) {
            abort(429, 'You have reached the daily feedback limit of 3 submissions.');
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') === 'content_issue') {
            $this->merge(['type' => 'content']);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['bug', 'suggestion', 'content', 'praise', 'other'])],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'page_path' => [
                'nullable',
                'string',
                'max:200',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                        $parsed = parse_url($value);
                        if (($parsed['host'] ?? null) !== request()->getHost()) {
                            $fail('The page path must be on the same origin.');
                        }
                    } elseif (! str_starts_with($value, '/') || str_starts_with($value, '//')) {
                        $fail('The page path must be a valid relative path.');
                    }
                },
            ],
            'honeypot' => ['prohibited'],
            'website' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('honeypot') || $this->filled('website')) {
                $validator->errors()->add('honeypot', 'Spam detected.');
            }
        });
    }
}
