<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'visibility' => ['required', 'string', 'in:all,authenticated,guest'],
            'visibility_rules' => ['nullable', 'string'],
            'git_commit_hash' => ['nullable', 'string', 'max:100'],
            'git_commit_message' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after_or_equal' => 'End date must be after or equal to the start date.',
        ];
    }
}
