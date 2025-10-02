<?php
/**
 * News Aggregator Configuration Example
 * 
 * Copy this file to config.php and update the values below according to your environment.
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

// CORS Settings (for API endpoints)
define('CORS_ENABLED', true);
define('CORS_ORIGIN', '*'); // Change to your domain in production
define('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Session Configuration
define('SESSION_NAME', 'news_aggregator_session');
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('America/New_York');
?>
