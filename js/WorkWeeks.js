class WorkWeeks {
    constructor(containerSelector, availableWeeks) {
        // DOM element and state
        this.container = document.querySelector(containerSelector);
        this.availableWeeks = availableWeeks;
        this.selectedWeek = this.getCurrentWeekNumber();
        
        // Event callbacks
        this.onWeekSelected = null;
        
        // Initialize
        this.initialize();
    }

    initialize() {
        this.renderWeekSelector();
        if (this.availableWeeks.length > 0) {
            this.selectWeek(this.selectedWeek);
        }
        this.attachEventListeners();
    }

    renderWeekSelector() {
        const weekButtons = this.availableWeeks.map(week => `
            <button class="btn btn-outline-primary week-btn" data-week="${week}">
                ${week}
            </button>
        `).join('');

        this.container.innerHTML = `
            <div class="week-selector">
                ${weekButtons}
            </div>
        `;
    }

    attachEventListeners() {
        this.container.addEventListener('click', (event) => {
            const weekButton = event.target.closest('.week-btn');
            if (weekButton) {
                const weekNumber = weekButton.dataset.week;
                this.selectWeek(weekNumber);
            }
        });
    }

    selectWeek(weekNumber) {
        // Update internal state
        this.selectedWeek = weekNumber;

        // Update UI
        const buttons = this.container.querySelectorAll('.week-btn');
        buttons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.week === weekNumber);
        });

        // Notify listeners
        if (this.onWeekSelected) {
            this.onWeekSelected(weekNumber);
        }
    }

    getCurrentWeekNumber(date = new Date()) {
        const currentDate = new Date(date);
        const firstDayOfYear = new Date(currentDate.getFullYear(), 0, 1);
        const pastDays = Math.floor((currentDate - firstDayOfYear) / (24 * 60 * 60 * 1000));
        const weekNumber = Math.ceil((pastDays + firstDayOfYear.getDay() + 1) / 7);

        const yearStr = currentDate.getFullYear().toString().slice(2);
        const weekStr = weekNumber.toString().padStart(2, '0');

        return yearStr + weekStr;
    }

    // Public methods for other classes to interact with
    getSelectedWeek() {
        return this.selectedWeek;
    }

    setWeekSelectedCallback(callback) {
        this.onWeekSelected = callback;
    }
}