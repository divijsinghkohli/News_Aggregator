-- News Aggregator Seed Data
-- Run this after schema.sql to populate with test data

USE news_aggregator;

-- Insert test users (passwords are hashed for 'password123')
INSERT INTO users (username, email, password_hash) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('demo_user', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert user preferences
INSERT INTO preferences (user_id, category) VALUES
(1, 'technology'),
(1, 'business'),
(1, 'science'),
(2, 'sports'),
(2, 'entertainment'),
(2, 'health'),
(3, 'general'),
(3, 'technology'),
(3, 'business'),
(3, 'sports');

-- Insert some sample saved articles
INSERT INTO saved_articles (user_id, title, description, url, image_url, source, published_at) VALUES
(1, 'Latest Tech Innovations in 2024', 'Exploring the cutting-edge technologies that are shaping our future.', 'https://example.com/tech-innovations', 'https://via.placeholder.com/400x200', 'Tech News', '2024-01-15 10:30:00'),
(1, 'AI Revolution in Healthcare', 'How artificial intelligence is transforming medical diagnosis and treatment.', 'https://example.com/ai-healthcare', 'https://via.placeholder.com/400x200', 'Health Tech', '2024-01-14 14:20:00'),
(2, 'Olympic Games Highlights', 'Best moments from the recent Olympic games and athlete performances.', 'https://example.com/olympics', 'https://via.placeholder.com/400x200', 'Sports Daily', '2024-01-13 09:15:00'),
(2, 'Movie Industry Trends', 'Analysis of current trends in the entertainment industry.', 'https://example.com/movie-trends', 'https://via.placeholder.com/400x200', 'Entertainment Weekly', '2024-01-12 16:45:00'),
(3, 'Global Economic Outlook', 'Expert analysis on the current state of the global economy.', 'https://example.com/economy', 'https://via.placeholder.com/400x200', 'Business Times', '2024-01-11 11:30:00');

-- Insert search history
INSERT INTO search_history (user_id, search_query, results_count) VALUES
(1, 'artificial intelligence', 25),
(1, 'blockchain technology', 18),
(1, 'quantum computing', 12),
(2, 'football news', 30),
(2, 'basketball highlights', 22),
(2, 'tennis tournaments', 15),
(3, 'climate change', 35),
(3, 'renewable energy', 28),
(3, 'space exploration', 20),
(NULL, 'covid updates', 45),
(NULL, 'stock market', 33);

-- Insert API usage tracking data
INSERT INTO api_usage (endpoint, request_params, response_status, execution_time_ms) VALUES
('/api/headlines.php', '{"category":"technology"}', 200, 150),
('/api/headlines.php', '{"category":"sports"}', 200, 120),
('/api/headlines.php', '{"category":"business"}', 200, 180),
('/api/search.php', '{"query":"artificial intelligence"}', 200, 250),
('/api/search.php', '{"query":"climate change"}', 200, 200),
('/api/preferences.php', '{"method":"GET"}', 200, 50),
('/api/preferences.php', '{"method":"POST"}', 200, 75);

-- Create a default admin user for testing
INSERT INTO users (username, email, password_hash) VALUES
('admin', 'admin@newsaggregator.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Set admin preferences to all categories
INSERT INTO preferences (user_id, category) VALUES
(4, 'general'),
(4, 'technology'),
(4, 'business'),
(4, 'sports'),
(4, 'science'),
(4, 'health'),
(4, 'entertainment');

-- Add some recent search history for better testing
INSERT INTO search_history (user_id, search_query, results_count, searched_at) VALUES
(1, 'machine learning', 28, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 'world cup', 42, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(3, 'electric vehicles', 31, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, 'cryptocurrency', 19, DATE_SUB(NOW(), INTERVAL 4 HOUR));

-- Display summary of inserted data
SELECT 'Database seeded successfully!' as status;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_preferences FROM preferences;
SELECT COUNT(*) as total_saved_articles FROM saved_articles;
SELECT COUNT(*) as total_searches FROM search_history;
