/**
 * File: js/gantt-custom.js
 * Custom implementation for the specific project data format
 */
GanttChart.Custom = (function() {
    'use strict';

    // Store workpackage data
    let workpackages = [];

    // Store categorization status data
    let categorizationStatus = {};

    /**
     * Initialize custom implementation
     */
    function initialize() {
        // Override the default Ajax loadData method with our custom implementation
        const originalLoadData = GanttChart.Ajax.loadData;
        GanttChart.Ajax.loadData = function(filter) {
            loadProjectData(filter);
        };

        // Override the Items.generate method to use our custom implementation
        const originalGenerate = GanttChart.Items.generate;
        GanttChart.Items.generate = function(items, minDate, maxDate) {
            generateProjectRows(items, minDate, maxDate);
        };

        // Override the Ajax exportToCsv method with our custom implementation
        GanttChart.Ajax.exportToCsv = exportProjectData;
    }

    function updateProjectFilters(sequences) {
        const $dropdown = $('#projectFilterDropdown');

        // Clear existing project filters
        $dropdown.find('li:not(:first-child)').remove();

        // Get unique project numbers
        const projects = [...new Set(sequences.map(seq => seq.project))];

        // Only add divider if there are projects
        if (projects.length > 0) {
            $dropdown.append('<li><hr class="dropdown-divider"></li>');
        }

        // Add filters for each project
        projects.forEach(project => {
            $dropdown.append(`<li><a class="dropdown-item" href="#" data-filter="${project}">${project}</a></li>`);
        });

        // Explicitly bind click events to the newly added dropdown items
        $('.dropdown-item[data-filter]').off('click').on('click', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            console.log('Project filter selected:', filter);

            // Update button text
            $('#filterDropdownBtn').text($(this).text());

            // Update current filter in config
            GanttChart.Core.setConfig({ currentFilter: filter });

            // Load data with the selected filter
            GanttChart.Ajax.loadData(filter);
        });
    }

    /**
     * Load project data from server
     * @param {string} filter - Filter to apply
     */
    function loadProjectData(filter) {
        // Show loading state
        GanttChart.Core.showLoading();

        // Reset the item count badge
        if ($('#itemCountBadge').length) {
            $('#itemCountBadge').text('0');
        }

        // Get endpoints from configuration
        const config = GanttChart.Core.getConfig();
        const mainEndpoint = config.dataEndpoint || 'ajax_ssf_get_timelinefabrication.php';
        const workpackagesEndpoint = config.workpackagesEndpoint || 'ajax_get_ssf_timelinefabrication_workpackages.php';

        // Load main project data and workpackages in parallel
        Promise.all([
            // Fetch main project data
            fetch(mainEndpoint)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                }),

            // Fetch workpackage data
            fetch(workpackagesEndpoint)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
        ])
            .then(([projectData, workpackageData]) => {
                // Store workpackage data
                workpackages = workpackageData;

                // Process and filter data if needed
                const processedData = processProjectData(projectData, filter);

                // Update state with processed data
                GanttChart.Core.setState({
                    items: processedData.sequences,
                    dateRange: processedData.dateRange
                });

                // Update project filters dropdown
                updateProjectFilters(processedData.sequences);

                if (processedData.sequences.length > 0) {
                    // Update the item count badge
                    if ($('#itemCountBadge').length) {
                        updateItemCountBadge(processedData.sequences.length);
                    }

                    // Generate timeline
                    GanttChart.Timeline.generate(
                        processedData.dateRange.start,
                        processedData.dateRange.end
                    );

                    // Generate project rows (custom implementation)
                    generateProjectRows(
                        processedData.sequences,
                        processedData.dateRange.start,
                        processedData.dateRange.end
                    );

                    // Initialize interactions
                    GanttChart.Interactions.init();

                    // Show chart
                    GanttChart.Core.showChart();

                    // Load categorization status data for the rows
                    loadCategorizationStatus(processedData.sequences);
                } else {
                    // Set the item count badge to 0
                    if ($('#itemCountBadge').length) {
                        $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');
                    }

                    // Show no items message
                    GanttChart.Core.showNoItems();
                }
            })
            .catch(error => {
                console.error('Error loading data:', error);

                // Set the item count badge to 0
                if ($('#itemCountBadge').length) {
                    $('#itemCountBadge').text('0').removeClass('bg-success bg-primary').addClass('bg-secondary');
                }

                GanttChart.Core.showNoItems();

                // Show alert
                alert('Error loading data: ' + error.message);
            });
    }

    /**
     * Process project data from server response
     * @param {Object} data - Raw project data
     * @param {string} filter - Filter to apply
     * @return {Object} Processed data
     */
    function processProjectData(data, filter) {
        console.log('Processing data with filter:', filter);

        // If no data or empty sequences, return empty structure
        if (!data || !data.sequences || data.sequences.length === 0) {
            return {
                dateRange: {
                    start: new Date().toISOString().split('T')[0],
                    end: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
                },
                sequences: []
            };
        }

        // Apply filter if needed
        let filteredSequences = data.sequences;

        if (filter && filter !== 'all') {
            console.log('Applying filter:', filter);

            // Check if filter is combined (project:category)
            const combinedFilter = filter.split(':');
            const projectFilter = combinedFilter[0];
            const categoryFilter = combinedFilter.length > 1 ? combinedFilter[1] : null;

            console.log('Parsed filters - Project:', projectFilter, 'Category:', categoryFilter);

            // Filter sequences based on both project and category if available
            filteredSequences = data.sequences.filter(sequence => {
                // First apply project filter if it's not 'all'
                if (projectFilter !== 'all' && projectFilter.match(/^[A-Za-z0-9\-]+$/)) {
                    // Skip sequence if it doesn't match project
                    if (sequence.project !== projectFilter) {
                        return false;
                    }
                }

                // Then apply category filter if it exists
                if (categoryFilter) {
                    switch (categoryFilter) {
                        case 'in-progress':
                            return sequence.fabrication.percentage > 0 && sequence.fabrication.percentage < 100;
                        case 'ready-for-fabrication':
                            return sequence.iff.percentage >= 100;
                        case 'has-workpackage':
                            return sequence.hasWorkPackage === true ||
                                sequence.hasWP === 1 ||
                                hasWorkPackages(sequence);
                        case 'categorize-needed':
                            return sequence.categorize.percentage < 100;
                        case 'all':
                            return true;
                        default:
                            return true;
                    }
                } else {
                    // Single filter that's not a combined one
                    switch (projectFilter) {
                        case 'in-progress':
                            return sequence.fabrication.percentage > 0 && sequence.fabrication.percentage < 100;
                        case 'ready-for-fabrication':
                            return sequence.iff.percentage >= 100;
                        case 'has-workpackage':
                            return sequence.hasWorkPackage === true ||
                                sequence.hasWP === 1 ||
                                hasWorkPackages(sequence);
                        case 'categorize-needed':
                            return sequence.categorize.percentage < 100;
                        default:
                            // This handles the case where there's only a project filter
                            if (projectFilter.match(/^[A-Za-z0-9\-]+$/)) {
                                return sequence.project === projectFilter;
                            }
                            return true;
                    }
                }
            });
        }

        console.log('Filtered sequences count:', filteredSequences.length);

        // Return processed data
        return {
            dateRange: data.dateRange,
            sequences: filteredSequences
        };
    }

    /**
     * Check if a sequence has workpackages
     * @param {Object} sequence - Project sequence
     * @return {boolean} True if sequence has workpackages
     */
    function hasWorkPackages(sequence) {
        return workpackages.some(wp =>
            wp.jobNumber === sequence.project &&
            wp.sequence === sequence.sequence
        );
    }

    /**
     * Update item count badge
     * @param {number} count - Number of items
     */
    function updateItemCountBadge(count) {
        const $badge = $('#itemCountBadge');
        if (!$badge.length) return;

        $badge.text(count);

        // Change badge color based on count
        if (count > 20) {
            $badge.removeClass('bg-secondary bg-success').addClass('bg-primary');
        } else if (count > 0) {
            $badge.removeClass('bg-secondary bg-primary').addClass('bg-success');
        } else {
            $badge.removeClass('bg-success bg-primary').addClass('bg-secondary');
        }
    }

    /**
     * Update project filters dropdown
     * @param {Array} sequences - Project sequences
     */
    function filterItems(filter) {
        console.log("Client-side filtering with: " + filter);
        // Skip if no filter or 'all'
        if (!filter || filter === 'all') {
            $('.gantt-row').show();
            return;
        }

        // Hide all rows first
        $('.gantt-row').hide();

        // Show rows that match the filter
        if (filter === 'in-progress') {
            $('.gantt-row .item-bar.in-progress').closest('.gantt-row').show();
        } else if (filter === 'completed') {
            $('.gantt-row .item-bar.completed').closest('.gantt-row').show();
        } else if (filter === 'not-started') {
            $('.gantt-row .item-bar.not-started').closest('.gantt-row').show();
        } else if (filter === 'overdue') {
            $('.gantt-row .date-conflict-warning.overdue').closest('.gantt-row').show();
        } else if (filter === 'at-risk') {
            $('.gantt-row .date-conflict-warning.at-risk').closest('.gantt-row').show();
        } else if (filter === 'high-priority') {
            $('.gantt-row.priority-high').show();
        } else if (filter.startsWith('status-')) {
            // Filter by status if filter is 'status-value'
            const statusMatch = filter.match(/^status-(.+)$/);
            if (statusMatch) {
                const status = statusMatch[1];
                $(`.gantt-row[data-status="${status}"]`).show();
            }
        }
    }

    /**
     * Generate project rows
     * @param {Array} sequences - Project sequences
     * @param {string} minDate - Start date for timeline
     * @param {string} maxDate - End date for timeline
     */
    function generateProjectRows(sequences, minDate, maxDate) {
        const $itemRowsContainer = $(GanttChart.Core.getConfig().itemRows);
        $itemRowsContainer.empty();

        // Create project rows
        sequences.forEach(function(sequence) {
            const $row = createProjectRow(sequence);
            $itemRowsContainer.append($row);
        });
    }

    /**
     * Create a single project row
     * @param {Object} sequence - Project sequence
     * @return {jQuery} Created row element
     */
    function createProjectRow(sequence) {
        // Determine row classes based on sequence properties
        const hasWP = hasWorkPackages(sequence);
        const rowClasses = `gantt-row project-row${hasWP ? ' has-wp' : ''}`;

        // Get sequence work packages
        const sequenceWPs = workpackages.filter(wp =>
            wp.jobNumber === sequence.project &&
            wp.sequence === sequence.sequence
        );

        // Create row element
        const $row = $('<div></div>')
            .addClass(rowClasses)
            .attr('data-project', sequence.project)
            .attr('data-sequence', sequence.sequence)
            .attr('data-rowid', `${sequence.project}:${sequence.sequence}`);

        // Create labels section
        const $labels = $('<div class="gantt-labels"></div>')
            .attr('data-rowid', `${sequence.project}:${sequence.sequence}`);

        // Add row title with link to details
        $labels.append(`
            <div class="gantt-rowtitle">
                <a href="sequence_status/sequence_status.php?jobNumber=${sequence.project}&sequenceName=${sequence.sequence}" 
                   target="_blank" title="View Sequence Details">
                   ${sequence.project}: ${sequence.sequence}
                </a>
            </div>
            <div class="gantt-pmname">PM: ${sequence.pm}</div>
        `);

        // Append labels to row
        $row.append($labels);

        // Create timeline column
        const $timeline = $('<div class="gantt-timeline"></div>');

        // Add today line
        addTodayLine($timeline);

        // Determine start and end dates
        const startDate = sequence.fabrication.start;
        const endDate = sequence.fabrication.end;

        // Add task bar
        addSequenceBar(sequence, startDate, endDate, $timeline);

        // Add workpackage markers
        if (hasWP && sequenceWPs.length > 0) {
            addWorkPackageMarkers(sequence, sequenceWPs, $timeline);
        }

        // Add IFF and categorize indicators
        addProcessIndicators(sequence, $timeline);

        // Append timeline to row
        $row.append($timeline);

        return $row;
    }

    /**
     * Add today's line to the timeline
     * @param {jQuery} $timeline - Timeline element
     */
    function addTodayLine($timeline) {
        // Get today's date
        const today = GanttChart.Core.getToday();

        // Calculate position using TimeUtils
        const todayPos = GanttChart.TimeUtils.dateToPosition(today);

        if (todayPos >= 0 && todayPos <= 100) {
            // Create a today line
            const $todayLine = $('<div class="current-date-line"></div>')
                .css('left', todayPos + '%')
                .attr('title', 'Today: ' + GanttChart.Core.formatDate(today));

            $timeline.append($todayLine);
        }
    }

    /**
     * Add sequence bar to the timeline
     * @param {Object} sequence - Project sequence
     * @param {string} startDate - Start date for sequence
     * @param {string} endDate - End date for sequence
     * @param {jQuery} $timeline - Timeline element
     */
    function addSequenceBar(sequence, startDate, endDate, $timeline) {
        // Parse dates
        const parsedStartDate = GanttChart.Core.parseDate(startDate);
        const parsedEndDate = GanttChart.Core.parseDate(endDate);

        // Calculate positions using TimeUtils
        const startPos = GanttChart.TimeUtils.dateToPosition(parsedStartDate);
        const endPos = GanttChart.TimeUtils.dateToPosition(parsedEndDate);
        const width = Math.max(endPos - startPos, 3); // Ensure minimum width

        // Determine bar class based on completion percentage
        let barClass = 'item-bar fabrication-bar';
        if (sequence.fabrication.percentage >= 100) {
            barClass += ' status-completed';
        } else if (sequence.fabrication.percentage > 0) {
            barClass += ' status-in-progress';
        } else {
            barClass += ' status-not-started';
        }

        // Create bar element
        const $bar = $('<div></div>')
            .addClass(barClass)
            .css({
                'left': startPos + '%',
                'width': width + '%'
            });

        // Add bar content
        const barContent = `
            <span class="item-title-display">${sequence.project}: ${sequence.sequence}</span>
            <span class="item-details">
                ${sequence.fabrication.description}
                Start: ${GanttChart.Core.formatDate(startDate)}
                End: ${GanttChart.Core.formatDate(endDate)}
                Hours: ${sequence.fabrication.hours}
            </span>
        `;

        $bar.html(barContent);

        // Add progress indicator if percentage is valid
        if (sequence.fabrication.percentage >= 0) {
            const $progressBar = $('<div class="item-bar-percentage"></div>')
                .css('width', sequence.fabrication.percentage + '%');

            const $progressText = $('<div class="item-bar-percentage-text"></div>')
                .text(sequence.fabrication.percentage + '%');

            $progressBar.append($progressText);
            $bar.append($progressBar);
        }

        // Add the bar to the timeline
        $timeline.append($bar);

        // Add click event to show more details
        $bar.on('click', function() {
            showSequenceDetails(sequence);
        });
    }

    /**
     * Add workpackage markers to the timeline
     * @param {Object} sequence - Project sequence
     * @param {Array} sequenceWPs - Workpackages for this sequence
     * @param {jQuery} $timeline - Timeline element
     */
    function addWorkPackageMarkers(sequence, sequenceWPs, $timeline) {
        // Add each workpackage as a milestone marker
        sequenceWPs.forEach(wp => {
            const wpDate = GanttChart.Core.parseDate(wp.completionfriday);
            const position = GanttChart.TimeUtils.dateToPosition(wpDate);

            // Determine status class
            let statusClass = wp.onHold ? 'wp-on-hold' :
                wp.released ? 'wp-released' :
                    'wp-not-released';

            // Create marker element
            const $marker = $('<div></div>')
                .addClass(`date-marker workpackage-marker ${statusClass}`)
                .css('left', position + '%')
                .attr('title', `
                    WP: ${wp.workPackageNumber}
                    Week: ${wp.workWeek}
                    Status: ${wp.workPackageStatus}
                    Qty: ${wp.wpAssemblyQty}
                    Weight: ${wp.grossWeight} lbs
                    Hours: ${wp.hours}
                `);

            $timeline.append($marker);
        });

        // If we have workpackages, add bracket markers for first and last dates
        if (sequenceWPs.length > 0) {
            // Find min and max dates
            const startDate = sequenceWPs.reduce((min, wp) => {
                const date = GanttChart.Core.parseDate(wp.startDate);
                return (!min || date < min) ? date : min;
            }, null);

            const endDate = sequenceWPs.reduce((max, wp) => {
                const date = GanttChart.Core.parseDate(wp.completionfriday);
                return (!max || date > max) ? date : max;
            }, null);

            // Add start bracket
            if (startDate) {
                const startPos = GanttChart.TimeUtils.dateToPosition(startDate);
                const $startBracket = $('<div class="wp-bracket wp-start"></div>')
                    .css('left', startPos + '%')
                    .attr('title', 'WP Start Date: ' + GanttChart.Core.formatDate(startDate));

                $timeline.append($startBracket);
            }

            // Add end bracket
            if (endDate) {
                const endPos = GanttChart.TimeUtils.dateToPosition(endDate);
                const $endBracket = $('<div class="wp-bracket wp-end"></div>')
                    .css('left', endPos + '%')
                    .attr('title', 'WP End Date: ' + GanttChart.Core.formatDate(endDate));

                $timeline.append($endBracket);
            }
        }
    }

    /**
     * Add process indicators (IFF, Categorize) to the timeline
     * @param {Object} sequence - Project sequence
     * @param {jQuery} $timeline - Timeline element
     */
    function addProcessIndicators(sequence, $timeline) {
        // Add IFF (Issued For Fabrication) indicator
        if (sequence.iff.percentage >= 0) {
            const iffDate = GanttChart.Core.parseDate(sequence.iff.start);
            const iffPos = GanttChart.TimeUtils.dateToPosition(iffDate);

            // Determine indicator class based on percentage
            const iffClass = sequence.iff.percentage >= 100 ? 'iff-complete' : 'iff-incomplete';

            const $iffIndicator = $('<div></div>')
                .addClass(`process-indicator iff-indicator ${iffClass}`)
                .css('left', iffPos + '%')
                .attr('title', `IFF: ${GanttChart.Core.formatDate(sequence.iff.start)}
                                Completion: ${sequence.iff.percentage}%`);

            $timeline.append($iffIndicator);
        }

        // Add Categorize indicator
        if (sequence.categorize.percentage >= 0) {
            const categorizeDate = GanttChart.Core.parseDate(sequence.categorize.start);
            const categorizePos = GanttChart.TimeUtils.dateToPosition(categorizeDate);

            // Determine indicator class based on percentage
            const categorizeClass = sequence.categorize.percentage >= 100 ? 'categorize-complete' : 'categorize-incomplete';

            const $categorizeIndicator = $('<div></div>')
                .addClass(`process-indicator categorize-indicator ${categorizeClass}`)
                .css('left', categorizePos + '%')
                .attr('title', `Categorization: ${GanttChart.Core.formatDate(sequence.categorize.start)}
                                Completion: ${sequence.categorize.percentage}%`);

            $timeline.append($categorizeIndicator);
        }
    }

    /**
     * Show sequence details in a modal
     * @param {Object} sequence - Project sequence
     */
    function showSequenceDetails(sequence) {
        // Find workpackages for this sequence
        const sequenceWPs = workpackages.filter(wp =>
            wp.jobNumber === sequence.project &&
            wp.sequence === sequence.sequence
        );

        // Get categorization status if available
        const catStatus = categorizationStatus[`${sequence.project}:${sequence.sequence}`] || {
            TotalItems: 0,
            CategorizedCount: 0,
            IFFCount: 0,
            NotIFFCount: 0
        };

        // Calculate percentages
        const categorizedPercentage = catStatus.TotalItems > 0 ?
            ((catStatus.CategorizedCount / catStatus.TotalItems) * 100).toFixed(1) : '0.0';

        const iffPercentage = catStatus.TotalItems > 0 ?
            ((catStatus.IFFCount / catStatus.TotalItems) * 100).toFixed(1) : '0.0';

        // Populate modal with sequence details
        $('#ganttDetailModalLabel').text(`${sequence.project}: ${sequence.sequence}`);
        $('#ganttDetailContent').html(`
            <div class="detail-grid">
                <div class="detail-row">
                    <div class="detail-label">Project:</div>
                    <div class="detail-value">${sequence.project}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Sequence:</div>
                    <div class="detail-value">${sequence.sequence}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">PM:</div>
                    <div class="detail-value">${sequence.pm}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value">${sequence.fabrication.description}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Progress:</div>
                    <div class="detail-value">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: ${sequence.fabrication.percentage}%;" 
                                aria-valuenow="${sequence.fabrication.percentage}" aria-valuemin="0" aria-valuemax="100">
                                ${sequence.fabrication.percentage}%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Fabrication:</div>
                    <div class="detail-value">
                        Start: ${GanttChart.Core.formatDate(sequence.fabrication.start)}<br>
                        End: ${GanttChart.Core.formatDate(sequence.fabrication.end)}<br>
                        Hours: ${sequence.fabrication.hours}
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">IFF Status:</div>
                    <div class="detail-value">
                        Date: ${GanttChart.Core.formatDate(sequence.iff.start)}<br>
                        Completion: ${sequence.iff.percentage}%<br>
                        Items: ${catStatus.IFFCount}/${catStatus.TotalItems} (${iffPercentage}%)
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Categorization:</div>
                    <div class="detail-value">
                        Date: ${GanttChart.Core.formatDate(sequence.categorize.start)}<br>
                        Completion: ${sequence.categorize.percentage}%<br>
                        Items: ${catStatus.CategorizedCount}/${catStatus.TotalItems} (${categorizedPercentage}%)
                    </div>
                </div>
            </div>
            
            ${sequenceWPs.length > 0 ? `
                <div class="detail-section mt-4">
                    <h4>Work Packages</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>WP #</th>
                                    <th>Week</th>
                                    <th>Status</th>
                                    <th>Qty</th>
                                    <th>Weight (lbs)</th>
                                    <th>Hours</th>
                                    <th>End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${sequenceWPs.map(wp => `
                                    <tr>
                                        <td>${wp.workPackageNumber}</td>
                                        <td>${wp.workWeek}</td>
                                        <td>${wp.workPackageStatus}</td>
                                        <td>${wp.wpAssemblyQty}</td>
                                        <td>${wp.grossWeight}</td>
                                        <td>${wp.hours}</td>
                                        <td>${GanttChart.Core.formatDate(wp.completionfriday)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            ` : ''}
            
            <div class="text-center mt-3">
                <a href="sequence_status/sequence_status.php?jobNumber=${sequence.project}&sequenceName=${sequence.sequence}" 
                   class="btn btn-primary" target="_blank">
                   View Full Sequence Details
                </a>
            </div>
        `);

        // Show the modal
        const detailModal = new bootstrap.Modal(document.getElementById('ganttDetailModal'));
        detailModal.show();
    }

    /**
     * Load categorization status data
     * @param {Array} sequences - Array of sequences to load status for
     */
    function loadCategorizationStatus(sequences) {
        // Skip if no sequences
        if (!sequences || sequences.length === 0) return;

        // Get endpoint from configuration
        const config = GanttChart.Core.getConfig();
        const endpoint = config.catstatusEndpoint || 'ajax_get_ssf_timelinefabrication_catstatus.php';

        // Prepare payload with job sequences
        const payload = {
            jobSequences: sequences.map(seq => ({
                JobNumber: seq.project,
                SequenceName: seq.sequence
            }))
        };

        // Make request to get categorization status
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Skip if error in response
                if (data.error) {
                    console.error('Error loading categorization status:', data.error);
                    return;
                }

                // Process and store categorization status data
                categorizationStatus = {};
                data.forEach(entry => {
                    const rowId = `${entry.JobNumber}:${entry.SequenceName}`;
                    categorizationStatus[rowId] = entry;

                    // Update row status classes based on categorization
                    updateRowCategorization(rowId, entry);
                });
            })
            .catch(error => {
                console.error('Error loading categorization status:', error);
            });
    }

    /**
     * Update row categorization display
     * @param {string} rowId - Row ID (Project:Sequence)
     * @param {Object} catStatus - Categorization status object
     */
    function updateRowCategorization(rowId, catStatus) {
        const $labels = $(`.gantt-labels[data-rowid="${rowId}"]`);
        if (!$labels.length) return;

        // Get the corresponding gantt-chart div
        const $chart = $labels.closest('.gantt-row').find('.gantt-timeline');
        if (!$chart.length) return;

        // Calculate percentages
        const totalItems = catStatus.TotalItems || 0;
        const categorizedPercentage = totalItems > 0 ?
            ((catStatus.CategorizedCount / totalItems) * 100).toFixed(1) : '0.0';
        const iffPercentage = totalItems > 0 ?
            ((catStatus.IFFCount / totalItems) * 100).toFixed(1) : '0.0';

        // Set tooltip text
        $labels.attr('title', `
            Total Items: ${totalItems}
            IFF Status: ${catStatus.IFFCount} - IFF (${iffPercentage}%)
            Categorization Status: ${catStatus.CategorizedCount} - Categorized (${categorizedPercentage}%)
        `);

        // Update classes based on status
        $labels.removeClass('categorize-success categorize-danger');
        $labels.css('backgroundImage', 'none');

        // Add appropriate classes
        if (catStatus.CategorizedCount > 0) {
            $labels.addClass('categorize-success');
        } else {
            $labels.addClass('categorize-danger');
        }

        // Add striped background for items that are not IFF
        if (catStatus.NotIFFCount > 0) {
            $labels.css('backgroundImage', 'repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1) 5px, transparent 5px, transparent 10px)');
        }

        // Remove any existing indicators
        $chart.find('.progress-indicator').remove();

        // Add category progress indicator to chart
        const $sequence = GanttChart.Core.getState().items.find(s =>
            s.project + ':' + s.sequence === rowId
        );

        if ($sequence) {
            // Find positions for indicators
            const iffPosition = $sequence.iff.percentage == -1 ? 0 :
                GanttChart.TimeUtils.dateToPosition(GanttChart.Core.parseDate($sequence.iff.start));

            const categorizePosition = $sequence.categorize.percentage == -1 ? 0 :
                GanttChart.TimeUtils.dateToPosition(GanttChart.Core.parseDate($sequence.categorize.start));

            // Create IFF indicator
            const $iffIndicator = $('<div class="progress-indicator iff-indicator"></div>')
                .addClass(Number(iffPercentage) > 98 ? 'indicator-good' : 'indicator-warning')
                .css('left', iffPosition + '%')
                .attr('title', `IFF: ${$sequence.iff.start}, ${iffPercentage}% Complete`);
            $iffIndicator.text(`${iffPercentage}%`);

            // Create categorize indicator
            const $categorizeIndicator = $('<div class="progress-indicator cat-indicator"></div>')
                .addClass(Number(categorizedPercentage) > 98 ? 'indicator-good' : 'indicator-warning')
                .css('left', categorizePosition + '%')
                .attr('title', `Categorization: ${$sequence.categorize.start}, ${categorizedPercentage}% Complete`);
            $categorizeIndicator.text(`${categorizedPercentage}%`);

            // Add indicators to chart
            $chart.append($iffIndicator, $categorizeIndicator);
        }
    }

    /**
     * Export project data to CSV
     */
    function exportProjectData() {
        const state = GanttChart.Core.getState();
        const sequences = state.items;

        if (!sequences || sequences.length === 0) {
            alert('No data to export');
            return;
        }

        // Prepare CSV header
        const headers = [
            'Project',
            'Sequence',
            'PM',
            'Fabrication Description',
            'Start Date',
            'End Date',
            'Progress',
            'Hours',
            'IFF Date',
            'IFF Progress',
            'Categorize Date',
            'Categorize Progress',
            'Has WorkPackage'
        ];

        // Prepare CSV rows
        const csvRows = [
            headers.join(',')
        ];

        // Add data rows
        sequences.forEach(sequence => {
            // Check if sequence has workpackages
            const hasWP = hasWorkPackages(sequence);

            const row = [
                csvEscapeValue(sequence.project),
                csvEscapeValue(sequence.sequence),
                csvEscapeValue(sequence.pm),
                csvEscapeValue(sequence.fabrication.description),
                GanttChart.Core.formatDate(sequence.fabrication.start),
                GanttChart.Core.formatDate(sequence.fabrication.end),
                sequence.fabrication.percentage + '%',
                sequence.fabrication.hours,
                GanttChart.Core.formatDate(sequence.iff.start),
                sequence.iff.percentage + '%',
                GanttChart.Core.formatDate(sequence.categorize.start),
                sequence.categorize.percentage + '%',
                hasWP ? 'Yes' : 'No'
            ];

            csvRows.push(row.join(','));
        });

        // Create CSV content
        const csvContent = csvRows.join('\n');

        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'project_gantt_data.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Escape value for CSV format
     * @param {string} value - Value to escape
     * @return {string} Escaped value
     */
    function csvEscapeValue(value) {
        if (value === undefined || value === null) return '';

        value = String(value);

        // If value contains commas, quotes, or newlines, wrap in quotes and escape internal quotes
        if (/[",\n\r]/.test(value)) {
            return '"' + value.replace(/"/g, '""') + '"';
        }
        return value;
    }

    // Initialize when the document is ready
    $(document).ready(function() {
        try {
            console.log('Initializing custom implementation');

            // Override the default Ajax loadData method with our custom implementation
            const originalLoadData = GanttChart.Ajax.loadData;
            GanttChart.Ajax.loadData = function(filter) {
                console.log('Custom loadData called with filter:', filter);
                // Ensure we only call our custom implementation when it's ready
                setTimeout(function() {
                    loadProjectData(filter);
                }, 10);
            };

            // Rest of your initialization code...
            console.log('Custom implementation initialized');
        } catch (error) {
            console.error('Error initializing custom implementation:', error);
            // Fall back to original behavior if our custom init fails
            GanttChart.Ajax.loadData = GanttChart.Ajax.loadData || originalLoadData;
        }

        $(document).on('click', '.dropdown-item[data-filter]', function(e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            console.log('Project filter selected:', filter);

            // Update button text
            $('#filterDropdownBtn').text($(this).text());

            // Update current filter in config
            GanttChart.Core.setConfig({ currentFilter: filter });

            // Load data with the selected filter
            GanttChart.Ajax.loadData(filter);
        });
    });

    // Public API
    return {
        initialize: initialize,
        loadProjectData: loadProjectData,
        exportProjectData: exportProjectData
    };
})();