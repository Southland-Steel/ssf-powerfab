/**
 * workweeks/js/workweeks-utils.js
 * Utility functions for the workweeks module
 */

/**
 * Format a number with 2 decimal places
 * @param {number} value - The number to format
 * @returns {string} Formatted number with 2 decimal places
 */
function formatNumber(value) {
    value = parseFloat(value);
    return isNaN(value) ? "0.00" : value.toFixed(2);
}

/**
 * Format a number with commas for thousands
 * @param {number} number - The number to format
 * @returns {string} Formatted number with commas (e.g. 1,234,567)
 */
function formatNumberWithCommas(number) {
    if (isNaN(number) || number === null || number === undefined) return "0";
    return Number(parseFloat(number).toFixed(0)).toLocaleString();
}

/**
 * Safely divides two numbers, avoiding division by zero
 * @param {number} numerator - The numerator
 * @param {number} denominator - The denominator
 * @returns {number} The result of the division, or 0 if denominator is 0
 */
function safeDivide(numerator, denominator) {
    if (!denominator || isNaN(denominator)) return 0;
    const result = numerator / denominator;
    return isNaN(result) ? 0 : result;
}

/**
 * Shows a loading overlay with a message
 * @param {string} message - The message to display in the loading overlay
 */
function showLoading(message) {
    // Remove any existing loading overlay
    hideLoading();

    // Create a new loading overlay
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-primary me-2" role="status"></div>
                <span>${message}</span>
            </div>
        </div>`;
    document.body.appendChild(overlay);
}

/**
 * Update the loading message
 * @param {string} message - The new message to display
 */
function updateLoadingMessage(message) {
    const spinner = document.querySelector('.loading-spinner');
    if (spinner) {
        spinner.querySelector('span').textContent = message;
    }
}

/**
 * Hide the loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.parentNode.removeChild(overlay);
    }
}

/**
 * Create a lookup map for quick access to items by a key
 * @param {Array} items - Array of items to index
 * @param {string} keyField - The field to use as the key
 * @returns {Map} A map with the key field as keys and arrays of matching items as values
 */
function createLookupMap(items, keyField = 'ProductionControlItemSequenceID') {
    const map = new Map();

    items.forEach(item => {
        if (!item || !item[keyField]) {
            console.warn(`Found invalid item in createLookupMap (missing ${keyField}):`, item);
            return;
        }

        const key = item[keyField];
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(item);
    });

    return map;
}

/**
 * Generate a unique identifier
 * @returns {string} A unique ID string
 */
function generateUniqueId() {
    return 'id_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Check if the browser is in dark mode
 * @returns {boolean} True if the browser is in dark mode
 */
function isDarkMode() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

/**
 * Convert inches to feet and inches format
 * @param {number} inches - The length in inches
 * @returns {string} Formatted string (e.g., "10'-6"")
 */
function inchesToFeetAndInches(inches) {
    if (inches === null || inches === undefined || isNaN(inches)) {
        return 'N/A';
    }

    const totalInches = parseFloat(inches);
    const feet = Math.floor(totalInches / 12);
    const remainingInches = totalInches % 12;

    if (feet === 0) {
        return `${remainingInches.toFixed(1)}"`;
    } else if (remainingInches === 0) {
        return `${feet}'`;
    } else {
        return `${feet}'-${remainingInches.toFixed(1)}"`;
    }
}

/**
 * Format a date string in a user-friendly format
 * @param {string} dateString - The date string to format
 * @returns {string} Formatted date (e.g., "Jan 15, 2025")
 */
function formatDate(dateString) {
    if (!dateString) return '';

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Extract the query parameters from the URL
 * @returns {Object} An object containing the query parameters
 */
function getQueryParams() {
    const params = {};
    const queryString = window.location.search.substring(1);
    const pairs = queryString.split('&');

    for (let i = 0; i < pairs.length; i++) {
        const pair = pairs[i].split('=');
        params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
    }

    return params;
}

/**
 * Update the URL with new query parameters without reloading the page
 * @param {Object} params - The parameters to set in the URL
 */
function updateUrlParams(params) {
    const url = new URL(window.location);

    // Set each parameter
    Object.keys(params).forEach(key => {
        url.searchParams.set(key, params[key]);
    });

    // Update the URL without reloading the page
    window.history.pushState({}, '', url);
}

/**
 * Debounce a function to prevent excessive calls
 * @param {function} func - The function to debounce
 * @param {number} wait - The debounce delay in milliseconds
 * @returns {function} The debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}