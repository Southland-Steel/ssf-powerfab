class WorkPackageData {
    constructor(workPackages, options = {}) {

        this.workPackages = workPackages;
        this.orderedStations = options.orderedStations || ['IFA', 'IFF', 'CUT', 'FIT', 'WELD', 'FINAL QC'];
        this.apiUrl = 'ajax_ssf_workpackage_assembly_station_status.php';
        this.data = [];
        this.table = document.getElementById('projectTable');
        this.initializeTable();
        this.totals = {
            totalHours: 0,
            completedHours: 0,
            remainingHours: 0,
            totalWeight: 0,
            completedWeight: 0,
            remainingWeight: 0,
            percentComplete: 0
        };
    }

    calculateTotals() {
        this.totals = {
            totalHours: 0,
            completedHours: 0,
            remainingHours: 0,
            totalWeight: 0,
            completedWeight: 0,
            remainingWeight: 0,
            percentComplete: 0
        };

        this.data.forEach(assembly => {
            const totalHours = parseFloat(assembly.TotalHours) || 0;
            const weight = parseFloat(assembly.Weight) || 0;
            const completionPercent = parseFloat(assembly.CompletionPercent) || 0;

            this.totals.totalHours += totalHours;
            this.totals.totalWeight += weight;
            
            this.totals.completedHours += (totalHours * (completionPercent / 100));
            this.totals.completedWeight += (weight * (completionPercent / 100));
        });

        // Calculate remaining values
        this.totals.remainingHours = this.totals.totalHours - this.totals.completedHours;
        this.totals.remainingWeight = this.totals.totalWeight - this.totals.completedWeight;
        this.totals.percentComplete = this.totals.totalHours > 0 
            ? (this.totals.completedHours / this.totals.totalHours * 100) 
            : 0;

        return this.totals;
    }

    initializeTable() {
        const headerRow = this.table.querySelector('thead tr');
        if (!headerRow) return;
    
        // Clear existing headers after the static columns
        const existingHeaders = Array.from(headerRow.children);
        const staticHeadersCount = 6;
    
        existingHeaders.slice(staticHeadersCount).forEach(header => 
            header.remove()
        );
    
        // Add station headers
        this.orderedStations.forEach(station => {
            const th = document.createElement('th');
            th.innerHTML = `${station}<div class="station-summary">0/0</div>`;
            headerRow.appendChild(th);
        });
    
        // Show loading in tbody
        this.table.querySelector('tbody').innerHTML = `
            <tr><td colspan="${staticHeadersCount + this.orderedStations.length}" 
            style="text-align: center;">Loading...</td></tr>`;
    }

    calculateStationTotals() {
        const totals = {};

        this.orderedStations.forEach(station => {
            totals[station] = {
                completed: 0,
                total: 0
            };
        });

        this.data.forEach(assembly => {
            // Calculate hours distribution for this assembly
            const totalHours = assembly.TotalEstimatedManHours;
            const stationHours = this.calculateStationHours(assembly.RouteName, totalHours);

            assembly.Stations.forEach(station => {
                const stationName = station.StationDescription.toUpperCase();
                if (this.orderedStations.includes(stationName)) {
                    // Use the calculated hours instead of quantities
                    const stationTotalHours = stationHours[stationName] || 0;
                    const completionRatio = station.StationQuantityCompleted / station.StationTotalQuantity;
                    
                    totals[stationName].completed += stationTotalHours * completionRatio;
                    totals[stationName].total += stationTotalHours;
                }
            });
        });

        return totals;
    }

    loadData(wpid) {
        fetch(this.apiUrl + '?workpackageid='+wpid)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                data.sort((a, b) => {
                    const aCompleted = this.checkCompletion(a.Stations);
                    const bCompleted = this.checkCompletion(b.Stations);
                    if (aCompleted === bCompleted) return 0;
                    return aCompleted ? 1 : -1; // 1 means a goes after b, -1 means a goes before b
                });
                this.data = data;
                this.renderData();
            })
            .catch(error => {
                this.handleError(error);
            });
    }



    handleError(error) {
        console.error('Error loading data:', error);
        this.table.querySelector('tbody').innerHTML = `
            <tr>
                <td colspan="${6 + this.orderedStations.length}" style="text-align: center; color: #dc2626;">
                    Error loading data. Please try again later.
                </td>
            </tr>
        `;
    }



    getStationData(assembly, stationName) {
        const station = assembly.Stations.find(s =>
            s.StationDescription.toUpperCase() === stationName
        );

        if (!station) return null;

        const assemblyDefinition = this.calculateStationHours(assembly.RouteName, assembly.TotalEstimatedManHours);
        const assemblyDefinitionEach = this.calculateStationHours(assembly.RouteName, assembly.AssemblyManHoursEach);


        const stationHours = assemblyDefinition[stationName] || 0;
        const stationHoursEach = assemblyDefinitionEach[stationName] || 0;

        return {
            completion: `${station.StationQuantityCompleted}/${station.StationTotalQuantity}`,
            percentage: ((station.StationQuantityCompleted / station.StationTotalQuantity) * 100),
            hours: station.StationActualHours ?
                `HRS: ${Math.round(station.StationActualHours*10)/10}` : '',
            completed: station.StationQuantityCompleted,
            total: station.StationTotalQuantity,
            completedHours: stationHoursEach * station.StationQuantityCompleted,
            totalHours: stationHoursEach * station.StationTotalQuantity
        };
    }

    calculateStationHours(route, totalHours) {
        switch (route) {
            case '04: SSF CUT & FAB':
                return {
                    'FIT': totalHours * 0.38,
                    'WELD': totalHours * 0.58,
                    'FINAL QC': totalHours * 0.04,
                    'CUT': totalHours * 0.0001,
                    'IFF': totalHours * 0.0001,
                    'IFA': totalHours * 0.0001
                };
            default:
                return {
                    'FIT': 0,
                    'WELD': 0,
                    'FINAL QC': totalHours
                };
        }
    }

    getStationStatus(stationData) {
        if (!stationData) return 'status-na';
        const percentage = parseFloat(stationData.percentage);
        if (percentage === 100) return 'status-complete';
        if (percentage > 0) return 'status-partial';
        return 'status-notstarted';
    }

    renderData() {
        
        const tbody = document.querySelector('#projectTable tbody');
        tbody.innerHTML = this.data.map(assembly => {
            const isCompleted = this.checkCompletion(assembly.Stations);
            const isOnHold = (assembly.ReleasedToFab != 1);
            const totalHours = assembly.TotalEstimatedManHours;
            const stationHours = this.calculateStationHours(assembly.RouteName, totalHours);

            const stationCells = this.orderedStations.map(station => {
                const stationData = this.getStationData(assembly, station);
                const statusClass = this.getStationStatus(stationData);
                const stationTotalHours = stationHours[station] || 0;
                const isStationCompleted = stationData && stationData.completed === stationData.total;

                return `
                    <td class="${statusClass} ${isStationCompleted ? 'completed-station' : ''}" 
                        style="${isStationCompleted ? 'background-color: #e6ffe6;' : ''}">
                        ${stationData ? `
                            <div>${stationData.completion}</div>
                            <div style="font-size: 0.875rem; color: #4b5563;">${this.formatNumber(stationData.completedHours)} / ${this.formatNumber(stationData.totalHours)}</div>
                        ` : '-'}
                    </td>
                `;
            }).join('');

            const seqLot = assembly.SequenceDescription + ' [' + assembly.LotNumber + ']';

            return `
                <tr class="${isCompleted ? 'completed-row' : ''} ${isOnHold ? 'hold-row' : ''}">
                    <td title="${isOnHold ? 'On Hold' : ''}">${assembly.JobNumber}<br>${assembly.RouteName || ''}</td>
                    <td>${seqLot}<br>${assembly.MainMark || ''}</td>
                    <td>${assembly.WorkPackageNumber}</td>
                    <td>${assembly.AssemblyQuantity}</td>
                    <td>${assembly.NetAssemblyWeightEach.toFixed(1)} / ${assembly.TotalNetWeight.toFixed(1)}</td>
                    <td>${this.formatNumber(assembly.AssemblyManHoursEach)} / ${this.formatNumber(assembly.TotalEstimatedManHours)}</td>
                    ${stationCells}
                </tr>
            `;
        }).join('');

        // After rendering the table data, update the summaries
        const totals = this.calculateStationTotals();
        const headers = this.table.querySelectorAll('thead th');
        const staticHeadersCount = 6;

        this.orderedStations.forEach((station, index) => {
            const headerCell = headers[staticHeadersCount + index];
            const summary = headerCell.querySelector('.station-summary');
            const stationTotal = totals[station];

            // Remove existing status classes
            headerCell.classList.remove('col-empty', 'col-complete');

            if (summary && stationTotal) {
                summary.textContent = `${stationTotal.completed < 1 ? stationTotal.completed.toFixed(2) : Math.round(stationTotal.completed*100)/100} / ${stationTotal.total < 1 ? stationTotal.total.toFixed(2) : Math.round(stationTotal.total*100)/100}`;

                // Add appropriate class based on totals
                if (stationTotal.total === 0) {
                    headerCell.classList.add('col-empty');
                } else if (stationTotal.completed === stationTotal.total) {
                    headerCell.classList.add('col-complete');
                }
            }
        });
    }

    formatNumber(num) {
        if (num >= 1000) {
            return Math.round(num);
        } else if (num >= 100) {
            return (Math.round(num * 10) / 10).toFixed(1);
        } else {
            return (Math.round(num * 100) / 100).toFixed(2);
        }
    }

    checkCompletion(stations) {

        const lastRelevantStation = [...stations].reverse().find(station =>
            station.StationDescription === "FINAL QC"
        );

        return lastRelevantStation &&
            lastRelevantStation.StationQuantityCompleted === lastRelevantStation.StationTotalQuantity;
    }

    init() {
        this.loadData();
        return this;
    }

    refresh() {
        this.initializeTable();
        this.loadData();
    }

}