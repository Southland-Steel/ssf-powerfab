<!DOCTYPE html>
<html>
<head>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-container {
            position: relative;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }

        .scroll-container {
            position: relative;
            overflow: hidden;
        }

        .nav-pills-wrapper {
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
            scroll-behavior: smooth;
            padding: 0.5rem 0;
        }

        /* Hide scrollbar for Chrome/Safari */
        .nav-pills-wrapper::-webkit-scrollbar {
            display: none;
        }

        .nav-pills {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.5rem;
            padding: 0 1.5rem;
        }

        .nav-pills .nav-link {
            white-space: nowrap;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #dee2e6;
            color: #495057;
        }

        .nav-pills .nav-link.active {
            background: #0d6efd;
            border-color: #0d6efd;
        }

        .scroll-indicator {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(90deg, rgba(248,249,250,0.9) 0%, rgba(248,249,250,0.9) 100%);
            z-index: 1;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .scroll-indicator.active {
            opacity: 1;
        }

        .scroll-left {
            left: 0;
        }

        .scroll-right {
            right: 0;
            transform: rotate(180deg);
        }

        .scroll-indicator svg {
            width: 1.5rem;
            height: 1.5rem;
            fill: #495057;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="nav-container">
        <div class="scroll-container">
            <!-- Scroll indicators -->
            <div class="scroll-indicator scroll-left">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            </div>
            <div class="scroll-indicator scroll-right">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            </div>

            <!-- Pills navigation -->
            <div class="nav-pills-wrapper" id="pillsWrapper">
                <ul class="nav nav-pills" id="packagePills" role="tablist">
                    <!-- Pills will be dynamically added here -->
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    // Sample data structure for different workweeks
    const workweekData = {
        'week1': [
            { id: 'wp1', name: 'Infrastructure Setup' },
            { id: 'wp2', name: 'Data Migration' },
            { id: 'wp3', name: 'UI Development' },
            { id: 'wp4', name: 'Backend Integration' },
            { id: 'wp5', name: 'Security Implementation' },
            { id: 'wp6', name: 'Testing Phase' },
            { id: 'wp7', name: 'Documentation' },
            { id: 'wp8', name: 'Deployment Prep' },
            { id: 'wp9', name: 'Quality Assurance' },
            { id: 'wp10', name: 'User Training' }
        ]
        // Add more weeks as needed
    };

    class PillsNavigation {
        constructor() {
            this.wrapper = document.querySelector('.nav-pills-wrapper');
            this.container = document.querySelector('.nav-pills');
            this.leftIndicator = document.querySelector('.scroll-left');
            this.rightIndicator = document.querySelector('.scroll-right');

            this.setupEventListeners();
            this.loadState();
            this.updateScrollIndicators();
        }

        setupEventListeners() {
            // Scroll indicators click handlers
            this.leftIndicator.addEventListener('click', () => {
                this.wrapper.scrollBy({ left: -200, behavior: 'smooth' });
            });

            this.rightIndicator.addEventListener('click', () => {
                this.wrapper.scrollBy({ left: 200, behavior: 'smooth' });
            });

            // Scroll event handler
            this.wrapper.addEventListener('scroll', () => {
                this.updateScrollIndicators();
            });

            // Handle pill clicks
            this.container.addEventListener('click', (e) => {
                const pill = e.target.closest('.nav-link');
                if (pill) {
                    this.setActivePill(pill.id);
                }
            });
        }

        updateScrollIndicators() {
            const { scrollLeft, scrollWidth, clientWidth } = this.wrapper;

            // Show/hide left indicator
            this.leftIndicator.classList.toggle('active', scrollLeft > 0);

            // Show/hide right indicator
            this.rightIndicator.classList.toggle(
                'active',
                Math.ceil(scrollLeft + clientWidth) < scrollWidth
            );
        }

        loadState() {
            // Get saved state from localStorage
            const activeId = localStorage.getItem('activePillId') || 'wp1';
            this.setActivePill(activeId, true);
        }

        setActivePill(pillId, isInitial = false) {
            // Remove active class from all pills
            this.container.querySelectorAll('.nav-link').forEach(pill => {
                pill.classList.remove('active');
            });

            // Add active class to selected pill
            const activePill = document.getElementById(pillId);
            if (activePill) {
                activePill.classList.add('active');
                localStorage.setItem('activePillId', pillId);

                if (!isInitial) {
                    // Scroll pill into view if not initial load
                    this.scrollPillIntoView(activePill);
                }
            }
        }

        scrollPillIntoView(pill) {
            const pillRect = pill.getBoundingClientRect();
            const containerRect = this.wrapper.getBoundingClientRect();

            if (pillRect.left < containerRect.left || pillRect.right > containerRect.right) {
                pill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }

        renderPills(weekId) {
            const pills = workweekData[weekId];
            this.container.innerHTML = pills.map(pill => `
                    <li class="nav-item">
                        <button class="nav-link" id="${pill.id}" role="tab">
                            ${pill.name}
                        </button>
                    </li>
                `).join('');

            this.loadState();
            this.updateScrollIndicators();
        }
    }

    // Initialize navigation
    const navigation = new PillsNavigation();
    navigation.renderPills('week1');
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>