<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\LinkReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportClipLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $dailyCount = LinkReport::query()
            ->where('user_id', $this->user()->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyCount >= 10) {
            abort(429, 'You have reached the limit of 10 reports per day.');
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(['dead', 'wrong_scene', 'copyright', 'inappropriate'])],
            'details' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Please select a reason for reporting this link.',
            'reason.in' => 'The selected report reason is invalid.',
            'details.max' => 'Details must not exceed 500 characters.',
        ];
    }
}
