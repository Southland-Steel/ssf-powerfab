<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Scheduler</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --light-blue: #e7f1ff;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: white;
            border-bottom: 1px solid #dee2e6;
        }

        .week-selector {
            overflow-x: auto;
            white-space: nowrap;
            padding: 1rem 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .week-selector::-webkit-scrollbar {
            display: none;
        }

        .week-btn {
            min-width: 80px;
            margin: 0 4px;
            transition: all 0.2s;
        }

        .week-btn.active {
            background-color: var(--primary-blue);
            color: white;
            transform: scale(1.05);
        }

        .stats-card {
            transition: all 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .progress {
            height: 8px;
        }

        .station-timeline {
            position: relative;
            padding: 1rem;
            border-radius: 8px;
            background: var(--light-blue);
            margin-bottom: 1rem;
        }

        .station-name {
            font-weight: 600;
            color: var(--primary-blue);
        }

        .table-container {
            overflow-x: auto;
        }

        .fixed-week-display {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1001;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="fixed-week-display">
    Current Week: <span class="current-week">2447</span>
</div>

<div class="container-fluid">
    <div class="sticky-header">
        <div class="row align-items-center py-3">
            <div class="col">
                <img src="/api/placeholder/150/50" alt="Grid Structures Logo" class="img-fluid" style="max-height: 40px;">
            </div>
            <div class="col text-end">
                <button class="btn btn-outline-secondary btn-sm" id="toggleFilters">
                    Additional Filters
                </button>
            </div>
        </div>

        <div class="week-selector">
            <div class="d-flex">
                <button class="btn btn-outline-primary week-btn" onclick="prevWeek()">←</button>
                <div class="weeks-container d-flex">
                </div>
                <button class="btn btn-outline-primary week-btn" onclick="nextWeek()">→</button>
            </div>
        </div>

        <div class="row g-3 py-3">
            <div class="col-md-4">
                <div class="stats-card card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Hours</h6>
                        <h4 class="card-title mb-3">1,611 Total Hours</h4>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: 56.81%"></div>
                        </div>
                        <small class="text-muted">
                            915 Complete (56.81%) | 696 Remaining
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Weight</h6>
                        <h4 class="card-title mb-3">200,784 lbs (100 tons)</h4>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: 3.98%"></div>
                        </div>
                        <small class="text-muted">
                            7,990 lbs Complete (3.98%)
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Station Progress</h6>
                        <div class="station-timeline">
                            <div class="station-name">CNC → Week 2451</div>
                            <div class="station-name">Cut → Week 2450</div>
                            <div class="station-name">Kit & Press → Week 2449</div>
                            <div class="station-name">Seam Welding → Week 2448</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="additionalFilters" class="row py-3" style="display: none;">
        <div class="col">
            <div class="btn-group" role="group">
                <button class="btn btn-outline-secondary active">All Bays</button>
                <button class="btn btn-outline-secondary">8</button>
                <button class="btn btn-outline-secondary">5</button>
            </div>
        </div>
        <div class="col">
            <div class="btn-group" role="group">
                <button class="btn btn-outline-secondary active">All Routes</button>
                <button class="btn btn-outline-secondary">Shaft</button>
                <button class="btn btn-outline-secondary">Ship Loose</button>
                <button class="btn btn-outline-secondary">Structural</button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-hover">
            <thead class="table-light">
            <tr>
                <th>Job Route</th>
                <th>Seq/Lot Main</th>
                <th>WP</th>
                <th>Asm. Qty</th>
                <th>Net # Each / Total</th>
                <th>Hrs. Each / Total</th>
                <th>CNC</th>
                <th>Cut</th>
                <th>Kit-Up</th>
                <th>Traps Cut</th>
                <th>Press Break</th>
                <th>Seam Welder</th>
                <th>Fit</th>
                <th>Weld</th>
                <th>Final QC</th>
            </tr>
            </thead>
            <tbody>
            <!-- Table content will be populated dynamically -->
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        // Populate week buttons
        const weeks = ['2446', '2447', '2448', '2449', '2450', '2451', '2452',
            '2501', '2502', '2503', '2504', '2505', '2506', '2507',
            '2508', '2509', '2510'];

        const weeksContainer = $('.weeks-container');
        weeks.forEach(week => {
            weeksContainer.append(`
                    <button class="btn btn-outline-primary week-btn ${week === '2447' ? 'active' : ''}"
                            onclick="selectWeek('${week}')">${week}</button>
                `);
        });

        // Toggle filters
        $('#toggleFilters').click(function() {
            $('#additionalFilters').slideToggle();
        });
    });

    function selectWeek(week) {
        $('.week-btn').removeClass('active');
        $(`.week-btn:contains(${week})`).addClass('active');
        $('.current-week').text(week);
        // Additional logic for loading week data would go here
    }

    function scrollWeeks(direction) {
        const container = $('.week-selector');
        const scrollAmount = 300;
        container.animate({
            scrollLeft: container.scrollLeft() + (direction * scrollAmount)
        }, 300);
    }

    function prevWeek() {
        scrollWeeks(-1);
    }

    function nextWeek() {
        scrollWeeks(1);
    }
</script>
</body>
</html>