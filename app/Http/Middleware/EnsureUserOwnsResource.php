<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserOwnsResource
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resourceType = $request->route()->getParameter('model');
        $resourceId = $request->route()->getParameter('id');

        if ($resourceType && $resourceId) {
            $modelClass = 'App\\Models\\' . ucfirst($resourceType);
            
            if (class_exists($modelClass)) {
                $resource = $modelClass::find($resourceId);
                
                if ($resource && $resource->user_id !== auth()->id()) {
                    abort(403, 'Access denied. You do not own this resource.');
                }
            }
        }

        return $next($request);
    }
}
