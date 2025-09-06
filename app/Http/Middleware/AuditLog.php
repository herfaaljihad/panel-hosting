<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLog
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $startTime = microtime(true);

        $response = $next($request);

        // Log audit trail for important operations
        if ($this->shouldLog($request)) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::channel('audit')->info('User Action', [
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'parameters' => $this->sanitizeParameters($request->all()),
                'response_status' => $response->getStatusCode(),
                'execution_time_ms' => $executionTime,
                'timestamp' => now()->toISOString(),
            ]);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged.
     */
    protected function shouldLog(Request $request): bool
    {
        $sensitiveRoutes = [
            'admin.*',
            'domains.*',
            'databases.*',
            'emails.*',
            'ftp.*',
            'ssl.*',
            'dns.*',
            'cron.*',
            'backup.*',
            'packages.*'
        ];

        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return false;
        }

        foreach ($sensitiveRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize request parameters to remove sensitive data.
     */
    protected function sanitizeParameters(array $parameters): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'current_password', '_token'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($parameters[$key])) {
                $parameters[$key] = '[REDACTED]';
            }
        }

        return $parameters;
    }
}
