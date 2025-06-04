<?php
/**
 * Security Helper
 * Additional security functions
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Sanitize filename more strictly
 */
function secure_filename($filename) {
    // Remove any directory traversal attempts
    $filename = str_replace(array('../', '..\\', '..', './', '.\\'), '', $filename);
    
    // Keep only safe characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Ensure it has a safe extension
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_extensions)) {
        $filename .= '.jpg'; // Force safe extension
    }
    
    // Limit length
    if (strlen($filename) > 100) {
        $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 90);
        $filename = $name . '.' . $ext;
    }
    
    return $filename;
}

/**
 * Validate products_id more strictly
 */
function validate_product_id($id) {
    if (!is_numeric($id) || $id < 1 || $id > 999999999) {
        return false;
    }
    return (int)$id;
}

/**
 * Clean HTML output
 */
function clean_html($input) {
    // Remove any scripts or dangerous tags
    $input = preg_replace('#<script[^>]*>.*?</script>#is', '', $input);
    $input = preg_replace('#<iframe[^>]*>.*?</iframe>#is', '', $input);
    $input = preg_replace('#<object[^>]*>.*?</object>#is', '', $input);
    $input = preg_replace('#<embed[^>]*>#is', '', $input);
    
    // Use htmlspecialchars
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate image URL
 */
function validate_image_url($url) {
    // Must be a valid URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Must be HTTPS
    if (strpos($url, 'https://') !== 0) {
        return false;
    }
    
    // Must be from allowed domains
    $allowed_domains = array(
        'image.tmdb.org',
        'www.themoviedb.org',
        'api.themoviedb.org'
    );
    
    $parsed = parse_url($url);
    if (!isset($parsed['host']) || !in_array($parsed['host'], $allowed_domains)) {
        return false;
    }
    
    return true;
}

/**
 * Generate secure random token
 */
function generate_secure_token($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    } else {
        // Fallback for older PHP
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= chr(mt_rand(32, 126));
        }
        return md5($token . uniqid(mt_rand(), true));
    }
}

/**
 * Check for suspicious patterns in input
 */
function has_suspicious_content($input) {
    $suspicious_patterns = array(
        '/<script/i',
        '/javascript:/i',
        '/on\w+\s*=/i', // onclick, onload, etc.
        '/data:text\/html/i',
        '/vbscript:/i',
        '/file:\/\//i',
        '/\.\.\//',
        '/\x00/', // null byte
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/base64_decode/i',
        '/eval\s*\(/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/shell_exec/i',
        '/passthru/i',
        '/\$_GET/i',
        '/\$_POST/i',
        '/\$_REQUEST/i'
    );
    
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Log security event
 */
function log_security_event($event_type, $details = array()) {
    $CI =& get_instance();
    
    $log_data = array(
        'event_type' => $event_type,
        'user_id' => $CI->session->userdata('user_id'),
        'username' => $CI->session->userdata('username'),
        'ip_address' => $CI->input->ip_address(),
        'user_agent' => $CI->input->user_agent(),
        'timestamp' => date('Y-m-d H:i:s'),
        'details' => json_encode($details)
    );
    
    // Log to file
    $log_message = sprintf(
        "[%s] Security Event: %s | User: %s | IP: %s | Details: %s",
        $log_data['timestamp'],
        $log_data['event_type'],
        $log_data['username'] ?: 'guest',
        $log_data['ip_address'],
        $log_data['details']
    );
    
    log_message('security', $log_message);
    
    // Optionally save to database
    // $CI->db->insert('security_log', $log_data);
}