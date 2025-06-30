<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Package Grid</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .badge {
            margin: 2px;
            cursor: pointer;
        }
        .grid-cell {
            min-width: 100px;
            height: 40px;
            border: 1px solid #dee2e6;
            padding: 2px;
        }
        .badge {
            margin: 2px;
            cursor: pointer;
        }
        table {
            table-layout: fixed;
            width: 100%;
        }
        thead{
            position: sticky;
            top: -2px;
            background-color: white;
            z-index: 1;
        }
        th:first-child,
        td:first-child {
            width: 120px; /* Adjust this value as needed */
        }
        th:not(:first-child),
        td:not(:first-child) {
            width: calc((100% - 120px) / (var(--num-columns) - 1));

        }
        th:not(:first-child){
            text-align: center;
        }
        th .small{
            font-size: 0.7em;
        }
        .proj-reviewed{
            background-color: saddlebrown !important;
        }
        /* Workshop selector styles */
        .workshop-selector {
            display: inline-block;
            margin-left: 20px;
        }
        .workshop-btn {
            padding: 5px 15px;
            margin: 0 5px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            color: #333;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: normal;
        }
        .workshop-btn:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }
        .workshop-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
            font-weight: bold;
        }
        /* Greensburg workshop styles */
        .workshop-greensburg h1 {
            color: #28a745; /* Green color for Greensburg */
        }
        .workshop-greensburg thead {
            background-color: #d4edda; /* Light green background for table header */
        }
        .badge.bg-primary{
            background-color: #99332b !important;
        }
    </style>
</head>
<body class="">
<div class="container-fluid">
    <h1 class="mb-4">
        Work Package Grid
        <div class="workshop-selector">
            <button class="workshop-btn active" data-workshop="1">Greensburg</button>
        </div>
    </h1>
    <p>These are the workpackages and their assigned workweek for each job.</p>
    <div id="grid-container"></div>
    <h2 class="mt-5 mb-4">Unassigned Work Packages</h2>
    <div id="unassigned-container"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        let currentWorkshopId = 1; // Default to Amite

        // Workshop selector functionality
        $('.workshop-btn').on('click', function() {
            const workshopId = $(this).data('workshop');
            if (workshopId !== currentWorkshopId) {
                currentWorkshopId = workshopId;
                $('.workshop-btn').removeClass('active');
                $(this).addClass('active');

                // Update body class for styling
                if (workshopId == 2) {
                    $('body').addClass('workshop-greensburg');
                } else {
                    $('body').removeClass('workshop-greensburg');
                }

                // Reload data with new workshop
                loadWorkPackageData();
            }
        });

        function getDateRangeOfWeek(weekStr) {
            if (typeof weekStr !== 'string' || weekStr.length !== 4) {
                return { start: 'Invalid Date', end: 'Invalid Date' };
            }

            const year = 2000 + parseInt(weekStr.substring(0, 2), 10);
            const week = parseInt(weekStr.substring(2), 10);

            if (isNaN(year) || isNaN(week) || year < 2000 || year > 2099 || week < 1 || week > 53) {
                return { start: 'Invalid Date', end: 'Invalid Date' };
            }

            try {
                const jan3 = new Date(year, 0, 3);
                const firstSunday = new Date(jan3);
                firstSunday.setDate(jan3.getDate() - (jan3.getDay() + 7) % 7);

                const weekStart = new Date(firstSunday);
                weekStart.setDate(firstSunday.getDate() + (week - 1) * 7);

                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekStart.getDate() + 6);

                return {
                    start: weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                    end: weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                };
            } catch (error) {
                return { start: 'Invalid Date', end: 'Invalid Date' };
            }
        }

        function loadWorkPackageData() {
            $.ajax({
                url: 'ajax_grid_workpackage_weeks.php',
                type: 'GET',
                dataType: 'json',
                data: { workshopid: currentWorkshopId },
                success: function(data) {

                    document.documentElement.style.setProperty('--num-columns', data.workWeeks.length + 1);

                    const $table = $('<table>').addClass('table table-bordered');
                    const $thead = $('<thead>');
                    const $headerRow = $('<tr>').append($('<th>').text('Job Number'));

                    data.workWeeks.forEach(week => {
                        const dateRange = getDateRangeOfWeek(week.name.toString());
                        const lbsperhour = (Math.round(week.weight / week.hours * 10)/10);
                        const $th = $('<th>')
                            .append($('<div>').text(`Week ${week.name}`))
                            .append($('<div>').addClass('small text-muted').text(`${dateRange.start} - ${dateRange.end}`))
                            .append($('<div>').addClass('small').text(`[${week.hours} hrs]`))
                            .append($('<div>').addClass('small').text(`[${week.weight}#]`))
                            .append($('<div>').addClass('small').text(`[${lbsperhour} lbs/hr]`));
                        $headerRow.append($th);
                    });


                    $thead.append($headerRow);
                    $table.append($thead);

                    const $tbody = $('<tbody>');
                    data.assignedOrder.forEach(jobNumber => {
                        const weeks = data.assigned[jobNumber];
                        const $row = $('<tr>').append($('<td>').text(jobNumber));

                        data.workWeeks.forEach(week => {
                            const $cell = $('<td>').addClass('grid-cell');

                            if (weeks[week.name]) {
                                weeks[week.name].forEach(wp => {
                                    const tooltipContent = `
                                        Description: ${wp.description}<br>
                                        Released to Fab: ${wp.releasedToFab}<br>
                                        On Hold: ${wp.onHold}<br>
                                        Weight: ${wp.weight}<br>
                                        Hours: ${wp.hours}
                                        ${wp.notes ? `<br>Notes: ${wp.notes}` : ''}
                                    `;
                                    const reviewstatus = (wp.reviewstatus == 'REVIEWED') ? 'proj-reviewed' : '';
                                    let badgeClass = 'bg-primary';
                                    if (wp.priority === 20) badgeClass = 'bg-danger';
                                    if (wp.priority === 10) badgeClass = 'bg-warning';
                                    const $badge = $('<span>')
                                        .addClass(`badge ${badgeClass} ${reviewstatus}`)
                                        .attr({
                                            'data-bs-toggle': 'tooltip',
                                            'data-bs-html': 'true',
                                            'title': tooltipContent
                                        })
                                        .text(wp.workPackageNumber);

                                    $cell.append($badge);
                                });
                            }

                            $row.append($cell);
                        });

                        $tbody.append($row);
                    });

                    $table.append($tbody);
                    $('#grid-container').empty().append($table);

                    // Render unassigned work packages
                    let unassignedHtml = '<ul class="list-group">';
                    Object.entries(data.unassigned).forEach(([jobNumber, workPackages]) => {
                        unassignedHtml += `<li class="list-group-item"><strong>Job ${jobNumber}:</strong> `;
                        workPackages.forEach(wp => {
                            const tooltipContent = `
                                Description: ${wp.description}<br>
                                Released to Fab: ${wp.releasedToFab}<br>
                                On Hold: ${wp.onHold}<br>
                                Weight: ${wp.weight}<br>
                                Hours: ${wp.hours}
                                ${wp.notes ? `<br>Notes: ${wp.notes}` : ''}
                            `;
                            let badgeClass = 'bg-secondary';
                            if (wp.priority === 20) badgeClass = 'bg-danger';
                            if (wp.priority === 10) badgeClass = 'bg-warning';
                            unassignedHtml += `<span class="badge ${badgeClass}"
                                                         data-bs-toggle="tooltip"
                                                         data-bs-html="true"
                                                         title="${tooltipContent}">
                                                     ${wp.workPackageNumber}
                                                 </span>`;
                        });
                        unassignedHtml += '</li>';
                    });
                    unassignedHtml += '</ul>';
                    $('#unassigned-container').html(unassignedHtml);

                    // Initialize tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl)
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                    $('#grid-container').html('<p class="text-danger">Error loading data. Please try again later.</p>');
                }
            });
        }

        // Load initial data
        loadWorkPackageData();
    });
</script>
</body>
</html>