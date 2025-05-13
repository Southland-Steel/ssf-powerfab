/**
 * assets/js/navigation.js
 * Custom JavaScript for navigation menu functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
});

/**
 * Initialize the navigation menu functionality
 */
function initializeNavigation() {
    // Get all dropdown elements
    const dropdowns = document.querySelectorAll('.dropdown');

    // Add click handlers for mobile menu
    dropdowns.forEach(dropdown => {
        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');

        // Handle dropdown toggle on mobile
        if (window.innerWidth < 992) {
            dropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();

                // Check if this dropdown is already open
                const isOpen = dropdown.classList.contains('show');

                // Close any open dropdowns
                dropdowns.forEach(d => d.classList.remove('show'));

                // Toggle this dropdown (open if it was closed, or keep it closed if it was open)
                if (!isOpen) {
                    dropdown.classList.add('show');
                }
            });
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            dropdowns.forEach(dropdown => dropdown.classList.remove('show'));
        }
    });

    // Handle screen resize
    window.addEventListener('resize', function() {
        // Close all dropdowns when resizing
        dropdowns.forEach(dropdown => dropdown.classList.remove('show'));
    });

    // Add hover effect for desktop
    if (window.innerWidth >= 992) {
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('mouseenter', function() {
                this.classList.add('show');
            });

            dropdown.addEventListener('mouseleave', function() {
                this.classList.remove('show');
            });
        });
    }

    // Add aria-expanded attribute for accessibility
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !expanded);
        });
    });

    // Add keyboard navigation support
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Make dropdown items keyboard-navigable
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    dropdownItems.forEach((item, index, items) => {
        item.addEventListener('keydown', function(e) {
            let targetItem;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                targetItem = index < items.length - 1 ? items[index + 1] : items[0];
                targetItem.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                targetItem = index > 0 ? items[index - 1] : items[items.length - 1];
                targetItem.focus();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                const parentDropdown = this.closest('.dropdown');
                parentDropdown.classList.remove('show');
                parentDropdown.querySelector('.dropdown-toggle').focus();
            }
        });
    });
}

/**
 * Highlight the current section in the navigation
 * @param {string} section - The section ID to highlight
 */
function highlightNavSection(section) {
    // Remove active class from all links
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        link.classList.remove('active');
    });

    // Add active class to the specified section
    const sectionLink = document.querySelector(`#${section}Link`);
    if (sectionLink) {
        sectionLink.classList.add('active');

        // If it's in a dropdown, also highlight the dropdown
        const parentDropdown = sectionLink.closest('.dropdown');
        if (parentDropdown) {
            const dropdownToggle = parentDropdown.querySelector('.dropdown-toggle');
            if (dropdownToggle) {
                dropdownToggle.classList.add('active');
            }
        }
    }
}