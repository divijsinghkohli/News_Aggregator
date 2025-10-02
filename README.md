# News Aggregator

A modern, full-stack web application that aggregates news from various sources, allowing users to browse headlines by category, search for specific topics, and save their preferences.

## Features

- **Category-based News Browsing**: View headlines organized by Technology, Business, Sports, Science, Health, and Entertainment
- **Advanced Search**: Search for news articles by keywords with sorting options
- **User Preferences**: Save and manage preferred news categories
- **Responsive Design**: Modern, mobile-friendly interface using Bootstrap 5
- **Real-time Updates**: AJAX-powered interface with no page reloads
- **Mock Data Support**: Works with or without News API key for development

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Font Awesome
- **Backend**: PHP 7.4+, MySQL 8.0+
- **API**: News API (newsapi.org)
- **Architecture**: RESTful API design with JSON responses

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 7.4 or higher** with the following extensions:
  - PDO
  - PDO_MySQL
  - JSON
  - cURL (optional, for enhanced API requests)
- **MySQL 8.0 or higher** (or MariaDB 10.3+)
- **Web Server** (Apache, Nginx, or PHP built-in server)
- **News API Key** (optional, but recommended for real data)

## Installation & Setup

### 1. Clone or Download the Project

```bash
# If using Git
git clone <repository-url> news-aggregator
cd news-aggregator

# Or download and extract the ZIP file
```

### 2. Database Setup

#### Create the Database

```bash
# Login to MySQL
mysql -u root -p

# Create database and user (optional)
CREATE DATABASE news_aggregator;
CREATE USER 'news_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON news_aggregator.* TO 'news_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Import Schema and Seed Data

```bash
# Import the database schema
mysql -u root -p news_aggregator < database/schema.sql

# Import seed data (optional, for testing)
mysql -u root -p news_aggregator < database/seed.sql
```

### 3. Configuration

#### Update Database Configuration

Edit `config/config.php` and update the database settings:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_aggregator');
define('DB_USER', 'your_username');    // Update this
define('DB_PASS', 'your_password');    // Update this
```

#### Get News API Key (Recommended)

1. Visit [NewsAPI.org](https://newsapi.org/)
2. Sign up for a free account
3. Get your API key from the dashboard
4. Update `config/config.php`:

```php
// Replace 'YOUR_NEWS_API_KEY_HERE' with your actual API key
define('NEWS_API_KEY', 'your_actual_api_key_here');
```

**Note**: The application works with mock data if no API key is provided, perfect for development and testing.

### 4. File Permissions

Ensure proper permissions for web server access:

```bash
# Make sure web server can read all files
chmod -R 755 /path/to/news-aggregator

# Ensure config file is readable but secure
chmod 644 config/config.php
```

## Running the Application

### Option 1: PHP Built-in Server (Recommended for Development)

```bash
# Navigate to project directory
cd /path/to/news-aggregator

# Start PHP server
php -S localhost:8000

# Open browser and visit
# http://localhost:8000
```

### Option 2: Apache/Nginx

1. Copy project files to your web server document root
2. Configure virtual host (optional)
3. Access via your web server URL

### Option 3: XAMPP/WAMP/MAMP

1. Copy project to `htdocs` (XAMPP) or `www` (WAMP/MAMP)
2. Start Apache and MySQL services
3. Visit `http://localhost/news-aggregator`

## Testing the Application

### 1. Basic Functionality Test

1. **Homepage**: Visit the main page and verify news articles load
2. **Categories**: Click different category links in the sidebar
3. **Search**: Use the search bar to find articles
4. **Preferences**: Click "Preferences" to test the modal

### 2. API Endpoints Testing

You can test the API endpoints directly:

```bash
# Test headlines endpoint
curl "http://localhost:8000/api/headlines.php?category=technology"

# Test search endpoint
curl "http://localhost:8000/api/search.php?query=artificial+intelligence"

# Test preferences endpoint
curl "http://localhost:8000/api/preferences.php"

# Save preferences (POST request)
curl -X POST "http://localhost:8000/api/preferences.php" \
  -H "Content-Type: application/json" \
  -d '{"categories":["technology","business"]}'
```

### 3. Database Verification

```sql
-- Check if tables were created
USE news_aggregator;
SHOW TABLES;

-- Verify seed data
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM preferences;
SELECT COUNT(*) FROM saved_articles;
```

## Project Structure

```
news-aggregator/
├── index.html              # Main application page
├── README.md               # This file
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── app.js          # Frontend JavaScript
├── api/
│   ├── headlines.php       # Headlines API endpoint
│   ├── search.php          # Search API endpoint
│   └── preferences.php     # Preferences API endpoint
├── config/
│   └── config.php          # Configuration settings
└── database/
    ├── schema.sql          # Database schema
    └── seed.sql            # Sample data
```

## Configuration Options

### Environment Settings

In `config/config.php`, you can customize:

- **Database connection settings**
- **News API configuration**
- **Application debug mode**
- **Rate limiting settings**
- **Default categories and pagination**

### Debug Mode

For development, enable debug mode in `config/config.php`:

```php
define('APP_DEBUG', true);  // Shows detailed error messages
```

For production, set to `false`:

```php
define('APP_DEBUG', false); // Hides sensitive error information
```

## API Documentation

### Headlines Endpoint

**GET** `/api/headlines.php`

Parameters:
- `category` (string): News category (technology, business, sports, etc.)
- `page` (int): Page number (default: 1)
- `pageSize` (int): Articles per page (default: 20, max: 100)

Example:
```
GET /api/headlines.php?category=technology&page=1&pageSize=10
```

### Search Endpoint

**GET** `/api/search.php`

Parameters:
- `query` (string): Search query (required, min 2 characters)
- `page` (int): Page number (default: 1)
- `pageSize` (int): Articles per page (default: 20, max: 100)
- `sortBy` (string): Sort order (relevancy, popularity, publishedAt)

Example:
```
GET /api/search.php?query=artificial+intelligence&sortBy=publishedAt
```

### Preferences Endpoint

**GET** `/api/preferences.php` - Get user preferences
**POST** `/api/preferences.php` - Save user preferences

POST Body:
```json
{
  "categories": ["technology", "business", "sports"]
}
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify MySQL is running
   - Check database credentials in `config/config.php`
   - Ensure database exists and user has proper permissions

2. **API Key Issues**
   - Verify API key is correctly set in `config/config.php`
   - Check News API quota and rate limits
   - Application works with mock data if API key is not configured

3. **Permission Errors**
   - Ensure web server has read access to all files
   - Check file permissions (755 for directories, 644 for files)

4. **JavaScript Errors**
   - Check browser console for errors
   - Ensure all CSS/JS files are loading correctly
   - Verify API endpoints are accessible

### Debug Steps

1. **Enable Debug Mode**: Set `APP_DEBUG = true` in config
2. **Check Error Logs**: Look at PHP error logs and browser console
3. **Test API Endpoints**: Use curl or browser to test API responses
4. **Verify Database**: Check if tables exist and contain data

### Performance Optimization

1. **Enable Caching**: Implement Redis or file-based caching
2. **Database Indexing**: Add indexes for frequently queried columns
3. **API Rate Limiting**: Implement proper rate limiting for production
4. **CDN**: Use CDN for static assets in production

## Future Enhancements

- **User Authentication**: Complete login/registration system
- **Article Bookmarking**: Save articles for later reading
- **Email Notifications**: Daily/weekly news digests
- **Social Sharing**: Share articles on social media
- **Advanced Filtering**: Filter by date, source, sentiment
- **Mobile App**: React Native or Flutter mobile version
- **Admin Dashboard**: Content management and analytics
- **Multiple Languages**: Internationalization support

## License

This project is open source and available under the [MIT License](LICENSE).

##  Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

If you encounter any issues or have questions:

1. Check this README for common solutions
2. Review the troubleshooting section
3. Check the browser console and PHP error logs
4. Create an issue with detailed error information

## Acknowledgments

- [News API](https://newsapi.org/) for providing news data
- [Bootstrap](https://getbootstrap.com/) for responsive UI components
- [Font Awesome](https://fontawesome.com/) for icons
- PHP and MySQL communities for excellent documentation

---
