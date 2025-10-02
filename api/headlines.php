<?php
/**
 * Headlines API Endpoint
 * 
 * Fetches top headlines from News API by category
 * GET /api/headlines.php?category=technology&page=1&pageSize=20
 */

require_once '../config/config.php';

// Set CORS headers
setCorsHeaders();

// Start timing for performance logging
$startTime = microtime(true);

try {
    // Get and validate parameters
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'general';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(MAX_PAGE_SIZE, max(1, intval($_GET['pageSize']))) : DEFAULT_PAGE_SIZE;
    
    // Validate category
    if (!isValidCategory($category)) {
        sendJsonResponse([
            'success' => false,
            'message' => ERROR_MESSAGES['INVALID_CATEGORY'],
            'validCategories' => SUPPORTED_CATEGORIES
        ], 400);
    }
    
    // Check if API key is configured
    if (!isApiKeyConfigured()) {
        // Return mock data for development/testing
        $mockData = getMockHeadlines($category, $page, $pageSize);
        sendJsonResponse($mockData);
    }
    
    // Build API URL
    $apiUrl = NEWS_API_BASE_URL . 'top-headlines?' . http_build_query([
        'category' => $category,
        'country' => DEFAULT_COUNTRY,
        'language' => DEFAULT_LANGUAGE,
        'page' => $page,
        'pageSize' => $pageSize,
        'apiKey' => NEWS_API_KEY
    ]);
    
    // Make API request
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: NewsAggregator/1.0',
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch data from News API');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from News API');
    }
    
    // Check API response status
    if ($data['status'] !== 'ok') {
        throw new Exception($data['message'] ?? 'API request failed');
    }
    
    // Process and clean the articles
    $articles = array_map('processArticle', $data['articles']);
    
    // Prepare response
    $response = [
        'success' => true,
        'category' => $category,
        'totalResults' => $data['totalResults'],
        'page' => $page,
        'pageSize' => $pageSize,
        'articles' => $articles
    ];
    
    // Log API usage
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/headlines.php', $_GET, 200, $executionTime);
    
    sendJsonResponse($response);
    
} catch (Exception $e) {
    // Log error
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/headlines.php', $_GET, 500, $executionTime);
    
    if (APP_DEBUG) {
        sendJsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => ERROR_MESSAGES['GENERIC_ERROR']
        ], 500);
    }
}

/**
 * Process and clean article data
 */
function processArticle($article) {
    return [
        'title' => $article['title'] ?? 'No title',
        'description' => $article['description'] ?? 'No description available',
        'url' => $article['url'] ?? '#',
        'urlToImage' => $article['urlToImage'] ?? null,
        'publishedAt' => $article['publishedAt'] ?? null,
        'source' => [
            'name' => $article['source']['name'] ?? 'Unknown Source'
        ],
        'content' => isset($article['content']) ? substr($article['content'], 0, 200) . '...' : null
    ];
}

/**
 * Generate mock headlines for development/testing
 */
function getMockHeadlines($category, $page, $pageSize) {
    $mockArticles = [
        'technology' => [
            [
                'title' => 'Revolutionary AI Breakthrough Announced by Tech Giants',
                'description' => 'Major technology companies unveil groundbreaking artificial intelligence capabilities that could transform industries.',
                'url' => 'https://example.com/ai-breakthrough',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=AI+News',
                'publishedAt' => date('c', strtotime('-2 hours')),
                'source' => ['name' => 'Tech Today']
            ],
            [
                'title' => 'New Smartphone Features Change Mobile Computing',
                'description' => 'Latest smartphone releases include innovative features that push the boundaries of mobile technology.',
                'url' => 'https://example.com/smartphone-features',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Mobile+Tech',
                'publishedAt' => date('c', strtotime('-4 hours')),
                'source' => ['name' => 'Mobile News']
            ],
            [
                'title' => 'Quantum Computing Reaches New Milestone',
                'description' => 'Researchers achieve significant progress in quantum computing, bringing practical applications closer to reality.',
                'url' => 'https://example.com/quantum-computing',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Quantum+Tech',
                'publishedAt' => date('c', strtotime('-6 hours')),
                'source' => ['name' => 'Science Tech']
            ]
        ],
        'business' => [
            [
                'title' => 'Stock Markets Reach Record Highs Amid Economic Growth',
                'description' => 'Global stock markets continue their upward trend as economic indicators show strong growth across sectors.',
                'url' => 'https://example.com/stock-markets',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Stock+Market',
                'publishedAt' => date('c', strtotime('-1 hour')),
                'source' => ['name' => 'Business Daily']
            ],
            [
                'title' => 'Major Corporate Merger Announced',
                'description' => 'Two industry leaders announce merger plans that could reshape the competitive landscape.',
                'url' => 'https://example.com/corporate-merger',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Business+News',
                'publishedAt' => date('c', strtotime('-3 hours')),
                'source' => ['name' => 'Financial Times']
            ]
        ],
        'sports' => [
            [
                'title' => 'Championship Finals Set Record Viewership',
                'description' => 'The latest championship games attract millions of viewers worldwide, setting new broadcasting records.',
                'url' => 'https://example.com/championship-finals',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Sports+News',
                'publishedAt' => date('c', strtotime('-30 minutes')),
                'source' => ['name' => 'Sports Network']
            ],
            [
                'title' => 'Olympic Preparations Underway',
                'description' => 'Athletes and organizers make final preparations for the upcoming Olympic games.',
                'url' => 'https://example.com/olympic-prep',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Olympics',
                'publishedAt' => date('c', strtotime('-2 hours')),
                'source' => ['name' => 'Olympic News']
            ]
        ]
    ];
    
    // Get articles for the category, fallback to general
    $articles = $mockArticles[$category] ?? $mockArticles['technology'];
    
    // Add some general articles if needed
    if (count($articles) < $pageSize) {
        $generalArticles = [
            [
                'title' => 'Breaking: Major News Development',
                'description' => 'Important news story that affects multiple sectors and communities.',
                'url' => 'https://example.com/breaking-news',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Breaking+News',
                'publishedAt' => date('c', strtotime('-15 minutes')),
                'source' => ['name' => 'News Network']
            ],
            [
                'title' => 'Global Climate Summit Concludes',
                'description' => 'World leaders reach new agreements on climate action and environmental protection.',
                'url' => 'https://example.com/climate-summit',
                'urlToImage' => 'https://via.placeholder.com/400x200?text=Climate+News',
                'publishedAt' => date('c', strtotime('-5 hours')),
                'source' => ['name' => 'Environmental News']
            ]
        ];
        $articles = array_merge($articles, $generalArticles);
    }
    
    // Simulate pagination
    $startIndex = ($page - 1) * $pageSize;
    $paginatedArticles = array_slice($articles, $startIndex, $pageSize);
    
    return [
        'success' => true,
        'category' => $category,
        'totalResults' => count($articles),
        'page' => $page,
        'pageSize' => $pageSize,
        'articles' => $paginatedArticles,
        'note' => 'This is mock data. Configure NEWS_API_KEY in config.php to use real data.'
    ];
}
?>
