// Main Application class
class App {
    constructor() {
        this.dataManager = new DataManager();
        this.filterManager = new FilterManager();
        this.tableRenderer = new TableRenderer();
        this.filterRenderer = new FilterRenderer();
        this.summaryRenderer = new SummaryRenderer();
        this.modalManager = new ModalManager();
        this.jsonViewer = new JsonViewer();
        this.loadingOverlay = new LoadingOverlay();
    }

    init() {
        const self = this;

        // Initialize modals
        this.modalManager.init();

        // Setup week buttons
        let weeklist = [];
        PHP_DATA.weeks.forEach(week => {
            weeklist.push(`
                <button class="week-btn ${week == PHP_DATA.currentWeek ? 'active' : ''}" 
                        onclick="app.loadProjectData('${week}')">
                    ${week}
                </button>`);
        });

        $('#activeFabWorkpackages').html(`<strong>Work Weeks:</strong> ${weeklist.join(' ')}`);

        // Setup week button click handler
        $(document).on('click', '.week-btn', function() {
            $('.week-btn').removeClass('active');
            $(this).addClass('active');
        });

        // Setup sticky header
        const header = document.querySelector("#projectTable thead");
        if (header) {
            const sticky = header.offsetTop;
            window.onscroll = function() {
                if (window.pageYOffset > sticky) {
                    header.classList.add("sticky");
                } else {
                    header.classList.remove("sticky");
                }
            };
        }

        // Load initial data
        this.loadProjectData(PHP_DATA.currentWeek);
    }

    loadProjectData(workweek) {
        this.dataManager.loadProjectData(workweek);
    }

    // Utility function for debugging route/category combinations
    analyzeRouteCategoryStations() {
        const projectData = this.dataManager.getProjectData();
        const combinations = new Set();

        projectData.forEach(item => {
            const route = item.RouteName || 'undefined';
            const category = item.Category || 'undefined';

            const currentDist = {};
            const hours = Calculator.calculateStationHours(route, category, 100);
            if (hours) {
                Object.entries(hours).forEach(([station, value]) => {
                    currentDist[station] = value;
                });
            }

            item.Stations.forEach(station => {
                const stationName = station.StationDescription;
                const percentage = currentDist[stationName] || '';
                combinations.add(`${route}\t${category}\t${stationName}\t${percentage}`);
            });
        });

        const output = 'Route\tCategory\tStation\tPercentage\n' +
            Array.from(combinations)
                .sort()
                .join('\n');

        const tempTextArea = document.createElement('textarea');
        tempTextArea.value = output;
        document.body.appendChild(tempTextArea);
        tempTextArea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextArea);

        console.log('Data has been copied to clipboard. Here it is for reference:');
        console.log(output);

        return 'Data copied to clipboard!';
    }
}

// Initialize the application when DOM is ready
const app = new App();
$(document).ready(function() {
    app.init();
});