/**
 * Global Theme System for CCSearch
 * Handles dark/light mode switching across all pages
 */

class ThemeManager {
    constructor() {
        this.currentTheme = 'light';
        this.init();
    }

    async init() {
        // Load saved theme preference
        await this.loadThemePreference();

        // Apply theme on page load
        this.applyTheme(this.currentTheme);

        // Update logo for initial theme
        this.updateLogo(this.currentTheme);

        // Listen for theme changes from other components
        document.addEventListener('themeChanged', (e) => {
            this.applyTheme(e.detail.theme);
        });
    }

    applyTheme(theme) {
        // Remove existing theme classes
        document.body.className = document.body.className.replace(/dark-theme|light-theme/g, '');

        // Apply new theme
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.add('light-theme');
        }

        // Update logo based on theme
        this.updateLogo(theme);

        this.currentTheme = theme;
        this.saveThemePreference(theme);

        // Dispatch event for other components
        document.dispatchEvent(new CustomEvent('themeApplied', {
            detail: { theme: theme }
        }));
    }

    updateLogo(theme) {
        const logo = document.getElementById('main-logo');
        if (logo) {
            if (theme === 'dark') {
                logo.src = '../image/darkmode_logo.png';
            } else {
                logo.src = '../icons/sidebar-icons/Icon.png';
            }
        }
    }

    getAccountActionsPath() {
        // Determine the correct path to account_actions.php based on current location
        const pathname = window.location.pathname;
        if (pathname.includes('/profile/')) {
            // We're already in the profile directory, use relative path
            return 'account_actions.php';
        } else if (pathname.includes('/notification/') || pathname.includes('/home/') || pathname.includes('/library/') || pathname.includes('/authors/') || pathname.includes('/publication/')) {
            // We're in a subdirectory, go up one level then to profile
            return '../profile/account_actions.php';
        } else {
            // We're in the root or another directory, use profile/account_actions.php
            return 'profile/account_actions.php';
        }
    }

    async loadThemePreference() {
        // Try localStorage first
        const localTheme = localStorage.getItem('userTheme');
        if (localTheme) {
            this.currentTheme = localTheme;
            return;
        }

        // Try to load from database if user is logged in
        try {
            const accountActionsPath = this.getAccountActionsPath();
            const response = await fetch(accountActionsPath + '?action=get_theme', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.status === 'success' && data.theme) {
                    this.currentTheme = data.theme;
                    localStorage.setItem('userTheme', data.theme);
                    return;
                }
            }
        } catch (error) {
            console.warn('Could not load theme from database:', error);
        }

        // Default to light theme
        this.currentTheme = 'light';
    }

    saveThemePreference(theme) {
        localStorage.setItem('userTheme', theme);

        // Also save to database if user is logged in
        this.saveToDatabase(theme);
    }

    async saveToDatabase(theme) {
        try {
            const accountActionsPath = this.getAccountActionsPath();
            const response = await fetch(accountActionsPath + '?action=save_theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: theme })
            });

            if (!response.ok) {
                console.warn('Failed to save theme preference to database');
            }
        } catch (error) {
            console.warn('Error saving theme to database:', error);
        }
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
        return newTheme;
    }

    getCurrentTheme() {
        return this.currentTheme;
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', async function () {
    window.themeManager = new ThemeManager();
    await window.themeManager.init();
});

// Global theme toggle function for easy access
function toggleTheme() {
    if (window.themeManager) {
        return window.themeManager.toggleTheme();
    }
    return null;
}

// Apply theme function for external use
function applyTheme(theme) {
    if (window.themeManager) {
        window.themeManager.applyTheme(theme);
    }
}
