<?php
/**
 * Rate Limiter Helper
 * Prevents abuse and DOS attacks
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Check rate limit for user action
 */
function check_rate_limit($action, $identifier = null, $max_attempts = 60, $window = 3600) {
    $CI =& get_instance();
    
    // Use IP address if no identifier provided
    if (!$identifier) {
        $identifier = $CI->input->ip_address();
    }
    
    $key = 'rate_limit_' . $action . '_' . md5($identifier);
    $cache_file = APPPATH . 'cache/rate_limit/' . $key . '.txt';
    
    // Create cache directory if not exists
    $cache_dir = dirname($cache_file);
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0777, true);
    }
    
    // Get current attempts
    $attempts = array();
    if (file_exists($cache_file)) {
        $data = file_get_contents($cache_file);
        $attempts = $data ? json_decode($data, true) : array();
    }
    
    // Clean old attempts
    $current_time = time();
    $attempts = array_filter($attempts, function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    // Check limit
    if (count($attempts) >= $max_attempts) {
        log_message('warning', "Rate limit exceeded for {$action} by {$identifier}");
        return false;
    }
    
    // Add current attempt
    $attempts[] = $current_time;
    
    // Save updated attempts
    file_put_contents($cache_file, json_encode(array_values($attempts)), LOCK_EX);
    
    return true;
}

/**
 * Get remaining attempts
 */
function get_rate_limit_remaining($action, $identifier = null, $max_attempts = 60, $window = 3600) {
    $CI =& get_instance();
    
    if (!$identifier) {
        $identifier = $CI->input->ip_address();
    }
    
    $key = 'rate_limit_' . $action . '_' . md5($identifier);
    $cache_file = APPPATH . 'cache/rate_limit/' . $key . '.txt';
    
    if (!file_exists($cache_file)) {
        return $max_attempts;
    }
    
    $data = file_get_contents($cache_file);
    $attempts = $data ? json_decode($data, true) : array();
    
    // Clean old attempts
    $current_time = time();
    $attempts = array_filter($attempts, function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    return max(0, $max_attempts - count($attempts));
}

/**
 * Clean expired rate limit files
 */
function clean_rate_limit_cache($older_than = 86400) { // 24 hours
    $cache_dir = APPPATH . 'cache/rate_limit/';
    
    if (!is_dir($cache_dir)) {
        return 0;
    }
    
    $cleaned = 0;
    $current_time = time();
    
    $files = glob($cache_dir . '*.txt');
    foreach ($files as $file) {
        if (is_file($file) && ($current_time - filemtime($file)) > $older_than) {
            if (@unlink($file)) {
                $cleaned++;
            }
        }
    }
    
    return $cleaned;
}

/**
 * Reset rate limit for specific action/user
 */
function reset_rate_limit($action, $identifier = null) {
    $CI =& get_instance();
    
    if (!$identifier) {
        $identifier = $CI->input->ip_address();
    }
    
    $key = 'rate_limit_' . $action . '_' . md5($identifier);
    $cache_file = APPPATH . 'cache/rate_limit/' . $key . '.txt';
    
    if (file_exists($cache_file)) {
        return @unlink($cache_file);
    }
    
    return true;
}