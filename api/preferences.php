<?php
/**
 * Preferences API Endpoint
 * 
 * Handles user preferences for news categories
 * GET /api/preferences.php - Get user preferences
 * POST /api/preferences.php - Save user preferences
 */

require_once '../config/config.php';

// Set CORS headers
setCorsHeaders();

// Start timing for performance logging
$startTime = microtime(true);

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetPreferences();
            break;
            
        case 'POST':
            handleSavePreferences();
            break;
            
        default:
            sendJsonResponse([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
    }
    
} catch (Exception $e) {
    // Log error
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/preferences.php', $_REQUEST, 500, $executionTime);
    
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
 * Handle GET request - retrieve user preferences
 */
function handleGetPreferences() {
    global $startTime;
    
    // For now, we'll use session-based preferences since we don't have user authentication
    // In a real application, this would be based on authenticated user ID
    $userId = getCurrentUserId();
    
    if ($userId) {
        // Get preferences from database
        $preferences = getUserPreferencesFromDB($userId);
    } else {
        // Get preferences from session for anonymous users
        $preferences = $_SESSION['preferences'] ?? ['general'];
    }
    
    // Ensure we have valid categories
    $preferences = array_filter($preferences, 'isValidCategory');
    
    // If no valid preferences, set default
    if (empty($preferences)) {
        $preferences = ['general'];
    }
    
    $response = [
        'success' => true,
        'preferences' => array_values($preferences),
        'availableCategories' => SUPPORTED_CATEGORIES
    ];
    
    // Log API usage
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/preferences.php', ['method' => 'GET'], 200, $executionTime);
    
    sendJsonResponse($response);
}

/**
 * Handle POST request - save user preferences
 */
function handleSavePreferences() {
    global $startTime;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid JSON input'
        ], 400);
    }
    
    // Validate categories
    $categories = $input['categories'] ?? [];
    
    if (!is_array($categories)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Categories must be an array'
        ], 400);
    }
    
    // Filter valid categories
    $validCategories = array_filter($categories, 'isValidCategory');
    $validCategories = array_unique($validCategories);
    
    if (empty($validCategories)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'At least one valid category must be selected',
            'validCategories' => SUPPORTED_CATEGORIES
        ], 400);
    }
    
    if (count($validCategories) > 7) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Maximum 7 categories can be selected'
        ], 400);
    }
    
    // Get current user ID
    $userId = getCurrentUserId();
    
    if ($userId) {
        // Save to database for authenticated users
        $success = saveUserPreferencesToDB($userId, $validCategories);
        if (!$success) {
            throw new Exception('Failed to save preferences to database');
        }
    } else {
        // Save to session for anonymous users
        $_SESSION['preferences'] = array_values($validCategories);
    }
    
    $response = [
        'success' => true,
        'message' => 'Preferences saved successfully',
        'preferences' => array_values($validCategories)
    ];
    
    // Log API usage
    $executionTime = round((microtime(true) - $startTime) * 1000);
    logApiUsage('/api/preferences.php', ['method' => 'POST', 'categories' => $validCategories], 200, $executionTime);
    
    sendJsonResponse($response);
}

/**
 * Get current user ID (placeholder for authentication system)
 */
function getCurrentUserId() {
    // For now, return a demo user ID if set in session
    // In a real application, this would check authentication and return the actual user ID
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get user preferences from database
 */
function getUserPreferencesFromDB($userId) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT category FROM preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $preferences = [];
        while ($row = $stmt->fetch()) {
            $preferences[] = $row['category'];
        }
        
        return $preferences;
    } catch (Exception $e) {
        if (APP_DEBUG) {
            error_log('Failed to get user preferences: ' . $e->getMessage());
        }
        return ['general']; // Default fallback
    }
}

/**
 * Save user preferences to database
 */
function saveUserPreferencesToDB($userId, $categories) {
    try {
        $pdo = getDatabaseConnection();
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete existing preferences
        $stmt = $pdo->prepare("DELETE FROM preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Insert new preferences
        $stmt = $pdo->prepare("INSERT INTO preferences (user_id, category) VALUES (?, ?)");
        foreach ($categories as $category) {
            $stmt->execute([$userId, $category]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        if (APP_DEBUG) {
            error_log('Failed to save user preferences: ' . $e->getMessage());
        }
        return false;
    }
}

/**
 * Get popular categories based on user preferences
 */
function getPopularCategories() {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("
            SELECT category, COUNT(*) as count 
            FROM preferences 
            GROUP BY category 
            ORDER BY count DESC 
            LIMIT 5
        ");
        
        $popular = [];
        while ($row = $stmt->fetch()) {
            $popular[] = [
                'category' => $row['category'],
                'count' => (int)$row['count']
            ];
        }
        
        return $popular;
    } catch (Exception $e) {
        if (APP_DEBUG) {
            error_log('Failed to get popular categories: ' . $e->getMessage());
        }
        return [];
    }
}

/**
 * Get user statistics for preferences
 */
function getUserPreferenceStats($userId) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_preferences,
                MIN(created_at) as first_preference_date,
                MAX(created_at) as last_updated
            FROM preferences 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetch();
    } catch (Exception $e) {
        if (APP_DEBUG) {
            error_log('Failed to get user preference stats: ' . $e->getMessage());
        }
        return null;
    }
}

// Additional endpoint for getting preference statistics (if requested via query parameter)
if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = getCurrentUserId();
        $stats = [
            'popularCategories' => getPopularCategories(),
            'userStats' => $userId ? getUserPreferenceStats($userId) : null
        ];
        
        sendJsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
?>
