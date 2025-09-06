<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ResponseHelper
{
    /**
     * Redirect dengan success message
     */
    public static function success(string $route, string $message, array $data = []): RedirectResponse
    {
        return Redirect::route($route, $data)->with('success', $message);
    }

    /**
     * Redirect dengan error message
     */
    public static function error(string $route, string $message, array $data = []): RedirectResponse
    {
        return Redirect::route($route, $data)->with('error', $message);
    }

    /**
     * Redirect back dengan success message
     */
    public static function successBack(string $message): RedirectResponse
    {
        return Redirect::back()->with('success', $message);
    }

    /**
     * Redirect back dengan error message
     */
    public static function errorBack(string $message): RedirectResponse
    {
        return Redirect::back()->with('error', $message);
    }

    /**
     * JSON response untuk API
     */
    public static function json(bool $success, string $message, array $data = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * JSON success response
     */
    public static function jsonSuccess(string $message, array $data = []): JsonResponse
    {
        return self::json(true, $message, $data);
    }

    /**
     * JSON error response
     */
    public static function jsonError(string $message, int $statusCode = 400): JsonResponse
    {
        return self::json(false, $message, [], $statusCode);
    }
}
