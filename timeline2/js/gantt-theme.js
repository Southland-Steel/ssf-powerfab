/**
 * File: js/gantt-theme.js
 * Gantt Chart Theme Module
 * Handles theme switching functionality
 */
GanttChart.Theme = (function() {
    'use strict';

    // Theme options
    const THEMES = {
        LIGHT: 'light',
        DARK: 'dark'
    };

    // Default theme
    const DEFAULT_THEME = THEMES.DARK;

    // Local storage key
    const STORAGE_KEY = 'gantt-chart-theme';

    /**
     * Initialize theme functionality
     */
    function initialize() {
        // Set up theme switch button
        $('#themeSwitch').on('change', function() {
            const isDarkMode = $(this).is(':checked');
            setTheme(isDarkMode ? THEMES.DARK : THEMES.LIGHT);
        });

        // Load saved theme or use default
        loadSavedTheme();
    }

    /**
     * Load saved theme from local storage or use default
     */
    function loadSavedTheme() {
        try {
            // Get saved theme
            const savedTheme = localStorage.getItem(STORAGE_KEY);

            // Use saved theme or default
            const themeToUse = savedTheme || DEFAULT_THEME;

            // Apply theme
            setTheme(themeToUse, false); // Don't save again

            // Update switch position
            $('#themeSwitch').prop('checked', themeToUse === THEMES.DARK);
        } catch (error) {
            console.error('Error loading saved theme:', error);
            // Apply default theme
            setTheme(DEFAULT_THEME, false);
        }
    }

    /**
     * Set theme
     * @param {string} theme - Theme name ('light' or 'dark')
     * @param {boolean} save - Whether to save the preference (default: true)
     */
    function setTheme(theme, save = true) {
        // Validate theme
        if (theme !== THEMES.LIGHT && theme !== THEMES.DARK) {
            console.error('Invalid theme:', theme);
            return;
        }

        // Apply theme by setting data attribute on html element
        document.documentElement.setAttribute('data-theme', theme);

        // Update switch state to match
        $('#themeSwitch').prop('checked', theme === THEMES.DARK);

        // Update icon visibility
        if (theme === THEMES.DARK) {
            $('.theme-icon-dark').show();
            $('.theme-icon-light').hide();
        } else {
            $('.theme-icon-dark').hide();
            $('.theme-icon-light').show();
        }

        // Save preference if requested
        if (save) {
            try {
                localStorage.setItem(STORAGE_KEY, theme);
            } catch (error) {
                console.error('Error saving theme preference:', error);
            }
        }
    }

    /**
     * Get current theme
     * @return {string} Current theme
     */
    function getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || DEFAULT_THEME;
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = getCurrentTheme();
        const newTheme = currentTheme === THEMES.LIGHT ? THEMES.DARK : THEMES.LIGHT;
        setTheme(newTheme);
    }

    // Public API
    return {
        init: initialize,
        setTheme: setTheme,
        getCurrentTheme: getCurrentTheme,
        toggleTheme: toggleTheme,
        THEMES: THEMES
    };
})();