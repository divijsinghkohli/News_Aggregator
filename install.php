<?php
/**
 * News Aggregator Installation Script
 * 
 * This script helps set up the News Aggregator application
 * Run this script once to configure the application
 */

// Check if already installed
if (file_exists('config/config.php') && !isset($_GET['force'])) {
    die('Application already installed. Add ?force=1 to reinstall.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$maxSteps = 4;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Aggregator - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .install-container { max-width: 800px; margin: 50px auto; }
        .step-indicator { margin-bottom: 30px; }
        .step-indicator .step { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 50%; margin-right: 10px; }
        .step.active { background-color: #0d6efd; color: white; }
        .step.completed { background-color: #198754; color: white; }
        .step.pending { background-color: #e9ecef; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="text-center mb-5">
            <h1><i class="fas fa-newspaper"></i> News Aggregator</h1>
            <p class="lead">Installation & Setup Wizard</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator text-center">
            <?php for ($i = 1; $i <= $maxSteps; $i++): ?>
                <span class="step <?php echo $i < $step ? 'completed' : ($i == $step ? 'active' : 'pending'); ?>">
                    <?php echo $i; ?>
                </span>
            <?php endfor; ?>
        </div>

        <?php if ($step == 1): ?>
            <!-- Step 1: Welcome & Requirements -->
            <div class="card">
                <div class="card-header">
                    <h3>Step 1: Welcome & System Requirements</h3>
                </div>
                <div class="card-body">
                    <h5>Welcome to News Aggregator!</h5>
                    <p>This wizard will help you set up your News Aggregator application.</p>
                    
                    <h6>System Requirements:</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            PHP 7.4+ 
                            <span class="badge bg-<?php echo version_compare(phpversion(), '7.4.0', '>=') ? 'success' : 'danger'; ?>">
                                <?php echo phpversion(); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            PDO Extension 
                            <span class="badge bg-<?php echo extension_loaded('pdo') ? 'success' : 'danger'; ?>">
                                <?php echo extension_loaded('pdo') ? 'Available' : 'Missing'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            PDO MySQL Extension 
                            <span class="badge bg-<?php echo extension_loaded('pdo_mysql') ? 'success' : 'danger'; ?>">
                                <?php echo extension_loaded('pdo_mysql') ? 'Available' : 'Missing'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            JSON Extension 
                            <span class="badge bg-<?php echo extension_loaded('json') ? 'success' : 'danger'; ?>">
                                <?php echo extension_loaded('json') ? 'Available' : 'Missing'; ?>
                            </span>
                        </li>
                    </ul>
                    
                    <div class="mt-4">
                        <a href="?step=2" class="btn btn-primary">Continue to Database Setup</a>
                    </div>
                </div>
            </div>

        <?php elseif ($step == 2): ?>
            <!-- Step 2: Database Configuration -->
            <div class="card">
                <div class="card-header">
                    <h3>Step 2: Database Configuration</h3>
                </div>
                <div class="card-body">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // Process database configuration
                        $dbHost = $_POST['db_host'] ?? 'localhost';
                        $dbName = $_POST['db_name'] ?? 'news_aggregator';
                        $dbUser = $_POST['db_user'] ?? 'root';
                        $dbPass = $_POST['db_pass'] ?? '';
                        
                        try {
                            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
                            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ]);
                            
                            // Save configuration
                            $configContent = file_get_contents('config/config.example.php');
                            $configContent = str_replace('localhost', $dbHost, $configContent);
                            $configContent = str_replace('news_aggregator', $dbName, $configContent);
                            $configContent = str_replace('root', $dbUser, $configContent);
                            $configContent = str_replace("define('DB_PASS', '');", "define('DB_PASS', '{$dbPass}');", $configContent);
                            
                            file_put_contents('config/config.php', $configContent);
                            
                            echo '<div class="alert alert-success">Database connection successful! Configuration saved.</div>';
                            echo '<a href="?step=3" class="btn btn-primary">Continue to API Setup</a>';
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                    }
                    
                    if (!isset($_POST['db_host'])):
                    ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" value="news_aggregator" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_user" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass">
                        </div>
                        <button type="submit" class="btn btn-primary">Test & Save Configuration</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($step == 3): ?>
            <!-- Step 3: API Configuration -->
            <div class="card">
                <div class="card-header">
                    <h3>Step 3: News API Configuration</h3>
                </div>
                <div class="card-body">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $apiKey = $_POST['api_key'] ?? '';
                        
                        if (!empty($apiKey)) {
                            // Update config file with API key
                            $configContent = file_get_contents('config/config.php');
                            $configContent = str_replace('YOUR_NEWS_API_KEY_HERE', $apiKey, $configContent);
                            file_put_contents('config/config.php', $configContent);
                            
                            echo '<div class="alert alert-success">API key saved successfully!</div>';
                        } else {
                            echo '<div class="alert alert-info">No API key provided. The application will use mock data.</div>';
                        }
                        
                        echo '<a href="?step=4" class="btn btn-primary">Continue to Final Setup</a>';
                    } else:
                    ?>
                    <p>To get real news data, you need an API key from NewsAPI.org</p>
                    <ol>
                        <li>Visit <a href="https://newsapi.org/" target="_blank">NewsAPI.org</a></li>
                        <li>Sign up for a free account</li>
                        <li>Get your API key from the dashboard</li>
                        <li>Enter it below</li>
                    </ol>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="api_key" class="form-label">News API Key (Optional)</label>
                            <input type="text" class="form-control" id="api_key" name="api_key" placeholder="Enter your API key or leave blank for mock data">
                            <div class="form-text">Leave blank to use mock data for development/testing</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save & Continue</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($step == 4): ?>
            <!-- Step 4: Complete -->
            <div class="card">
                <div class="card-header">
                    <h3>Step 4: Installation Complete!</h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4>🎉 Congratulations!</h4>
                    <p>Your News Aggregator application has been successfully installed and configured.</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Next Steps:</h6>
                                    <ul class="list-unstyled">
                                        <li>✅ Import database schema</li>
                                        <li>✅ Configure application settings</li>
                                        <li>✅ Set up API connection</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Quick Links:</h6>
                                    <div class="d-grid gap-2">
                                        <a href="index.html" class="btn btn-success">Launch Application</a>
                                        <a href="test.php" class="btn btn-outline-primary">Run System Test</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong>Important:</strong> Don't forget to import the database schema using the SQL files in the <code>database/</code> directory!
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <small class="text-muted">
                News Aggregator v1.0 | 
                <a href="README.md">Documentation</a> | 
                <a href="test.php">System Test</a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
