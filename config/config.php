<?php
/**
 * News Aggregator Configuration File
 * 
 * This file contains all the configuration settings for the News Aggregator application.
 * Make sure to update the values below according to your environment.
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_aggregator');
define('DB_USER', 'root');  // Change this to your MySQL username
define('DB_PASS', '');      // Change this to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// News API Configuration
// Get your API key from: https://newsapi.org/
define('NEWS_API_KEY', 'YOUR_NEWS_API_KEY_HERE');
define('NEWS_API_BASE_URL', 'https://newsapi.org/v2/');

// Application Settings
define('APP_NAME', 'News Aggregator');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true); // Set to false in production

// API Rate Limiting
define('API_RATE_LIMIT', 100); // Requests per hour
define('API_CACHE_DURATION', 300); // Cache duration in seconds (5 minutes)

// Default Settings
define('DEFAULT_COUNTRY', 'us');
define('DEFAULT_LANGUAGE', 'en');
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Supported Categories
define('SUPPORTED_CATEGORIES', [
    'general',
    'business',
    'entertainment',
    'health',
    'science',
    'sports',
    'technology'
]);

// Error Messages
define('ERROR_MESSAGES', [
    'DB_CONNECTION' => 'Unable to connect to the database',
    'API_KEY_MISSING' => 'News API key is not configured',
    'API_REQUEST_FAILED' => 'Failed to fetch news from API',
    'INVALID_CATEGORY' => 'Invalid news category',
    'INVALID_QUERY' => 'Invalid search query',
    'RATE_LIMIT_EXCEEDED' => 'API rate limit exceeded',
    'GENERIC_ERROR' => 'An unexpected error occurred'
]);

// CORS Settings (for API endpoints)
define('CORS_ENABLED', true);
define('CORS_ORIGIN', '*'); // Change to your domain in production
define('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Session Configuration
define('SESSION_NAME', 'news_aggregator_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// File Upload Settings (for future features)
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Timezone
date_default_timezone_set('America/New_York');

// Helper function to get database connection
function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                die('Database connection failed');
            }
        }
    }
    
    return $pdo;
}

// Helper function to validate API key
function isApiKeyConfigured() {
    return NEWS_API_KEY !== 'YOUR_NEWS_API_KEY_HERE' && !empty(NEWS_API_KEY);
}

// Helper function to set CORS headers
function setCorsHeaders() {
    if (CORS_ENABLED) {
        header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
        header('Access-Control-Allow-Methods: ' . CORS_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_HEADERS);
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}

// Helper function to log API usage
function logApiUsage($endpoint, $params = [], $status = 200, $executionTime = 0) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            INSERT INTO api_usage (endpoint, request_params, response_status, execution_time_ms) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $endpoint,
            json_encode($params),
            $status,
            $executionTime
        ]);
    } catch (Exception $e) {
        // Log error but don't break the application
        if (APP_DEBUG) {
            error_log('Failed to log API usage: ' . $e->getMessage());
        }
    }
}

// Helper function to validate category
function isValidCategory($category) {
    return in_array($category, SUPPORTED_CATEGORIES);
}

// Helper function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Helper function to generate API response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Error handler for production
if (!APP_DEBUG) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
