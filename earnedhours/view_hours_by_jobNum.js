class JobNumberWeeklyHours {
    constructor() {
        this.stations = ['Cut', 'Fit', 'FinalQC'];
        this.data = [];
    }

    getWeekStart(dateString) {
        // Parse the date string manually to avoid timezone issues
        const [year, month, day] = dateString.split('-').map(Number);
        const date = new Date(year, month - 1, day); // month is 0-indexed
        
        const dayOfWeek = date.getDay(); // 0 = Sunday, 1 = Monday, etc.
        const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Convert to Monday-based week
        
        const weekStart = new Date(date);
        weekStart.setDate(date.getDate() - daysToSubtract);
        
        return weekStart.toISOString().split('T')[0];
    }

    getWeekOf(dateString) {
        const weekStart = this.getWeekStart(dateString);
        const [year, month, day] = weekStart.split('-').map(Number);
        
        const monthStr = String(month).padStart(2, '0');
        const dayStr = String(day).padStart(2, '0');
        
        // Debug log to see what's happening
        console.log(`Date: ${dateString}, Week Start: ${weekStart}, Week Of: Week of ${monthStr} ${dayStr}`);
        
        return `Week of ${monthStr} ${dayStr}`;
    }

    formatActualDate(dateString) {
        // Already in YYYY-MM-DD format, just return as is
        return dateString;
    }

    formatWeekDisplay(weekStart) {
        const date = new Date(weekStart);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return `Week of ${date.toLocaleDateString('en-US', options)}`;
    }

    getJobNumberWeeklySummary(data) {
        const weeklyJobData = {};

        data.forEach(item => {
            if (!item.DateCompleted || !item.StationName || !item.JobNumber) return;

            const date = new Date(item.DateCompleted);
            const firstDayOfWeek = new Date(date);
            firstDayOfWeek.setDate(date.getDate() - date.getDay());
            const weekKey = firstDayOfWeek.toISOString().split('T')[0];
            const jobNumber = item.JobNumber.toString();
            const stationName = this.normalizeStationName(item.StationName);

            if (!weeklyJobData[weekKey]) {
                weeklyJobData[weekKey] = {};
            }

            if (!weeklyJobData[weekKey][jobNumber]) {
                weeklyJobData[weekKey][jobNumber] = {};
                this.stations.forEach(station => {
                    weeklyJobData[weekKey][jobNumber][station] = 0;
                });
                weeklyJobData[weekKey][jobNumber]['Total'] = 0;
            }

            if (this.stations.includes(stationName)) {
                // Use CalculatedHours which should be the same across all station types
                let hours = parseFloat(item.CalculatedHours || 0);
                
                weeklyJobData[weekKey][jobNumber][stationName] += hours;
                weeklyJobData[weekKey][jobNumber]['Total'] += hours;
            }
        });

        return weeklyJobData;
    }

    normalizeStationName(stationName) {
        const normalized = stationName.toLowerCase().trim();
        if (normalized === 'cut') return 'Cut';
        if (normalized === 'fit') return 'Fit';
        if (normalized === 'final qc' || normalized === 'finalqc') return 'FinalQC';
        return stationName;
    }

    getJobNumberDailySummary(data) {
        const dailyJobData = {};

        data.forEach(item => {
            if (!item.DateCompleted || !item.StationName || !item.JobNumber) return;

            const dateKey = item.DateCompleted; // Use the actual date instead of week start
            const jobNumber = item.JobNumber.toString();
            const stationName = this.normalizeStationName(item.StationName);

            if (!dailyJobData[dateKey]) {
                dailyJobData[dateKey] = {};
            }

            if (!dailyJobData[dateKey][jobNumber]) {
                dailyJobData[dateKey][jobNumber] = {};
                this.stations.forEach(station => {
                    dailyJobData[dateKey][jobNumber][station] = 0;
                });
                dailyJobData[dateKey][jobNumber]['Total'] = 0;
            }

            if (this.stations.includes(stationName)) {
                let hours = parseFloat(item.CalculatedHours || 0);
                
                dailyJobData[dateKey][jobNumber][stationName] += hours;
                dailyJobData[dateKey][jobNumber]['Total'] += hours;
            }
        });

        return dailyJobData;
    }

    downloadJobNumberWeeklySummary(format = 'csv') {
        const dailyJobData = this.getJobNumberDailySummary(this.data);

        if (format === 'csv') {
            this.downloadJobNumberCSV(dailyJobData);
        } else {
            this.downloadJobNumberJSON(dailyJobData);
        }
    }

    downloadJobNumberCSV(dailyJobData) {
        const rows = [];
        
        const headers = ['Week', 'Date', 'Job Number', ...this.stations, 'Total'];
        rows.push(headers);

        const sortedDates = Object.keys(dailyJobData).sort((a, b) => new Date(b) - new Date(a));

        sortedDates.forEach(dateKey => {
            const weekOf = this.getWeekOf(dateKey);
            const actualDate = this.formatActualDate(dateKey);
            const jobData = dailyJobData[dateKey];

            const sortedJobs = Object.keys(jobData).sort((a, b) => {
                const numA = parseInt(a) || 0;
                const numB = parseInt(b) || 0;
                return numA - numB;
            });

            // Add job rows for this date
            sortedJobs.forEach(jobNumber => {
                const data = jobData[jobNumber];
                const row = [
                    weekOf,
                    actualDate,
                    jobNumber,
                    ...this.stations.map(station => data[station].toFixed(2)),
                    data['Total'].toFixed(2)
                ];
                rows.push(row);
            });
        });

        // Remove the last empty row if it exists
        if (rows.length > 1 && rows[rows.length - 1].every(cell => cell === '')) {
            rows.pop();
        }

        const csvContent = rows.map(row => 
            row.map(cell => {
                const cellStr = cell.toString();
                if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                    return `"${cellStr.replace(/"/g, '""')}"`;
                }
                return cellStr;
            }).join(',')
        ).join('\n');

        this.downloadFile(
            csvContent,
            `job_daily_hours_details.csv`,
            'text/csv'
        );
    }

    downloadJobNumberJSON(dailyJobData) {
        const sortedData = {};
        Object.keys(dailyJobData)
            .sort((a, b) => new Date(b) - new Date(a))
            .forEach(dateKey => {
                const weekOf = this.getWeekOf(dateKey);
                const actualDate = this.formatActualDate(dateKey);
                
                if (!sortedData[weekOf]) {
                    sortedData[weekOf] = {};
                }
                
                sortedData[weekOf][actualDate] = {};
                
                const sortedJobs = Object.keys(dailyJobData[dateKey]).sort((a, b) => {
                    const numA = parseInt(a) || 0;
                    const numB = parseInt(b) || 0;
                    return numA - numB;
                });

                sortedJobs.forEach(jobNumber => {
                    sortedData[weekOf][actualDate][jobNumber] = dailyJobData[dateKey][jobNumber];
                });
            });

        this.downloadFile(
            JSON.stringify(sortedData, null, 2),
            `job_daily_hours_${new Date().toISOString().split('T')[0]}.json`,
            'application/json'
        );
    }

    getSummaryStats() {
        const dailyJobData = this.getJobNumberDailySummary(this.data);
        const stats = {
            totalDates: Object.keys(dailyJobData).length,
            totalJobs: new Set(),
            totalHours: 0,
            stationHours: {}
        };

        this.stations.forEach(station => {
            stats.stationHours[station] = 0;
        });

        Object.values(dailyJobData).forEach(dateData => {
            Object.entries(dateData).forEach(([jobNumber, jobData]) => {
                stats.totalJobs.add(jobNumber);
                stats.totalHours += jobData.Total;
                
                this.stations.forEach(station => {
                    stats.stationHours[station] += jobData[station];
                });
            });
        });

        stats.totalJobs = stats.totalJobs.size;
        return stats;
    }

    generateSummaryReport() {
        const stats = this.getSummaryStats();

        let report = `
        <div class="summary-report">
            <h4>Job Number Daily Hours Summary</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>${stats.totalDates}</h5>
                            <p>Total Dates</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>${stats.totalJobs}</h5>
                            <p>Unique Jobs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>${stats.totalHours.toFixed(2)}</h5>
                            <p>Total Hours</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5>${(stats.totalHours / Math.max(stats.totalDates, 1)).toFixed(2)}</h5>
                            <p>Avg Hours/Date</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <h6>Hours by Station:</h6>
                <ul class="list-unstyled">
                    ${this.stations.map(station => 
                        `<li><strong>${station}:</strong> ${stats.stationHours[station].toFixed(2)} hours</li>`
                    ).join('')}
                </ul>
            </div>
        </div>
    `;

        return report;
    }

    downloadFile(content, filename, type) {
        const blob = new Blob([content], { type });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    }

    setData(data) {
        this.data = data || [];
    }

    getData() {
        return this.data;
    }

    clearData() {
        this.data = [];
    }
}