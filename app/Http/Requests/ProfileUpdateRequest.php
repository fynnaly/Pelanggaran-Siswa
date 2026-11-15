<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $identityRules = $this->user()?->hasRole('siswa')
            ? [
                'name' => ['prohibited'],
                'email' => ['prohibited'],
                'nip' => ['prohibited'],
            ]
            : [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'lowercase',
                    'email',
                    'max:255',
                    Rule::unique(User::class)->ignore($this->user()->id),
                ],
                'nip' => ['nullable', 'string', 'max:40'],
            ];

        return $identityRules + [
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
