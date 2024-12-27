window.addEventListener('DOMContentLoaded', () => {
    // Check if URL contains '/dev/'
    if (window.location.pathname.includes('/dev/')) {
        // Create banner element
        const banner = document.createElement('div');
        banner.style.position = 'fixed';
        banner.style.bottom = '0';
        banner.style.left = '0';
        banner.style.right = '0';
        banner.style.backgroundColor = '#ff4444';
        banner.style.color = 'white';
        banner.style.padding = '8px';
        banner.style.textAlign = 'center';
        banner.style.fontWeight = 'bold';
        banner.style.zIndex = '9999';
        banner.textContent = '⚠️ DEVELOPMENT ENVIRONMENT ⚠️';

        // Add banner to page
        document.body.prepend(banner);

        // Adjust body padding to prevent banner overlap
        document.body.style.paddingTop =
            (parseInt(getComputedStyle(banner).height) + 16) + 'px';

        // Optional: Also change favicon
        const favicon = document.createElement('link');
        favicon.rel = 'icon';
        favicon.href = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><text y=".9em" font-size="90">🛠️</text></svg>';
        document.head.appendChild(favicon);
    }
});