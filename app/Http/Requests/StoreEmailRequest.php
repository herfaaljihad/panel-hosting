<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'unique:email_accounts,email',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if domain belongs to user
                    $domain = explode('@', $value)[1] ?? '';
                    $userDomains = auth()->user()->domains()->pluck('name')->toArray();
                    
                    if (!in_array($domain, $userDomains)) {
                        $fail('You can only create email accounts for your own domains.');
                    }
                }
            ],
            'password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
            'quota_mb' => 'nullable|integer|min:50|max:10240', // 50MB to 10GB
            'forward_to' => 'nullable|email:rfc,dns',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email account already exists.',
            'password.min' => 'Password must be at least 8 characters long.',
            'quota_mb.min' => 'Minimum quota is 50 MB.',
            'quota_mb.max' => 'Maximum quota is 10 GB.',
        ];
    }
}
