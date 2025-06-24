class WeeklyHours {
    constructor() {
        this.stations = ['Cut', 'Fit', 'FinalQC'];
        this.data = [];
    }

    getWeeklySummary(data) {
        const weeklyTotals = {};

        data.forEach(item => {
            if (!item.DateCompleted || !item.StationName) return;

            const date = new Date(item.DateCompleted);
            const firstDayOfWeek = new Date(date);
            firstDayOfWeek.setDate(date.getDate() - date.getDay());
            const weekKey = firstDayOfWeek.toISOString().split('T')[0];

            if (!weeklyTotals[weekKey]) {
                weeklyTotals[weekKey] = {};
                this.stations.forEach(station => {
                    weeklyTotals[weekKey][station] = 0;
                });
                weeklyTotals[weekKey]['Total'] = 0;
            }

            if (this.stations.includes(item.StationName)) {
                const hours = parseFloat(item.CalculatedHours) || 0;
                weeklyTotals[weekKey][item.StationName] += hours;
                weeklyTotals[weekKey]['Total'] += hours;
            }
        });

        return weeklyTotals;
    }

    downloadWeeklySummary(format) {
        const weeklyData = this.getWeeklySummary(this.data);

        if (format === 'csv') {
            const headers = ['Week Start', ...this.stations, 'Total'];
            const rows = [headers];

            Object.entries(weeklyData)
                .sort((a, b) => new Date(b[0]) - new Date(a[0])) // Sort by week descending
                .forEach(([week, data]) => {
                    const row = [
                        week,
                        ...this.stations.map(station => data[station].toFixed(2)),
                        data['Total'].toFixed(2)
                    ];
                    rows.push(row);
                });

            this.downloadFile(
                rows.map(row => row.join(',')).join('\n'),
                'ssf_weekly_summary.csv',
                'text/csv'
            );
        } else {
            // Sort the data by week for JSON too
            const sortedData = {};
            Object.entries(weeklyData)
                .sort((a, b) => new Date(b[0]) - new Date(a[0]))
                .forEach(([week, data]) => {
                    sortedData[week] = data;
                });

            this.downloadFile(
                JSON.stringify(sortedData, null, 2),
                'ssf_weekly_summary.json',
                'application/json'
            );
        }
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
        this.data = data;
    }
}