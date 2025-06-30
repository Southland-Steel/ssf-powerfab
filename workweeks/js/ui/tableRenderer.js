// Table Renderer class
class TableRenderer {
    constructor() {
        this.currentSortStation = null;
        this.sortDirection = 'asc'; // 'asc' for incomplete first, 'desc' for complete first
    }

    createTableHeader(projectData) {
        let headerHtml = `
        <tr class="table-columns">
            <th>Job<br>Route</th>
            <th>SeqLot<br>Main</th>
            <th>WP</th>
            <th>Asm. Qty</th>
            <th>Net # Each / Total</th>
            <th>Hrs. Each / Total</th>`;

        ORDERED_STATIONS.forEach(station => {
            const sortArrow = this.currentSortStation === station ?
                `<span class="sort-arrow">${this.sortDirection === 'asc' ? '▲' : '▼'}</span>` : '';

            headerHtml += `<th class="sortable" onclick="app.tableRenderer.sortByStation('${station}')" title="Click to sort by ${station} completion">
                ${station}${sortArrow}
            </th>`;
        });

        headerHtml += `</tr>`;
        $('#projectTable thead').html(headerHtml);
    }

    sortByStation(stationName) {
        // Toggle sort direction if clicking the same station
        if (this.currentSortStation === stationName) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSortStation = stationName;
            this.sortDirection = 'asc';
        }

        // Re-apply filters which will trigger populateTable with sorting
        app.filterManager.applyFilters();
    }

    getStationCompletionRatio(assembly, stationName) {
        const station = assembly.Stations.find(s => s.StationDescription === stationName);
        if (!station) return -1; // No station data

        if (PIECE_BASED_STATIONS.includes(stationName)) {
            // For piece-based stations, calculate based on pieces
            const totalPiecesNeeded = station.Pieces ? station.Pieces.reduce((sum, piece) =>
                sum + parseInt(piece.TotalPieceMarkQuantityNeeded || 0), 0) : 0;

            const totalPiecesCompleted = station.Pieces ? station.Pieces.reduce((sum, piece) => {
                let pieceQty = 0;
                if (stationName === 'NESTED') pieceQty = piece.QtyNested || 0;
                else if (stationName === 'MCUT' || stationName === 'CUT') pieceQty = piece.QtyCut || 0;
                else if (stationName === 'KIT') pieceQty = piece.QtyKitted || 0;
                return sum + parseInt(pieceQty);
            }, 0) : 0;

            return totalPiecesNeeded > 0 ? totalPiecesCompleted / totalPiecesNeeded : 1;
        } else {
            // For assembly-based stations
            const total = parseFloat(station.StationTotalQuantity) || 0;
            const completed = parseFloat(station.StationQuantityCompleted) || 0;
            return total > 0 ? completed / total : 1;
        }
    }

    populateTable(data) {
        // Apply sorting if a station is selected
        if (this.currentSortStation) {
            data.sort((a, b) => {
                const aRatio = this.getStationCompletionRatio(a, this.currentSortStation);
                const bRatio = this.getStationCompletionRatio(b, this.currentSortStation);

                // Handle cases where station doesn't exist
                if (aRatio === -1 && bRatio === -1) return 0;
                if (aRatio === -1) return 1; // Put items without the station at the end
                if (bRatio === -1) return -1;

                // Sort by completion ratio
                if (this.sortDirection === 'asc') {
                    // Incomplete first (lower ratio first)
                    return aRatio - bRatio;
                } else {
                    // Complete first (higher ratio first)
                    return bRatio - aRatio;
                }
            });
        } else {
            // Default sort: completed items at the bottom
            data.sort((a, b) => {
                const aCompleted = Calculator.checkCompletion(a.Stations);
                const bCompleted = Calculator.checkCompletion(b.Stations);
                if (aCompleted === bCompleted) return 0;
                return aCompleted ? 1 : -1;
            });
        }

        let bodyHtml = '';
        const totalJobHours = Calculator.calculateTotalProjectHours(data);
        const totalUsedHours = Calculator.calculateTotalUsedHours(data);
        const remainingHours = totalJobHours - totalUsedHours;
        const stationTotals = this.calculateStationTotals(data);

        // Add station summary row
        bodyHtml += this.addStationSummaryRow(stationTotals, data);

        // Add individual assembly rows
        data.forEach(assembly => {
            bodyHtml += this.renderAssemblyRow(assembly);
        });

        $('#projectTable tbody').html(bodyHtml);

        // Add click handlers
        this.attachEventHandlers();

        // Update summary data
        app.summaryRenderer.updateDataSummary(data, totalJobHours, totalUsedHours, remainingHours);
    }

    renderAssemblyRow(assembly) {
        const isCompleted = Calculator.checkCompletion(assembly.Stations);
        const isOnHold = (assembly.ReleasedToFab != 1);

        let rowHtml = `
            <tr class="table-datarow ${isCompleted ? 'completed-row' : ''} ${isOnHold ? 'hold-row' : ''}">
                <td title="ProductionControlID: ${assembly.ProductionControlID}">
                    ${assembly.JobNumber}<br>${assembly.RouteName}
                </td>
                <td title="SequenceID: ${assembly.SequenceID}, ProductionControlItemID: ${assembly.ProductionControlItemID}">
                    ${assembly.SequenceDescription} [${assembly.LotNumber}]<br>
                    <a href="#" onclick="app.jsonViewer.show('${assembly.ProductionControlItemSequenceID}'); return false;" class="text-decoration-none">${assembly.MainMark}</a>
                    <br>${assembly.Category}
                </td>
                <td title="ProductionControlItemSequenceID: ${assembly.ProductionControlItemSequenceID}">
                    ${assembly.WorkPackageNumber}
                </td>
                <td title="ProductionControlAssemblyID: ${assembly.ProductionControlAssemblyID}">${assembly.SequenceMainMarkQuantity}</td>
                <td>${Formatter.formatNumberWithCommas(assembly.NetAssemblyWeightEach)}# / ${Formatter.formatNumberWithCommas(assembly.TotalNetWeight)}#</td>
                <td>${Formatter.formatNumber(assembly.AssemblyManHoursEach)} / ${Formatter.formatNumber(assembly.TotalEstimatedManHours)}</td>`;

        // Add cells for each station
        ORDERED_STATIONS.forEach(stationName => {
            rowHtml += this.renderStationCell(assembly, stationName);
        });

        rowHtml += `</tr>`;
        return rowHtml;
    }

    renderStationCell(assembly, stationName) {
        const station = assembly.Stations.find(s => s.StationDescription === stationName);

        if (!station) {
            return `<td class="status-notstarted status-na">-</td>`;
        }

        let cellContent = '';
        let statusClass = '';

        if (PIECE_BASED_STATIONS.includes(stationName)) {
            // Calculate total pieces
            const totalPiecesCompleted = station.Pieces ? station.Pieces.reduce((sum, piece) => {
                let pieceQty = 0;
                if (stationName === 'NESTED') pieceQty = piece.QtyNested || 0;
                else if (stationName === 'MCUT' || stationName === 'CUT') pieceQty = piece.QtyCut || 0;
                else if (stationName === 'KIT') pieceQty = piece.QtyKitted || 0;
                return sum + parseInt(pieceQty);
            }, 0) : 0;

            const totalPiecesNeeded = station.Pieces ? station.Pieces.reduce((sum, piece) =>
                sum + parseInt(piece.TotalPieceMarkQuantityNeeded || 0), 0) : 0;

            // Determine status based on pieces
            if (totalPiecesCompleted === 0) {
                statusClass = 'status-notstarted';
            } else if (totalPiecesCompleted >= totalPiecesNeeded) {
                statusClass = 'status-complete';
            } else {
                statusClass = 'status-partial';
            }

            // Build cell content based on station
            if (stationName === 'NESTED') {
                cellContent = `
                    <a href="#" class="station-details" data-station="${stationName}"
                       data-assembly="${assembly.ProductionControlItemSequenceID}">
                        PCS: ${totalPiecesCompleted} / ${totalPiecesNeeded}
                    </a>`;
            } else if (stationName === 'CUT' || stationName === 'MCUT') {
                const stationTotalHours = Calculator.calculatePieceStationHours(station.Pieces, stationName);
                const completedAssemblies = Calculator.calculateCompletedAssemblies(station.Pieces, stationName);
                const totalNeeded = parseInt(assembly.SequenceMainMarkQuantity) || 0;
                const completionRatio = Calculator.safeDivide(completedAssemblies, totalNeeded);
                const stationUsedHours = stationTotalHours * completionRatio;

                cellContent = `
                    <a href="#" class="station-details" data-station="${stationName}"
                       data-assembly="${assembly.ProductionControlItemSequenceID}">
                        PCQTY: ${totalPiecesCompleted} / ${totalPiecesNeeded}
                    </a>
                    <br>HRS: ${Formatter.formatNumber(stationUsedHours)} / ${Formatter.formatNumber(stationTotalHours)}`;
            } else if (stationName === 'KIT') {
                cellContent = `
                    <a href="#" class="station-details" data-station="${stationName}"
                       data-assembly="${assembly.ProductionControlItemSequenceID}">
                        PCQTY: ${totalPiecesCompleted} / ${totalPiecesNeeded}
                    </a>`;
            }

            return `<td class="${statusClass}">${cellContent}</td>`;
        } else {
            // For assembly-based stations
            statusClass = Formatter.getStatusClass(station.StationQuantityCompleted, station.StationTotalQuantity);

            const assemblyStationHours = Calculator.calculateStationHours(
                assembly.RouteName || 'DEFAULT',
                assembly.Category || 'DEFAULT',
                parseFloat(assembly.TotalEstimatedManHours || 0)
            );

            const completionRatio = Calculator.safeDivide(station.StationQuantityCompleted, station.StationTotalQuantity);
            const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);
            const stationCompletedWeight = assemblyWeight * completionRatio;

            if (stationName === 'FIT') {
                const stationTotalHours = assemblyStationHours[stationName] || 0;
                const stationUsedHours = Calculator.safeDivide(station.StationQuantityCompleted * stationTotalHours, station.StationTotalQuantity);

                cellContent = `
                    ASMQTY: ${station.StationQuantityCompleted} / ${station.StationTotalQuantity}<br>
                    HRS: ${Formatter.formatNumber(stationUsedHours)} / ${Formatter.formatNumber(stationTotalHours)}<br>
                    WT: ${Formatter.formatNumberWithCommas(Math.round(stationCompletedWeight))}#`;
            } else if (stationName === 'WELD') {
                cellContent = `
                    ASMQTY: ${station.StationQuantityCompleted} / ${station.StationTotalQuantity}<br>
                    WT: ${Formatter.formatNumberWithCommas(Math.round(stationCompletedWeight))}#`;
            } else if (stationName === 'FINAL QC') {
                const stationTotalHours = assemblyStationHours[stationName] || 0;
                const stationUsedHours = Calculator.safeDivide(station.StationQuantityCompleted * stationTotalHours, station.StationTotalQuantity);

                cellContent = `
                    ASMQTY: ${station.StationQuantityCompleted} / ${station.StationTotalQuantity}<br>
                    HRS: ${Formatter.formatNumber(stationUsedHours)} / ${Formatter.formatNumber(stationTotalHours)}<br>
                    WT: ${Formatter.formatNumberWithCommas(Math.round(stationCompletedWeight))}#`;
            }

            return `<td class="${statusClass}">${cellContent}</td>`;
        }
    }

    calculateStationTotals(data) {
        let stationTotals = {};

        ORDERED_STATIONS.forEach(station => {
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
            if (!assembly || !assembly.Stations) return;

            const assemblyHours = parseFloat(assembly.TotalEstimatedManHours || 0);
            const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);

            const assemblyStationHours = Calculator.calculateStationHours(
                assembly.RouteName || 'DEFAULT',
                assembly.Category || 'DEFAULT',
                assemblyHours
            );

            assembly.Stations.forEach(station => {
                if (!station) return;

                const stationName = station.StationDescription;
                if (!ORDERED_STATIONS.includes(stationName)) return;

                let completed = parseFloat(station.StationQuantityCompleted || 0);
                let total = parseFloat(station.StationTotalQuantity || 0);

                stationTotals[stationName].completed += completed;
                stationTotals[stationName].total += total;

                const completionRatio = Calculator.safeDivide(completed, total);

                // Calculate hours only for stations that track them
                if (HOUR_TRACKING_STATIONS.includes(stationName)) {
                    let stationTotalHours = 0;

                    if (['MCUT', 'CUT'].includes(stationName)) {
                        stationTotalHours = Calculator.calculatePieceStationHours(station.Pieces, stationName);
                    } else {
                        stationTotalHours = assemblyStationHours[stationName] || 0;
                    }

                    const completedHours = stationTotalHours * completionRatio;
                    stationTotals[stationName].hours.completed += completedHours;
                    stationTotals[stationName].hours.total += stationTotalHours;
                }

                // Weight calculations
                stationTotals[stationName].weight.completed += assemblyWeight * completionRatio;
                stationTotals[stationName].weight.total += assemblyWeight;

                // Calculate piece totals for piece-based stations
                if (PIECE_BASED_STATIONS.includes(stationName) && station.Pieces) {
                    station.Pieces.forEach(piece => {
                        const qtyNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded || 0);
                        let qtyCompleted = 0;

                        if (stationName === 'NESTED') {
                            qtyCompleted = parseInt(piece.QtyNested || 0);
                        } else if (stationName === 'MCUT' || stationName === 'CUT') {
                            qtyCompleted = parseInt(piece.QtyCut || 0);
                        } else if (stationName === 'KIT') {
                            qtyCompleted = parseInt(piece.QtyKitted || 0);
                        }

                        stationTotals[stationName].pieces_completed += qtyCompleted;
                        stationTotals[stationName].pieces_total += qtyNeeded;
                    });
                }
            });
        });

        return stationTotals;
    }

    addStationSummaryRow(stationTotals, data) {
        const totalLineItems = data.length;
        const totalAsmQuantity = data.reduce((sum, item) => sum + (parseInt(item.SequenceMainMarkQuantity) || 0), 0);
        const completedLineItems = data.filter(item => Calculator.checkCompletion(item.Stations)).length;
        const completedAssemblies = data.reduce((sum, item) => {
            if (Calculator.checkCompletion(item.Stations)) {
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

        ORDERED_STATIONS.forEach(station => {
            const totals = stationTotals[station];
            if (!totals || totals.total === 0) {
                bodyHtml += '<td class="col-empty">-</td>';
            } else {
                const qtyPercentage = Calculator.safeDivide(totals.completed * 100, totals.total);
                const hoursPercentage = Calculator.safeDivide(totals.hours.completed * 100, totals.hours.total);
                const weightPercentage = Calculator.safeDivide(totals.weight.completed * 100, totals.weight.total);
                const isComplete = Math.abs(qtyPercentage - 100) < 0.01;

                if (station === 'NESTED') {
                    bodyHtml += `
                        <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                            PCNEED: ${totals.pieces_completed || 0} / ${totals.pieces_total || 0}
                        </td>`;
                } else if (station === 'CUT' || station === 'MCUT') {
                    const pcQtyPercentage = Calculator.safeDivide(totals.pieces_completed * 100, totals.pieces_total);
                    bodyHtml += `
                        <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                            PCQTY: ${totals.pieces_completed} / ${totals.pieces_total} (${pcQtyPercentage.toFixed(1)}%)<br>
                            HRS: ${Formatter.formatNumberWithCommas(Math.round(totals.hours.completed))} / ${Formatter.formatNumberWithCommas(Math.round(totals.hours.total))} (${hoursPercentage.toFixed(1)}%)
                        </td>`;
                } else if (station === 'KIT') {
                    const pcQtyPercentage = Calculator.safeDivide(totals.pieces_completed * 100, totals.pieces_total);
                    bodyHtml += `
                        <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                            PCQTY: ${totals.pieces_completed} / ${totals.pieces_total} (${pcQtyPercentage.toFixed(1)}%)
                        </td>`;
                } else if (station === 'FIT') {
                    bodyHtml += `
                        <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                            ASMQTY: ${totals.completed} / ${totals.total} (${qtyPercentage.toFixed(1)}%)<br>
                            HRS: ${Formatter.formatNumberWithCommas(Math.round(totals.hours.completed))} / ${Formatter.formatNumberWithCommas(Math.round(totals.hours.total))} (${hoursPercentage.toFixed(1)}%)<br>
                            WT: ${Formatter.formatNumberWithCommas(Math.round(totals.weight.completed))} / ${Formatter.formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
                        </td>`;
                } else if (station === 'WELD') {
                    bodyHtml += `
                        <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                            ASMQTY: ${totals.completed} / ${totals.total} (${qtyPercentage.toFixed(1)}%)<br>
                            WT: ${Formatter.formatNumberWithCommas(Math.round(totals.weight.completed))} / ${Formatter.formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
                        </td>`;
                } else if (station === 'FINAL QC') {
                    bodyHtml += `
                        <td class="sumcell ${isComplete ? 'col-complete' : ''}">
                            ASMQTY: ${totals.completed} / ${totals.total} (${qtyPercentage.toFixed(1)}%)<br>
                            HRS: ${Formatter.formatNumberWithCommas(Math.round(totals.hours.completed))} / ${Formatter.formatNumberWithCommas(Math.round(totals.hours.total))} (${hoursPercentage.toFixed(1)}%)<br>
                            WT: ${Formatter.formatNumberWithCommas(Math.round(totals.weight.completed))} / ${Formatter.formatNumberWithCommas(Math.round(totals.weight.total))} (${weightPercentage.toFixed(1)}%)
                        </td>`;
                }
            }
        });

        return bodyHtml + '</tr>';
    }

    clearSort() {
        this.currentSortStation = null;
        this.sortDirection = 'asc';
        app.filterManager.applyFilters();
    }

    attachEventHandlers() {
        $('.station-details').on('click', function(e) {
            e.preventDefault();
            const stationName = $(this).data('station');
            const assemblyId = $(this).data('assembly');
            app.modalManager.showPiecemarkDetails(stationName, assemblyId);
        });
    }
}