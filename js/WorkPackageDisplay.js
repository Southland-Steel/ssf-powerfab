class WorkPackageDisplay {
    constructor(containerSelector, tableSelector = '#projectTable') {
        this.container = document.querySelector(containerSelector);
        this.tableContainer = document.querySelector(tableSelector);
        
        if (!this.container) {
            console.error(`Container element '${containerSelector}' not found`);
        }
        if (!this.tableContainer) {
            console.error(`Table element '${tableSelector}' not found`);
        }
        
        this.workPackageData = null;
    }

    renderWorkPackageDetail(workPackage) {
        const detailContainer = this.container.querySelector('.workpackage-container');

        const statusClasses = [
            workPackage.OnHold ? 'on-hold' : '',
            workPackage.ReleasedToFab ? 'released' : 'not-released',
            this.isOverdue(workPackage.DueDate) ? 'overdue' : ''
        ].filter(Boolean).join(' ');

        detailContainer.innerHTML = `
            <div class="workpackage-container ${statusClasses}">
                <div class="info-group">
                    <div class="info-item">
                        <span class="label">Job:</span>
                        <span class="value">${this.escapeHtml(workPackage.JobNumber)}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">WP:</span>
                        <span class="value">${this.escapeHtml(workPackage.WorkPackageNumber)}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Group:</span>
                        <span class="value">${this.escapeHtml(workPackage.Group1 || 'N/A')}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Desc:</span>
                        <span class="value">${this.escapeHtml(workPackage.WorkPackageDescription)}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Due:</span>
                        <span class="value ${this.isOverdue(workPackage.DueDate) ? 'critical' : ''}">${workPackage.DueDate}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Weight:</span>
                        <span class="value">${workPackage.Weight}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Hours:</span>
                        <span class="value">${workPackage.Hours}</span>
                    </div>
                    <div class="info-item notes">
                        <span class="label">Notes:</span>
                        <span class="value notes-value" title="${this.escapeHtml(workPackage.Notes)}">${this.escapeHtml(workPackage.Notes)}</span>
                    </div>
                    ${this.renderStatusIndicators(workPackage)}
                </div>
            </div>
        `;
    }

    renderStatusIndicators(workPackage) {
        return `
            <div class="status-indicator">
                <div class="info-item">
                    <span class="status-dot ${workPackage.ReleasedToFab ? 'good-dot' : 'bad-dot'}"></span>
                    <span class="value">${workPackage.ReleasedToFab ? 'WP Released' : 'WP Not Released'}</span>
                </div>
                <div class="info-item">
                    <span class="status-dot ${!workPackage.OnHold ? 'good-dot' : 'bad-dot'}"></span>
                    <span class="value">${workPackage.OnHold ? 'On Hold' : 'Not On Hold'}</span>
                </div>
            </div>
        `;
    }

    renderWorkPackageStatistics(totals) {
        const statisticsContainer = this.container.querySelector('.workpackage-statistics');
        statisticsContainer.innerHTML = `
            <div class="statistics-group">
                ${this.renderStatisticsItem('Hours', totals.totalHours, totals.completedHours, totals.remainingHours)}
                ${this.renderStatisticsItem('Weight', totals.totalWeight, totals.completedWeight, totals.remainingWeight)}
                <div class="statistics-item">
                    <span class="label">Percent Complete:</span>
                    <span class="value">${this.formatNumber(totals.percentComplete)}%</span>
                </div>
            </div>
        `;
    }

    renderStatisticsItem(label, total, completed, remaining) {
        return `
            <div class="statistics-item">
                <span class="label">Total ${label}:</span>
                <span class="value">${this.formatNumber(total)}</span>
                <span class="label">Completed:</span>
                <span class="value">${this.formatNumber(completed)}</span>
                <span class="label">Remaining:</span>
                <span class="value">${this.formatNumber(remaining)}</span>
            </div>
        `;
    }

    renderAssemblyTable(assemblyData, orderedStations) {
        this.initializeTable(orderedStations);
        this.renderTableBody(assemblyData, orderedStations);
        this.updateStationSummaries(assemblyData);
    }

    initializeTable(orderedStations) {
        const headerRow = this.tableContainer.querySelector('thead tr');
        if (!headerRow) return;

        // Clear existing headers after the static columns
        const existingHeaders = Array.from(headerRow.children);
        const staticHeadersCount = 6;
    
        existingHeaders.slice(staticHeadersCount).forEach(header => 
            header.remove()
        );
    
        // Add station headers
        orderedStations.forEach(station => {
            const th = document.createElement('th');
            th.innerHTML = `${station}<div class="station-summary">0/0</div>`;
            headerRow.appendChild(th);
        });
    }

    renderTableBody(assemblyData, orderedStations) {
        const tbody = this.tableContainer.querySelector('tbody');
        tbody.innerHTML = assemblyData.map(assembly => 
            this.renderAssemblyRow(assembly, orderedStations)
        ).join('');
    }

    renderAssemblyRow(assembly, orderedStations) {
        const isCompleted = this.workPackageData.checkCompletion(assembly.Stations);
        const isOnHold = (assembly.ReleasedToFab != 1);
        const stationHours = this.workPackageData.calculateStationHours(
            assembly.RouteName, 
            assembly.TotalEstimatedManHours
        );

        const stationCells = orderedStations.map(station => {
            const stationData = this.workPackageData.getStationData(assembly, station);
            const statusClass = this.workPackageData.getStationStatus(stationData);
            const isStationCompleted = stationData && stationData.completed === stationData.total;

            return `
                <td class="${statusClass} ${isStationCompleted ? 'completed-station' : ''}" 
                    style="${isStationCompleted ? 'background-color: #e6ffe6;' : ''}">
                    ${this.renderStationCell(stationData)}
                </td>
            `;
        }).join('');

        return `
            <tr class="${isCompleted ? 'completed-row' : ''} ${isOnHold ? 'hold-row' : ''}">
                <td title="${isOnHold ? 'On Hold' : ''}">${assembly.JobNumber}<br>${assembly.RouteName || ''}</td>
                <td>${assembly.SequenceDescription} [${assembly.LotNumber}]<br>${assembly.MainMark || ''}</td>
                <td>${assembly.WorkPackageNumber}</td>
                <td>${assembly.AssemblyQuantity}</td>
                <td>${assembly.NetAssemblyWeightEach.toFixed(1)} / ${assembly.TotalNetWeight.toFixed(1)}</td>
                <td>${this.formatNumber(assembly.AssemblyManHoursEach)} / ${this.formatNumber(assembly.TotalEstimatedManHours)}</td>
                ${stationCells}
            </tr>
        `;
    }

    renderStationCell(stationData) {
        if (!stationData) return '-';
        return `
            <div>${stationData.completion}</div>
            <div style="font-size: 0.875rem; color: #4b5563;">
                ${this.formatNumber(stationData.completedHours)} / ${this.formatNumber(stationData.totalHours)}
            </div>
        `;
    }

    updateStationSummaries(assemblyData) {
        const totals = this.workPackageData.calculateStationTotals();
        const headers = this.tableContainer.querySelectorAll('thead th');
        const staticHeadersCount = 6;

        Object.entries(totals).forEach(([station, stationTotal], index) => {
            const headerCell = headers[staticHeadersCount + index];
            const summary = headerCell.querySelector('.station-summary');

            headerCell.classList.remove('col-empty', 'col-complete');

            if (summary) {
                summary.textContent = `${this.formatNumber(stationTotal.completed)} / ${this.formatNumber(stationTotal.total)}`;
                
                if (stationTotal.total === 0) {
                    headerCell.classList.add('col-empty');
                } else if (stationTotal.completed === stationTotal.total) {
                    headerCell.classList.add('col-complete');
                }
            }
        });
    }

    showError(message) {
        this.tableContainer.querySelector('tbody').innerHTML = `
            <tr>
                <td colspan="${6 + this.workPackageData.getOrderedStations().length}" 
                    style="text-align: center; color: #dc2626;">
                    ${message}
                </td>
            </tr>
        `;
    }

    showLoading() {
        if (!this.tableContainer) return;
        
        const tbody = this.tableContainer.querySelector('tbody');
        if (!tbody) return;
        
        const colspan = 6 + (this.workPackageData?.getOrderedStations()?.length || 0);
        tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" style="text-align: center;">
                    Loading...
                </td>
            </tr>
        `;
    }

    // Helper methods
    formatNumber(num) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(num);
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    isOverdue(dueDate) {
        return new Date(dueDate) < new Date();
    }

    // Public methods
    setWorkPackageData(workPackageData) {
        this.workPackageData = workPackageData;
    }
}