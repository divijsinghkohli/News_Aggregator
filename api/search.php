<?php
/**
 * Search API Endpoint
 * 
 * Searches news articles by keyword using News API
 * GET /api/search.php?query=artificial+intelligence&page=1&pageSize=20
 */

require_once '../config/config.php';

// Set CORS headers
setCorsHeaders();

// Start timing for performance logging
$startTime = microtime(true);

try {
    // Get and validate parameters
    $query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(MAX_PAGE_SIZE, max(1, intval($_GET['pageSize']))) : DEFAULT_PAGE_SIZE;
    $sortBy = isset($_GET['sortBy']) ? sanitizeInput($_GET['sortBy']) : 'publishedAt';
    $language = isset($_GET['language']) ? sanitizeInput($_GET['language']) : DEFAULT_LANGUAGE;
    
    // Validate query
    if (empty($query) || strlen($query) < 2) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Search query must be at least 2 characters long'
        ], 400);
    }
    
    if (strlen($query) > 500) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Search query is too long (maximum 500 characters)'
        ], 400);
    }
    
    // Validate sort parameter
    $validSortOptions = ['relevancy', 'popularity', 'publishedAt'];
    if (!in_array($sortBy, $validSortOptions)) {
        $sortBy = 'publishedAt';
    }
    
    // Log search query
    logSearchQuery($query);
    
    // Check if API key is configured
    if (!isApiKeyConfigured()) {
        // Return mock search results for development/testing
        $mockData = getMockSearchResults($query, $page, $pageSize, $sortBy);
        sendJsonResponse($mockData);
    }
    
    // Build API URL for everything endpoint (search)
    $apiUrl = NEWS_API_BASE_URL . 'everything?' . http_build_query([
        'q' => $query,
        'language' => $language,
        'sortBy' => $sortBy,
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
            'timeout' => 15
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch search results from News API');
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from News API');
    }
    
    // Check API response status
    if ($data['status'] !== 'ok') {
        throw new Exception($data['message'] ?? 'Search request failed');
    }
    
    // Process and clean the articles
    $articles = array_map('processSearchArticle', $data['articles']);
    
    // Filter out articles with missing essential data
    $articles = array_filter($articles, function($article) {
        return !empty($article['title']) && !empty($article['url']);
    });
    
    // Re-index array after filtering
    $articles = array_values($articles);
    
    // Prepare response
    $response = [
        'success' => true,
        'query' => $query,
        'totalResults' => $data['totalResults'],
        'page' => $page,
        'pageSize' => $pageSize,
        'sortBy' => $sortBy,
        'articles' => $articles
    ];
    
    // Update search history with results count
    updateSearchHistory($query, count($articles));
    
    // Log API usage
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/search.php', $_GET, 200, $executionTime);
    
    sendJsonResponse($response);
    
} catch (Exception $e) {
    // Log error
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/search.php', $_GET, 500, $executionTime);
    
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
 * Process and clean search article data
 */
function processSearchArticle($article) {
    // Calculate relevance score based on title and description
    $relevanceScore = 0;
    if (!empty($article['title'])) $relevanceScore += 3;
    if (!empty($article['description'])) $relevanceScore += 2;
    if (!empty($article['content'])) $relevanceScore += 1;
    if (!empty($article['urlToImage'])) $relevanceScore += 1;
    
    return [
        'title' => $article['title'] ?? 'No title',
        'description' => $article['description'] ?? 'No description available',
        'url' => $article['url'] ?? '#',
        'urlToImage' => $article['urlToImage'] ?? null,
        'publishedAt' => $article['publishedAt'] ?? null,
        'source' => [
            'name' => $article['source']['name'] ?? 'Unknown Source'
        ],
        'content' => isset($article['content']) ? substr($article['content'], 0, 300) . '...' : null,
        'relevanceScore' => $relevanceScore
    ];
}

/**
 * Log search query to database
 */
function logSearchQuery($query) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            INSERT INTO search_history (user_id, search_query, searched_at) 
            VALUES (?, ?, NOW())
        ");
        
        // For now, we'll use NULL for user_id (anonymous search)
        // This can be updated when user authentication is implemented
        $stmt->execute([null, $query]);
    } catch (Exception $e) {
        // Don't break the search if logging fails
        if (APP_DEBUG) {
            error_log('Failed to log search query: ' . $e->getMessage());
        }
    }
}

/**
 * Update search history with results count
 */
function updateSearchHistory($query, $resultsCount) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            UPDATE search_history 
            SET results_count = ? 
            WHERE search_query = ? 
            AND searched_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ORDER BY searched_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$resultsCount, $query]);
    } catch (Exception $e) {
        // Don't break the search if update fails
        if (APP_DEBUG) {
            error_log('Failed to update search history: ' . $e->getMessage());
        }
    }
}

/**
 * Generate mock search results for development/testing
 */
function getMockSearchResults($query, $page, $pageSize, $sortBy) {
    $mockArticles = [
        [
            'title' => "Search Results for '{$query}' - Advanced Technology Breakthrough",
            'description' => "Latest developments in {$query} technology show promising results for future applications and innovations.",
            'url' => 'https://example.com/search-result-1',
            'urlToImage' => 'https://via.placeholder.com/400x200?text=' . urlencode($query),
            'publishedAt' => date('c', strtotime('-1 hour')),
            'source' => ['name' => 'Tech Research'],
            'relevanceScore' => 7
        ],
        [
            'title' => "Industry Analysis: {$query} Market Trends",
            'description' => "Comprehensive analysis of current market trends and future projections related to {$query}.",
            'url' => 'https://example.com/search-result-2',
            'urlToImage' => 'https://via.placeholder.com/400x200?text=Market+Analysis',
            'publishedAt' => date('c', strtotime('-3 hours')),
            'source' => ['name' => 'Market Watch'],
            'relevanceScore' => 6
        ],
        [
            'title' => "Expert Opinion: The Future of {$query}",
            'description' => "Leading experts share their insights on the future implications and potential of {$query}.",
            'url' => 'https://example.com/search-result-3',
            'urlToImage' => 'https://via.placeholder.com/400x200?text=Expert+Opinion',
            'publishedAt' => date('c', strtotime('-5 hours')),
            'source' => ['name' => 'Expert Review'],
            'relevanceScore' => 5
        ],
        [
            'title' => "Global Impact: How {$query} is Changing the World",
            'description' => "Exploring the global impact and transformative effects of {$query} across different industries.",
            'url' => 'https://example.com/search-result-4',
            'urlToImage' => 'https://via.placeholder.com/400x200?text=Global+Impact',
            'publishedAt' => date('c', strtotime('-8 hours')),
            'source' => ['name' => 'Global News'],
            'relevanceScore' => 4
        ],
        [
            'title' => "Research Study: New Findings on {$query}",
            'description' => "Recent research reveals new insights and findings that could revolutionize our understanding of {$query}.",
            'url' => 'https://example.com/search-result-5',
            'urlToImage' => 'https://via.placeholder.com/400x200?text=Research+Study',
            'publishedAt' => date('c', strtotime('-12 hours')),
            'source' => ['name' => 'Research Journal'],
            'relevanceScore' => 6
        ]
    ];
    
    // Sort articles based on sortBy parameter
    switch ($sortBy) {
        case 'relevancy':
            usort($mockArticles, function($a, $b) {
                return $b['relevanceScore'] - $a['relevanceScore'];
            });
            break;
        case 'popularity':
            // For mock data, we'll randomize to simulate popularity
            shuffle($mockArticles);
            break;
        case 'publishedAt':
        default:
            // Already sorted by publishedAt (newest first)
            break;
    }
    
    // Simulate pagination
    $startIndex = ($page - 1) * $pageSize;
    $paginatedArticles = array_slice($mockArticles, $startIndex, $pageSize);
    
    return [
        'success' => true,
        'query' => $query,
        'totalResults' => count($mockArticles),
        'page' => $page,
        'pageSize' => $pageSize,
        'sortBy' => $sortBy,
        'articles' => $paginatedArticles,
        'note' => 'This is mock search data. Configure NEWS_API_KEY in config.php to use real data.'
    ];
}
?>
