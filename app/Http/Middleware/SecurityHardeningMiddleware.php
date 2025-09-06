<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class SecurityHardeningMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Skip ALL security checks for authentication routes
        if ($this->shouldSkipSecurityChecks($request)) {
            return $next($request);
        }

        // Check for suspicious patterns
        $this->checkSuspiciousActivity($request);
        
        // Validate request headers
        $this->validateHeaders($request);
        
        // Check for known attack patterns
        $this->checkAttackPatterns($request);
        
        // Rate limiting per IP (more lenient)
        $this->applyRateLimiting($request);

        return $next($request);
    }

    protected function shouldSkipSecurityChecks(Request $request)
    {
        $exemptRoutes = [
            'login',
            'register',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
            'password.confirm',
            'password.update',
            'verification.notice',
            'verification.verify',
            'verification.send',
        ];

        $exemptPaths = [
            '/login',
            '/register',
            '/logout',
            '/forgot-password',
            '/reset-password',
            '/password',
            '/confirm-password',
            '/verify-email',
            '/email/verification-notification',
        ];

        // Check by route name
        if ($request->route() && in_array($request->route()->getName(), $exemptRoutes)) {
            return true;
        }

        // Check by path
        $path = $request->getPathInfo();
        foreach ($exemptPaths as $exemptPath) {
            if (strpos($path, $exemptPath) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function checkSuspiciousActivity(Request $request)
    {
        // Skip this check entirely for authentication-related routes
        if ($this->shouldSkipSecurityChecks($request)) {
            return;
        }

        $suspicious = false;
        $patterns = [
            // SQL Injection patterns - more specific to avoid false positives
            '/(\bunion\b\s+select\b|\bselect\b\s+.*\bunion\b)/i',
            '/(\'\s*or\s*\'\d+\'\s*=\s*\'\d+\'|\"\s*or\s*\"\d+\"\s*=\s*\"\d+\")/i',
            '/(\'\s*;\s*drop\s+table|\"\s*;\s*drop\s+table)/i',
            
            // XSS patterns
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript\s*:\s*[^;]*/i',
            '/on\w+\s*=\s*["\'][^"\']*["\']/',
            
            // Path traversal
            '/\.\.\/.*\/etc\//',
            '/\.\.[\/\\\\].*[\/\\\\]etc[\/\\\\]/',
            
            // Command injection
            '/;\s*(cat|ls|pwd|id|whoami|uname|rm|cp|mv)\s/i',
            '/\|\s*(cat|ls|pwd|id|whoami|uname|rm|cp|mv)\s/i',
        ];

        $content = $request->getContent();
        $queryString = $request->getQueryString();
        $userAgent = $request->header('User-Agent', '');

        // Skip check if content contains legitimate password fields
        if (stripos($content, 'password') !== false && strlen($content) < 1000) {
            return; // Likely a legitimate password form
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content) || 
                preg_match($pattern, $queryString) || 
                preg_match($pattern, $userAgent)) {
                $suspicious = true;
                break;
            }
        }

        if ($suspicious) {
            Log::channel('security')->critical('Suspicious activity detected', [
                'ip' => $request->ip(),
                'user_agent' => $userAgent,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'content' => $content,
                'user_id' => Auth::id(),
            ]);

            // Block the request
            abort(403, 'Suspicious activity detected');
        }
    }

    protected function validateHeaders(Request $request)
    {
        // Check for missing or suspicious headers
        $userAgent = $request->header('User-Agent');
        
        if (empty($userAgent) || strlen($userAgent) > 500) {
            Log::channel('security')->warning('Invalid User-Agent header', [
                'ip' => $request->ip(),
                'user_agent' => $userAgent,
            ]);
        }

        // Check for suspicious referrers
        $referer = $request->header('Referer');
        if ($referer && !$this->isValidReferer($referer)) {
            Log::channel('security')->warning('Suspicious referer', [
                'ip' => $request->ip(),
                'referer' => $referer,
            ]);
        }
    }

    protected function checkAttackPatterns(Request $request)
    {
        $allInput = array_merge(
            $request->all(),
            [$request->getContent()],
            $request->headers->all()
        );

        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                // Check for common attack patterns
                if ($this->containsAttackPattern($value)) {
                    Log::channel('security')->alert('Attack pattern detected', [
                        'ip' => $request->ip(),
                        'field' => $key,
                        'value' => $value,
                        'user_id' => Auth::id(),
                    ]);

                    abort(403, 'Malicious content detected');
                }
            }
        }
    }

    protected function containsAttackPattern($input)
    {
        // Skip check for common legitimate strings
        $legitimatePatterns = [
            'password',
            'confirm_password',
            'current_password',
            'new_password',
            'password_confirmation',
        ];

        foreach ($legitimatePatterns as $pattern) {
            if (stripos($input, $pattern) !== false && strlen($input) < 200) {
                return false; // Skip attack pattern check for legitimate password fields
            }
        }

        $patterns = [
            // More sophisticated patterns - only very specific dangerous functions
            '/eval\s*\([^)]*\)/i',
            '/exec\s*\([^)]*\)/i',
            '/system\s*\([^)]*\)/i',
            '/shell_exec\s*\([^)]*\)/i',
            '/passthru\s*\([^)]*\)/i',
            '/`[^`]*`/i', // Backtick execution
            
            // Only match dangerous file operations with specific patterns
            '/file_get_contents\s*\(\s*["\'][^"\']*\.(php|inc|conf)["\']/',
            '/file_put_contents\s*\(\s*["\'][^"\']*\.(php|inc|conf)["\']/',
            
            // Database injection patterns
            '/union\s+select/i',
            '/drop\s+table/i',
            '/truncate\s+table/i',
            
            // Only match very obvious XSS attempts
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript\s*:/i',
            '/on\w+\s*=\s*["\'][^"\']*["\']/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    protected function applyRateLimiting(Request $request)
    {
        $key = 'security_rate_limit:' . $request->ip();
        
        // Very lenient rate limiting - allow 500 requests per hour
        $maxAttempts = 500;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::channel('security')->warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'attempts' => RateLimiter::attempts($key),
                'path' => $request->getPathInfo(),
            ]);

            abort(429, 'Too many requests');
        }

        RateLimiter::hit($key, 3600); // 1 hour window
    }

    protected function isValidReferer($referer)
    {
        $allowedDomains = [
            config('app.url'),
            'https://' . request()->getHost(),
            'http://' . request()->getHost(),
        ];

        foreach ($allowedDomains as $domain) {
            if (strpos($referer, $domain) === 0) {
                return true;
            }
        }

        return false;
    }
}
