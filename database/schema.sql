-- News Aggregator Database Schema
-- Run this script to set up the database structure

-- Create database
CREATE DATABASE IF NOT EXISTS news_aggregator;
USE news_aggregator;

-- Users table for storing user accounts
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User preferences table for storing preferred categories
CREATE TABLE IF NOT EXISTS preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category (user_id, category)
);

-- Saved articles table for users to save articles
CREATE TABLE IF NOT EXISTS saved_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    url VARCHAR(1000) NOT NULL,
    image_url VARCHAR(1000),
    source VARCHAR(100),
    published_at DATETIME,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Search history table to track user searches
CREATE TABLE IF NOT EXISTS search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    search_query VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_search (user_id, searched_at),
    INDEX idx_search_query (search_query)
);

-- API usage tracking table
CREATE TABLE IF NOT EXISTS api_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(100) NOT NULL,
    request_params TEXT,
    response_status INT,
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT,
    INDEX idx_endpoint_time (endpoint, request_time)
);

-- Create indexes for better performance
CREATE INDEX idx_preferences_user ON preferences(user_id);
CREATE INDEX idx_saved_articles_user ON saved_articles(user_id);
CREATE INDEX idx_saved_articles_date ON saved_articles(saved_at);

-- Create a view for user statistics
CREATE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.created_at,
    COUNT(DISTINCT p.category) as preferred_categories,
    COUNT(DISTINCT sa.id) as saved_articles,
    COUNT(DISTINCT sh.id) as total_searches
FROM users u
LEFT JOIN preferences p ON u.id = p.user_id
LEFT JOIN saved_articles sa ON u.id = sa.user_id
LEFT JOIN search_history sh ON u.id = sh.user_id
GROUP BY u.id, u.username, u.email, u.created_at;
