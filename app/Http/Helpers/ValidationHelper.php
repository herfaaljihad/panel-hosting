<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidationHelper
{
    /**
     * Validate domain name
     */
    public static function validateDomain(string $domain): bool
    {
        $validator = Validator::make(['domain' => $domain], [
            'domain' => ['required', 'string', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]*\.([a-zA-Z]{2,})$/']
        ]);

        return !$validator->fails();
    }

    /**
     * Validate email address
     */
    public static function validateEmail(string $email): bool
    {
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email']
        ]);

        return !$validator->fails();
    }

    /**
     * Validate cron expression
     */
    public static function validateCronExpression(string $cron): bool
    {
        $validator = Validator::make(['cron' => $cron], [
            'cron' => ['required', 'regex:/^(\*|([0-5]?\d)) (\*|([01]?\d|2[0-3])) (\*|([0-2]?\d|3[01])) (\*|([0]?\d|1[0-2])) (\*|[0-6])$/']
        ]);

        return !$validator->fails();
    }

    /**
     * Validate filename
     */
    public static function validateFilename(string $filename): bool
    {
        $validator = Validator::make(['filename' => $filename], [
            'filename' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]+$/']
        ]);

        return !$validator->fails();
    }

    /**
     * Validate port number
     */
    public static function validatePort(int $port): bool
    {
        return $port >= 1 && $port <= 65535;
    }

    /**
     * Validate IP address (IPv4)
     */
    public static function validateIPv4(string $ip): bool
    {
        $validator = Validator::make(['ip' => $ip], [
            'ip' => ['required', 'ip']
        ]);

        return !$validator->fails();
    }

    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf kecil';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf besar';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password harus mengandung angka';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password harus mengandung karakter khusus';
        }
        
        return $errors;
    }
}
