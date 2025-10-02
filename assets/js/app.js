// News Aggregator JavaScript Application

class NewsAggregator {
    constructor() {
        this.currentCategory = 'general';
        this.currentQuery = '';
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadNews('general');
        this.loadPreferences();
    }

    bindEvents() {
        // Category navigation
        document.querySelectorAll('.category-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const category = e.currentTarget.dataset.category;
                this.selectCategory(category);
            });
        });

        // Search form
        document.getElementById('searchForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                this.searchNews(query);
            }
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', () => {
            if (this.currentQuery) {
                this.searchNews(this.currentQuery);
            } else {
                this.loadNews(this.currentCategory);
            }
        });

        // Preferences
        document.getElementById('preferencesLink').addEventListener('click', (e) => {
            e.preventDefault();
            this.showPreferencesModal();
        });

        document.getElementById('savePreferences').addEventListener('click', () => {
            this.savePreferences();
        });
    }

    selectCategory(category) {
        // Update active category
        document.querySelectorAll('.category-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-category="${category}"]`).classList.add('active');

        this.currentCategory = category;
        this.currentQuery = '';
        document.getElementById('searchInput').value = '';
        this.loadNews(category);
    }

    async loadNews(category) {
        if (this.isLoading) return;

        this.showLoading();
        this.updatePageTitle(this.getCategoryDisplayName(category));

        try {
            const response = await fetch(`api/headlines.php?category=${category}`);
            const data = await response.json();

            if (data.success) {
                this.displayNews(data.articles);
            } else {
                this.showError(data.message || 'Failed to load news');
            }
        } catch (error) {
            console.error('Error loading news:', error);
            this.showError('Failed to connect to the server');
        } finally {
            this.hideLoading();
        }
    }

    async searchNews(query) {
        if (this.isLoading) return;

        this.showLoading();
        this.currentQuery = query;
        this.updatePageTitle(`Search Results for "${query}"`);

        try {
            const response = await fetch(`api/search.php?query=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.success) {
                this.displayNews(data.articles);
            } else {
                this.showError(data.message || 'Search failed');
            }
        } catch (error) {
            console.error('Error searching news:', error);
            this.showError('Failed to connect to the server');
        } finally {
            this.hideLoading();
        }
    }

    displayNews(articles) {
        const container = document.getElementById('newsContainer');
        
        if (!articles || articles.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="no-articles">
                        <i class="fas fa-newspaper"></i>
                        <h4>No articles found</h4>
                        <p>Try searching for something else or check back later.</p>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = articles.map(article => this.createArticleCard(article)).join('');
    }

    createArticleCard(article) {
        const publishedDate = article.publishedAt ? 
            new Date(article.publishedAt).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) : 'Unknown date';

        const imageUrl = article.urlToImage || 'https://via.placeholder.com/400x200?text=No+Image';
        const description = article.description || 'No description available.';
        const source = article.source?.name || 'Unknown Source';

        return `
            <div class="col-md-6 col-lg-4">
                <div class="card news-card">
                    <img src="${imageUrl}" class="card-img-top" alt="News Image" 
                         onerror="this.src='https://via.placeholder.com/400x200?text=No+Image'">
                    <div class="card-body">
                        <h5 class="card-title">${this.escapeHtml(article.title)}</h5>
                        <p class="card-text">${this.escapeHtml(description)}</p>
                        <div class="news-meta">
                            <span class="news-source">${this.escapeHtml(source)}</span>
                            <span class="news-date">${publishedDate}</span>
                        </div>
                        <a href="${article.url}" target="_blank" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-external-link-alt me-1"></i>Read More
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    showLoading() {
        this.isLoading = true;
        document.getElementById('loadingSpinner').classList.remove('d-none');
        document.getElementById('newsContainer').style.opacity = '0.5';
        
        const refreshBtn = document.getElementById('refreshBtn');
        refreshBtn.classList.add('loading');
        refreshBtn.disabled = true;
    }

    hideLoading() {
        this.isLoading = false;
        document.getElementById('loadingSpinner').classList.add('d-none');
        document.getElementById('newsContainer').style.opacity = '1';
        
        const refreshBtn = document.getElementById('refreshBtn');
        refreshBtn.classList.remove('loading');
        refreshBtn.disabled = false;
    }

    showError(message) {
        const container = document.getElementById('newsContainer');
        container.innerHTML = `
            <div class="col-12">
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong> ${this.escapeHtml(message)}
                </div>
            </div>
        `;
    }

    updatePageTitle(title) {
        document.getElementById('pageTitle').textContent = title;
    }

    getCategoryDisplayName(category) {
        const categoryNames = {
            'general': 'Latest News',
            'technology': 'Technology',
            'business': 'Business',
            'sports': 'Sports',
            'science': 'Science',
            'health': 'Health',
            'entertainment': 'Entertainment'
        };
        return categoryNames[category] || 'News';
    }

    async loadPreferences() {
        try {
            const response = await fetch('api/preferences.php');
            const data = await response.json();
            
            if (data.success && data.preferences) {
                data.preferences.forEach(category => {
                    const checkbox = document.getElementById(`pref_${category}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        } catch (error) {
            console.error('Error loading preferences:', error);
        }
    }

    showPreferencesModal() {
        const modal = new bootstrap.Modal(document.getElementById('preferencesModal'));
        modal.show();
    }

    async savePreferences() {
        const selectedCategories = [];
        document.querySelectorAll('#preferencesModal input[type="checkbox"]:checked').forEach(checkbox => {
            selectedCategories.push(checkbox.value);
        });

        try {
            const response = await fetch('api/preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ categories: selectedCategories })
            });

            const data = await response.json();
            
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('preferencesModal'));
                modal.hide();
                
                // Show success message (you could add a toast notification here)
                console.log('Preferences saved successfully');
            } else {
                alert('Failed to save preferences: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving preferences:', error);
            alert('Failed to save preferences. Please try again.');
        }
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new NewsAggregator();
});

// Add some utility functions for enhanced UX
document.addEventListener('DOMContentLoaded', () => {
    // Add smooth scrolling to top when clicking logo
    document.querySelector('.navbar-brand').addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('searchInput').focus();
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('searchInput');
            if (searchInput === document.activeElement) {
                searchInput.blur();
            }
        }
    });

    // Add search input placeholder animation
    const searchInput = document.getElementById('searchInput');
    const placeholders = [
        'Search news...',
        'Try "technology"...',
        'Search "climate change"...',
        'Find "sports news"...'
    ];
    let placeholderIndex = 0;

    setInterval(() => {
        if (searchInput !== document.activeElement && !searchInput.value) {
            placeholderIndex = (placeholderIndex + 1) % placeholders.length;
            searchInput.placeholder = placeholders[placeholderIndex];
        }
    }, 3000);
});
