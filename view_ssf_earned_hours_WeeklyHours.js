class WeeklyHoursComponent {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.stations = ['Cut','Press Break', 'Seam Welder', 'Fit', 'Weld', 'Final QC'];
        this.init();
    }

    init() {
        this.render();
        this.attachEventListeners();
    }

    render() {
        this.container.innerHTML = `
            <div class="weekly-export-controls">
                <div class="btn-group">
                    <button class="btn btn-secondary" id="exportCSV">Export Weekly Summary (CSV)</button>
                    <button class="btn btn-secondary ms-2" id="exportJSON">Export Weekly Summary (JSON)</button>
                </div>
            </div>
        `;
    }

    attachEventListeners() {
        this.container.querySelector('#exportCSV').addEventListener('click', () => this.downloadWeeklySummary('csv'));
        this.container.querySelector('#exportJSON').addEventListener('click', () => this.downloadWeeklySummary('json'));
    }

    getWeeklySummary(data) {
        const weeklyTotals = {};

        data.forEach(item => {
            if (!item.DateCompleted || !item.StationName) return;

            // Get the date and find its week
            const date = new Date(item.DateCompleted);
            const firstDayOfWeek = new Date(date);
            firstDayOfWeek.setDate(date.getDate() - date.getDay()); // Sunday is 0
            const weekKey = firstDayOfWeek.toISOString().split('T')[0];

            // Initialize week if needed
            if (!weeklyTotals[weekKey]) {
                weeklyTotals[weekKey] = {};
                this.stations.forEach(station => {
                    weeklyTotals[weekKey][station] = 0;
                });
            }

            // Add hours to appropriate station
            if (this.stations.includes(item.StationName)) {
                weeklyTotals[weekKey][item.StationName] += parseFloat(item.CalculatedHours) || 0;
            }
        });

        return weeklyTotals;
    }

    downloadWeeklySummary(format) {
        const weeklyData = this.getWeeklySummary(this.data); // Accessing the global allData

        if (format === 'csv') {
            const rows = [['Week', ...this.stations]];
            Object.entries(weeklyData).forEach(([week, data]) => {
                rows.push([week, ...this.stations.map(station => data[station].toFixed(1))]);
            });
            this.downloadFile(rows.map(row => row.join(',')).join('\n'), 'gs_weekly_summary.csv', 'text/csv');
        } else {
            this.downloadFile(JSON.stringify(weeklyData, null, 2), 'gs_weekly_summary.json', 'application/json');
        }
    }

    downloadFile(content, filename, type) {
        const blob = new Blob([content], { type });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    setData(data) {
        this.data = data;
    }
}