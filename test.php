<?php
/**
 * News Aggregator Test Script
 * 
 * Run this script to test your installation and configuration
 * Usage: php test.php or visit http://localhost:8000/test.php
 */

// Include configuration
require_once 'config/config.php';

// Set content type
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Aggregator - System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        code { background-color: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
        .test-section { margin-bottom: 30px; }
    </style>
</head>
<body>
    <h1>📰 News Aggregator - System Test</h1>
    <p>This script tests your News Aggregator installation and configuration.</p>

    <div class="test-section">
        <h2>🔧 PHP Configuration</h2>
        <?php
        // Test PHP version
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo "<div class='test-result success'>✅ PHP Version: {$phpVersion} (Compatible)</div>";
        } else {
            echo "<div class='test-result error'>❌ PHP Version: {$phpVersion} (Requires PHP 7.4+)</div>";
        }

        // Test required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<div class='test-result success'>✅ Extension '{$ext}' is loaded</div>";
            } else {
                echo "<div class='test-result error'>❌ Extension '{$ext}' is missing</div>";
            }
        }

        // Test optional extensions
        $optionalExtensions = ['curl', 'mbstring'];
        foreach ($optionalExtensions as $ext) {
            if (extension_loaded($ext)) {
                echo "<div class='test-result success'>✅ Extension '{$ext}' is loaded (recommended)</div>";
            } else {
                echo "<div class='test-result warning'>⚠️ Extension '{$ext}' is missing (recommended but not required)</div>";
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🗄️ Database Connection</h2>
        <?php
        try {
            $pdo = getDatabaseConnection();
            echo "<div class='test-result success'>✅ Database connection successful</div>";
            
            // Test database structure
            $tables = ['users', 'preferences', 'saved_articles', 'search_history', 'api_usage'];
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
                    $count = $stmt->fetchColumn();
                    echo "<div class='test-result success'>✅ Table '{$table}' exists ({$count} records)</div>";
                } catch (Exception $e) {
                    echo "<div class='test-result error'>❌ Table '{$table}' missing or inaccessible</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='test-result error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='test-result info'>💡 Check your database configuration in config/config.php</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🔑 API Configuration</h2>
        <?php
        if (isApiKeyConfigured()) {
            echo "<div class='test-result success'>✅ News API key is configured</div>";
            
            // Test API connection
            $testUrl = NEWS_API_BASE_URL . 'top-headlines?country=us&category=general&pageSize=1&apiKey=' . NEWS_API_KEY;
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => 'User-Agent: NewsAggregator-Test/1.0'
                ]
            ]);
            
            $response = @file_get_contents($testUrl, false, $context);
            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'ok') {
                    echo "<div class='test-result success'>✅ News API connection successful</div>";
                } else {
                    $error = $data['message'] ?? 'Unknown API error';
                    echo "<div class='test-result error'>❌ News API error: " . htmlspecialchars($error) . "</div>";
                }
            } else {
                echo "<div class='test-result error'>❌ Cannot connect to News API</div>";
            }
        } else {
            echo "<div class='test-result warning'>⚠️ News API key not configured (using mock data)</div>";
            echo "<div class='test-result info'>💡 Get your API key from <a href='https://newsapi.org/' target='_blank'>newsapi.org</a> and update config/config.php</div>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🌐 API Endpoints</h2>
        <?php
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $endpoints = [
            'headlines' => '/api/headlines.php?category=general',
            'search' => '/api/search.php?query=test',
            'preferences' => '/api/preferences.php'
        ];

        foreach ($endpoints as $name => $endpoint) {
            $url = $baseUrl . $endpoint;
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                    'header' => 'Accept: application/json'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data && isset($data['success']) && $data['success']) {
                    echo "<div class='test-result success'>✅ {$name} endpoint working</div>";
                } else {
                    echo "<div class='test-result error'>❌ {$name} endpoint error</div>";
                }
            } else {
                echo "<div class='test-result error'>❌ {$name} endpoint unreachable</div>";
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>📁 File Permissions</h2>
        <?php
        $checkPaths = [
            'config/config.php',
            'api/headlines.php',
            'assets/css/style.css',
            'assets/js/app.js'
        ];

        foreach ($checkPaths as $path) {
            if (file_exists($path)) {
                if (is_readable($path)) {
                    echo "<div class='test-result success'>✅ {$path} is readable</div>";
                } else {
                    echo "<div class='test-result error'>❌ {$path} is not readable</div>";
                }
            } else {
                echo "<div class='test-result error'>❌ {$path} does not exist</div>";
            }
        }
        ?>
    </div>

    <div class="test-section">
        <h2>🎯 Quick Actions</h2>
        <div class="test-result info">
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li><a href="index.html">🏠 Visit the main application</a></li>
                <li><a href="api/headlines.php?category=technology">🔗 Test Headlines API</a></li>
                <li><a href="api/search.php?query=technology">🔍 Test Search API</a></li>
                <li><a href="api/preferences.php">⚙️ Test Preferences API</a></li>
            </ul>
        </div>
    </div>

    <div class="test-section">
        <h2>📊 System Information</h2>
        <div class="test-result info">
            <p><strong>Server Information:</strong></p>
            <ul>
                <li>PHP Version: <?php echo phpversion(); ?></li>
                <li>Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></li>
                <li>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></li>
                <li>Current Time: <?php echo date('Y-m-d H:i:s T'); ?></li>
                <li>Memory Limit: <?php echo ini_get('memory_limit'); ?></li>
                <li>Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s</li>
            </ul>
        </div>
    </div>

    <hr>
    <p><em>Test completed at <?php echo date('Y-m-d H:i:s T'); ?></em></p>
</body>
</html>
