<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreDomainRequest
 * 
 * @property string $name Domain name to be stored
 */
class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:domains,name',
                'regex:/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/',
                function ($attribute, $value, $fail) {
                    // Check if domain is valid and not a reserved domain
                    $reservedDomains = ['localhost', 'example.com', 'test.com', 'invalid'];
                    if (in_array($value, $reservedDomains)) {
                        $fail('This domain name is reserved and cannot be used.');
                    }
                    
                    // Check if user has reached domain limit
                    $user = auth()->user();
                    $package = $user->package;
                    if ($package && $package->max_domains > 0) {
                        $currentDomains = $user->domains()->count();
                        if ($currentDomains >= $package->max_domains) {
                            $fail('You have reached your domain limit. Please upgrade your package.');
                        }
                    }
                }
            ],
            'document_root' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9\/\-_\.]+$/'
            ],
            'php_version' => 'nullable|string|in:7.4,8.0,8.1,8.2,8.3',
            'ssl_enabled' => 'boolean',
            'redirect_www' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Domain name is required.',
            'name.unique' => 'This domain is already registered in the system.',
            'name.regex' => 'Please enter a valid domain name (e.g., example.com).',
            'document_root.regex' => 'Document root can only contain letters, numbers, slashes, hyphens, underscores, and dots.',
            'php_version.in' => 'Please select a valid PHP version.',
        ];
    }
}
