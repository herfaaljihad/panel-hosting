<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'tar', 'gz'])
                    ->max(50 * 1024), // 50MB max
            ],
            'directory' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9\/\-_\.]+$/',
                function ($attribute, $value, $fail) {
                    // Prevent directory traversal
                    if (str_contains($value, '..') || str_contains($value, '~')) {
                        $fail('Invalid directory path.');
                    }
                }
            ],
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.types' => 'Only the following file types are allowed: jpg, jpeg, png, gif, pdf, doc, docx, txt, zip, tar, gz.',
            'file.max' => 'File size cannot exceed 50MB.',
            'directory.regex' => 'Directory path contains invalid characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize directory path
        if ($this->directory) {
            $this->merge([
                'directory' => trim($this->directory, '/'),
            ]);
        }
    }
}
