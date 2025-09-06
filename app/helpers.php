<?php

if (!function_exists('format_bytes')) {
    /**
     * Format bytes to human readable format
     */
    function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('get_status_color')) {
    /**
     * Get Bootstrap color class for status
     */
    function get_status_color($status)
    {
        return match($status) {
            'active', 'running', 'online', 'success' => 'success',
            'inactive', 'stopped', 'offline' => 'secondary', 
            'error', 'failed', 'critical' => 'danger',
            'warning', 'pending' => 'warning',
            'info' => 'info',
            default => 'secondary'
        };
    }
}

if (!function_exists('get_priority_color')) {
    /**
     * Get Bootstrap color class for priority
     */
    function get_priority_color($priority)
    {
        return match($priority) {
            4, 'critical' => 'danger',
            3, 'high' => 'warning', 
            2, 'medium' => 'info',
            1, 'low' => 'secondary',
            default => 'secondary'
        };
    }
}

if (!function_exists('truncate_string')) {
    /**
     * Truncate string with ellipsis
     */
    function truncate_string($string, $length = 50)
    {
        return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
    }
}

if (!function_exists('time_ago')) {
    /**
     * Get human readable time ago
     */
    function time_ago($datetime)
    {
        return $datetime ? $datetime->diffForHumans() : 'Never';
    }
}

if (!function_exists('percentage_color')) {
    /**
     * Get color based on percentage
     */
    function percentage_color($percentage)
    {
        if ($percentage >= 90) return 'danger';
        if ($percentage >= 75) return 'warning';
        if ($percentage >= 50) return 'info';
        return 'success';
    }
}
