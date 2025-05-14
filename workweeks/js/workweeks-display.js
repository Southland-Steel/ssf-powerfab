// workweeks/js/workweeks-display.js

// Create the table header
function createTableHeader() {
    let headerHtml = `
    <tr class="table-columns">
        <th>Job<br>Route</th>
        <th>SeqLot<br>Main</th>
        <th>WP</th>
        <th>Asm. Qty</th>
        <th>Net # Each / Total</th>
        <th>Hrs. Each / Total</th>
    `;

    orderedStations.forEach(station => {
        // Calculate the completion percentage for this station using assembly quantities
        const stationTotal = projectData.reduce((acc, item) => {
            if (!item || !item.Stations) return acc;

            const stationData = item.Stations.find(s => s && s.StationDescription === station);
            if (!stationData) return acc;

            // For all stations, use assembly quantities
            const totalQty = parseInt(stationData.StationTotalQuantity) || 0;
            const completedQty = parseInt(stationData.StationQuantityCompleted) || 0;

            return {
                total: acc.total + totalQty,
                completed: acc.completed + completedQty
            };
        }, { total: 0, completed: 0 });

        const percentage = stationTotal.total ? (stationTotal.completed / stationTotal.total) * 100 : 0;

        headerHtml += `
            <th>
                ${station}
            </th>`;
    });

    headerHtml += `</tr>`;
    document.getElementById('projectTable').querySelector('thead').innerHTML = headerHtml;
}

// Display the filtered data in the table
function displayTable(data) {
    // Handle case where data is empty or undefined
    if (!data || data.length === 0) {
        document.getElementById('projectTable').querySelector('tbody').innerHTML =
            '<tr><td colspan="' + (6 + orderedStations.length) + '" class="text-center">No data available</td></tr>';
        updateDataSummary([], 0, 0, 0);
        return;
    }

    // Create the table header first
    createTableHeader();

    // Sort data to show completed items at the bottom
    data.sort((a, b) => {
        const aCompleted = checkCompletion(a.Stations);
        const bCompleted = checkCompletion(b.Stations);
        if (aCompleted === bCompleted) return 0;
        return aCompleted ? 1 : -1;
    });

    const stationTotals = calculateStationTotals(data);
    const totalJobHours = calculateTotalHours(data);
    const totalUsedHours = calculateTotalUsedHours(data);
    const remainingHours = totalJobHours - totalUsedHours;

    let bodyHtml = '';

    // Add station summary row
    bodyHtml += addStationSummaryRow(stationTotals, data);

    // Add individual assembly rows
    data.forEach(assembly => {
        if (!assembly) return;

        const isCompleted = checkCompletion(assembly.Stations);
        const isOnHold = (assembly.ReleasedToFab != 1);

        // Ensure assembly properties are valid before using them
        const stationHours = calculateStationHours(
            assembly.RouteName || 'DEFAULT',
            assembly.Category || 'DEFAULT',
            parseFloat(assembly.TotalEstimatedManHours || 0)
        );

        const totalNetWeight = assembly.TotalNetWeight || 0;
        const netAssemblyWeightEach = assembly.NetAssemblyWeightEach || 0;
        const assemblyManHoursEach = assembly.AssemblyManHoursEach || 0;
        const totalEstimatedManHours = assembly.TotalEstimatedManHours || 0;

        bodyHtml += `
        <tr class="table-datarow ${isCompleted ? 'completed-row' : ''} ${isOnHold ? 'hold-row' : ''}">
            <td title="ProductionControlID: ${assembly.ProductionControlID || 'N/A'}">
                ${assembly.JobNumber || 'N/A'}<br>${assembly.RouteName || 'N/A'}
            </td>
            <td title="SequenceID: ${assembly.SequenceID || 'N/A'}, ProductionControlItemID: ${assembly.ProductionControlItemID || 'N/A'}">
                ${assembly.SequenceDescription || 'N/A'} [${assembly.LotNumber || 'N/A'}]<br>
                <a href="#" onclick="showJsonModal('${assembly.ProductionControlItemSequenceID}'); return false;" class="text-decoration-none">${assembly.MainMark || 'N/A'}</a>
                <br>${assembly.Category || 'N/A'}
            </td>
            <td title="ProductionControlItemSequenceID: ${assembly.ProductionControlItemSequenceID || 'N/A'}">
                ${assembly.WorkPackageNumber || 'N/A'}
            </td>
            <td title="ProductionControlAssemblyID: ${assembly.ProductionControlAssemblyID || 'N/A'}">${assembly.SequenceMainMarkQuantity || 0}</td>
            <td>${formatNumberWithCommas(netAssemblyWeightEach)}# / ${formatNumberWithCommas(totalNetWeight)}#</td>
            <td>${formatNumber(assemblyManHoursEach)} / ${formatNumber(totalEstimatedManHours)}</td>
        `;

        // Add cells for each station
        orderedStations.forEach(stationName => {
            const station = assembly.Stations ? assembly.Stations.find(s => s && s.StationDescription === stationName) : null;
            const stationObj = getStation(stationName);
            bodyHtml += stationObj.renderCell(station, assembly);
        });

        bodyHtml += `</tr>`;
    });

    document.getElementById('projectTable').querySelector('tbody').innerHTML = bodyHtml;

    // Add event listeners for piecemark details
    document.querySelectorAll('.station-details').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const stationName = this.getAttribute('data-station');
            const assemblyId = this.getAttribute('data-assembly');
            showPiecemarkDetails(stationName, assemblyId);
        });
    });

    // Update summary data
    updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours);
}

// Add a summary row for station totals
function addStationSummaryRow(stationTotals, data) {
    const totalLineItems = data.length;
    const totalAsmQuantity = data.reduce((sum, item) => {
        if (!item) return sum;
        return sum + (parseInt(item.SequenceMainMarkQuantity) || 0);
    }, 0);

    const completedLineItems = data.filter(item => item && checkCompletion(item.Stations)).length;
    const completedAssemblies = data.reduce((sum, item) => {
        if (!item) return sum;
        if (checkCompletion(item.Stations)) {
            return sum + (parseInt(item.SequenceMainMarkQuantity) || 0);
        }
        return sum;
    }, 0);

    let bodyHtml = `<tr class="station-summary">
        <td colspan="6">
            Station Totals: (completed of total)<br>
            Line Items: ${completedLineItems} of ${totalLineItems}<br>
            Assemblies: ${completedAssemblies} of ${totalAsmQuantity}
        </td>`;

    orderedStations.forEach(station => {
        const totals = stationTotals[station];
        if (!totals || totals.total === 0) {
            bodyHtml += '<td class="col-empty">-</td>';
        } else {
            const qtyPercentage = safeDivide(totals.completed * 100, totals.total);
            const hoursPercentage = safeDivide(totals.hours.completed * 100, totals.hours.total);
            const weightPercentage = safeDivide(totals.weight.completed * 100, totals.weight.total);
            const isComplete = Math.abs(qtyPercentage - 100) < 0.01;

            // Different display format based on station type
            if (station === 'NESTED') {
                bodyHtml += `
                <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                    ASMNEED: ${totals.completed} / ${totals.total}<br>
                    PCNEED: ${totals.pieces_completed || 0} / ${totals.pieces_total || 0}<br>
                    ASMWT: ${formatNumberWithCommas(Math.round(totals.weight.completed))} / ${formatNumberWithCommas(Math.round(totals.weight.total))}
                </td>`;
            } else if (station === 'CUT' || station === 'KIT') {
                const assemblyQtyPercentage = safeDivide(totals.completed * 100, totals.total);

                bodyHtml += `
                <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                    ASMQTY: ${totals.completed} / ${totals.total} (${assemblyQtyPercentage.toFixed(1)}%)<br>
                    PCQTY: ${totals.pieces_completed} / ${totals.pieces_total} (${(safeDivide(totals.pieces_completed * 100, totals.pieces_total)).toFixed(1)}%)<br>
                    ASMWT: ${formatNumberWithCommas(Math.round(totals.weight.completed))} / ${formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
                </td>`;
            } else {
                bodyHtml += `
                <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                    QTY: ${totals.completed} / ${totals.total} (${qtyPercentage.toFixed(1)}%)<br>
                    HRS: ${formatNumberWithCommas(Math.round(totals.hours.completed))} / ${formatNumberWithCommas(Math.round(totals.hours.total))} (${hoursPercentage.toFixed(1)}%)<br>
                    WT: ${formatNumberWithCommas(Math.round(totals.weight.completed))} / ${formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
                </td>`;
            }
        }
    });

    return bodyHtml + '</tr>';
}

// Update the data summary sections
function updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours) {
    if (!data || data.length === 0) {
        document.getElementById('lineItemSummary').innerHTML = '<strong>No data available</strong>';
        document.getElementById('hoursSummary').innerHTML = '<strong>No data available</strong>';
        document.getElementById('weightSummary').innerHTML = '<strong>No data available</strong>';
        return;
    }

    const totalWeight = calculateTotalWeight(data);
    const completedWeight = calculateCompletedWeight(data);
    const remainingWeight = totalWeight - completedWeight;
    const remainingTons = Math.round(remainingWeight / 200) / 10;
    const totalTons = Math.round(totalWeight / 200) / 10;

    // Use safeDivide to avoid division by zero errors
    const hoursPerTon = safeDivide(totalJobHours, totalTons);
    const lbsPerHour = safeDivide(totalWeight, totalJobHours);

    const percentageCompleteByHours = safeDivide(totalUsedHours * 100, totalJobHours);
    const percentageCompleteByWeight = safeDivide(completedWeight * 100, totalWeight);

    // Update hours summary
    document.getElementById('hoursSummary').innerHTML = `
        Visible Total Hours: ${formatNumberWithCommas(totalJobHours)}<br>
        Visible Hours Complete: ${formatNumberWithCommas(totalUsedHours)} (${percentageCompleteByHours.toFixed(2)}%)<br>
        Visible Hours Remaining: ${formatNumberWithCommas(remainingHours)}<br>
        Visible Hours per Ton: ${hoursPerTon.toFixed(2)}<span style="font-size: 0.8rem; font-weight: bold; color: #3a0202"> -
        ${lbsPerHour.toFixed(2)} (lbs/hr)</span>
    `;

    // Update weight summary
    document.getElementById('weightSummary').innerHTML = `
        Visible Total Weight: ${formatNumberWithCommas(totalWeight)} lbs (${totalTons} tons)<br>
        Visible Green Flag Weight: ${formatNumberWithCommas(completedWeight)} lbs (${percentageCompleteByWeight.toFixed(2)}%)<br>
        Remaining Green Flag Weight: ${formatNumberWithCommas(remainingWeight)} lbs (${remainingTons} tons)<br>
    `;

    const totalLineItems = data.length;
    const totalAsmQty = data.reduce((sum, item) => {
        if (!item) return sum;
        return sum + (parseInt(item.SequenceMainMarkQuantity) || 0);
    }, 0);

    // Update assembly info section
    document.getElementById('lineItemSummary').innerHTML = `
        Total Line Items: ${formatNumberWithCommas(totalLineItems)}<br>
        Total Assembly Qty: ${formatNumberWithCommas(totalAsmQty)}<br>
    `;
}

// Calculate station totals for summary row
function calculateStationTotals(data) {
    let stationTotals = {};

    orderedStations.forEach(station => {
        stationTotals[station] = {
            completed: 0,
            total: 0,
            pieces_completed: 0,
            pieces_total: 0,
            hours: {
                completed: 0,
                total: 0
            },
            weight: {
                completed: 0,
                total: 0
            }
        };
    });

    data.forEach(assembly => {
        // Skip if assembly is undefined or doesn't have the necessary properties
        if (!assembly || !assembly.Stations) return;

        // Use parseFloat and provide defaults to ensure we have valid numbers
        const totalHours = parseFloat(assembly.TotalEstimatedManHours || 0);

        const stationHours = calculateStationHours(
            assembly.RouteName || 'DEFAULT',
            assembly.Category || 'DEFAULT',
            totalHours
        );

        const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);

        if (assembly.Stations) {
            assembly.Stations.forEach(station => {
                if (!station) return;

                const stationName = station.StationDescription;
                if (!orderedStations.includes(stationName)) return;

                let completed = parseFloat(station.StationQuantityCompleted || 0);
                let total = parseFloat(station.StationTotalQuantity || 0);

                // Sum up quantities
                stationTotals[stationName].completed += completed;
                stationTotals[stationName].total += total;

                // Calculate hours and weights
                const completionRatio = safeDivide(completed, total);
                const stationTotalHours = stationHours[stationName] || 0;
                const completedHours = stationTotalHours * completionRatio;

                stationTotals[stationName].hours.completed += completedHours;
                stationTotals[stationName].hours.total += stationTotalHours;
                stationTotals[stationName].weight.completed += assemblyWeight * completionRatio;
                stationTotals[stationName].weight.total += assemblyWeight;

                // Special handling for piecemark stations (NESTED, CUT, KIT)
                if (['NESTED', 'CUT', 'KIT'].includes(stationName) && station.Pieces) {
                    station.Pieces.forEach(piece => {
                        // Skip if piece is undefined
                        if (!piece) return;

                        if (stationName === 'NESTED') {
                            // For nesting, account for what's already been cut
                            const totalNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded || 0);
                            const stillNeedsNesting = parseInt(piece.StillNeedsNesting || 0);
                            const alreadyNested = totalNeeded - stillNeedsNesting;

                            stationTotals[stationName].pieces_completed += alreadyNested;
                            stationTotals[stationName].pieces_total += totalNeeded;
                        } else {
                            const totalNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded || 0);
                            stationTotals[stationName].pieces_total += totalNeeded;

                            if (stationName === 'CUT') {
                                stationTotals[stationName].pieces_completed += parseInt(piece.QtyCut || 0);
                            } else { // KIT
                                stationTotals[stationName].pieces_completed += parseInt(piece.QtyKitted || 0);
                            }
                        }
                    });
                }
            });
        }
    });

    return stationTotals;
}

// Calculate total hours used across all stations
function calculateTotalUsedHours(data) {
    let totalUsed = 0;

    data.forEach(assembly => {
        // Skip if assembly is undefined or doesn't have the necessary properties
        if (!assembly || !assembly.Stations) return;

        // Ensure we have a valid number for totalHours
        const totalHours = parseFloat(assembly.TotalEstimatedManHours || 0);

        if (isNaN(totalHours)) {
            console.warn(`Invalid TotalEstimatedManHours value for assembly with MainMark: ${assembly.MainMark || 'unknown'}`);
            return;
        }

        const stationHours = calculateStationHours(
            assembly.RouteName || 'DEFAULT',
            assembly.Category || 'DEFAULT',
            totalHours
        );

        // For each station, calculate the hours used based on completion percentage
        if (assembly.Stations) {
            assembly.Stations.forEach(station => {
                if (!station || !orderedStations.includes(station.StationDescription)) return;

                const stationTotal = parseFloat(station.StationTotalQuantity || 0);
                const stationCompleted = parseFloat(station.StationQuantityCompleted || 0);
                const completionRatio = safeDivide(stationCompleted, stationTotal);
                const stationAllocatedHours = stationHours[station.StationDescription] || 0;

                totalUsed += stationAllocatedHours * completionRatio;
            });
        }
    });

    return totalUsed;
}

// Calculate total weight of all assemblies
function calculateTotalWeight(data) {
    return data.reduce((sum, assembly) => {
        if (!assembly) return sum;
        const weight = parseFloat(assembly.TotalNetWeight || 0);
        return sum + (isNaN(weight) ? 0 : weight);
    }, 0);
}

// Calculate total estimated hours of all assemblies
function calculateTotalHours(data) {
    return data.reduce((sum, assembly) => {
        if (!assembly) return sum;
        const hours = parseFloat(assembly.TotalEstimatedManHours || 0);
        return sum + (isNaN(hours) ? 0 : hours);
    }, 0);
}

// Calculate completed weight (assemblies where the final station is complete)
function calculateCompletedWeight(data) {
    return data.reduce((sum, assembly) => {
        if (!assembly || !assembly.Stations) return sum;

        const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);
        if (isNaN(assemblyWeight)) return sum;

        const lastStation = assembly.Stations
            .filter(station => station && orderedStations.includes(station.StationDescription))
            .sort((a, b) => orderedStations.indexOf(b.StationDescription) - orderedStations.indexOf(a.StationDescription))[0];

        if (lastStation && lastStation.StationQuantityCompleted === lastStation.StationTotalQuantity) {
            return sum + assemblyWeight;
        }
        return sum;
    }, 0);
}

// Check if an assembly is completed (final station is complete)
function checkCompletion(stations) {
    // Handle case where stations is undefined
    if (!stations || !Array.isArray(stations)) return false;

    const lastRelevantStation = [...stations].reverse().find(station =>
        station && station.StationDescription === "FINAL QC"
    );

    return lastRelevantStation &&
        lastRelevantStation.StationQuantityCompleted === lastRelevantStation.StationTotalQuantity;
}

// Format a number with 2 decimal places
function formatNumber(value) {
    value = parseFloat(value);
    return isNaN(value) ? "0.00" : value.toFixed(2);
}

// Format a number with commas for thousands
function formatNumberWithCommas(number) {
    if (isNaN(number) || number === null || number === undefined) return "0";
    return Number(parseFloat(number).toFixed(0)).toLocaleString();
}

// Show detailed JSON data in a modal
function showJsonModal(pciseqId) {
    const rowData = projectData.find(item =>
        item && item.ProductionControlItemSequenceID &&
        item.ProductionControlItemSequenceID.toString() === pciseqId.toString()
    );

    if (rowData) {
        document.getElementById('jsonContent').innerHTML = formatCollapsibleJson(rowData);
        const jsonModal = new bootstrap.Modal(document.getElementById('jsonModal'));
        jsonModal.show();
    } else {
        console.warn(`No data found for ProductionControlItemSequenceID: ${pciseqId}`);
    }
}

// Format JSON for display in the modal with collapsible sections
function formatCollapsibleJson(obj, level = 0) {
    if (obj === null) return '<span class="json-null">null</span>';
    if (typeof obj !== 'object') {
        if (typeof obj === 'string') return `<span class="json-string">"${obj}"</span>`;
        if (typeof obj === 'boolean') return `<span class="json-boolean">${obj}</span>`;
        if (typeof obj === 'number') return `<span class="json-number">${obj}</span>`;
        return obj;
    }

    const isArray = Array.isArray(obj);
    const items = Object.entries(obj);
    const length = items.length;

    if (length === 0) return isArray ? '[]' : '{}';

    const indent = '  '.repeat(level);
    const closingBracket = isArray ? ']' : '}';
    const collapsibleClass = level > 0 ? 'collapsed' : '';

    let result = `<div class="expandable-container ${collapsibleClass}">`;
    result += `<span class="collapse-icon" onclick="toggleCollapse(this)">▼</span>`;
    result += isArray ? '[' : '{';
    result += `<span class="array-length">${length} item${length > 1 ? 's' : ''}</span>`;
    result += '<div class="collapsible-content json-indent">';

    items.forEach(([key, value], index) => {
        result += '<div>';
        if (!isArray) {
            result += `<span class="json-key">"${key}"</span>: `;
        }
        result += formatCollapsibleJson(value, level + 1);
        if (index < length - 1) result += ',';
        result += '</div>';
    });

    result += '</div>' + closingBracket + '</div>';
    return result;
}

// Toggle collapse state of JSON sections
function toggleCollapse(icon) {
    const container = icon.parentElement;
    container.classList.toggle('collapsed');
    icon.textContent = container.classList.contains('collapsed') ? '►' : '▼';
}

// Expand all JSON sections
function expandAllJson() {
    const containers = document.querySelectorAll('.expandable-container');
    containers.forEach(container => {
        container.classList.remove('collapsed');
        container.querySelector('.collapse-icon').textContent = '▼';
    });
}

// Collapse all JSON sections
function collapseAllJson() {
    const containers = document.querySelectorAll('.expandable-container');
    containers.forEach(container => {
        if (!container.parentElement.id || container.parentElement.id !== 'jsonContent') {
            container.classList.add('collapsed');
            container.querySelector('.collapse-icon').textContent = '►';
        }
    });
}

// Copy JSON to clipboard
function copyJsonToClipboard() {
    const jsonContent = document.getElementById('jsonContent').textContent;
    try {
        const formattedJson = JSON.stringify(JSON.parse(jsonContent), null, 2);
        navigator.clipboard.writeText(formattedJson).then(() => {
            alert('JSON copied to clipboard!');
        });
    } catch (err) {
        console.error('Failed to copy text: ', err);
        // Fallback method for older browsers
        const tempTextArea = document.createElement('textarea');
        tempTextArea.value = jsonContent;
        document.body.appendChild(tempTextArea);
        tempTextArea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextArea);
        alert('JSON copied to clipboard!');
    }
}