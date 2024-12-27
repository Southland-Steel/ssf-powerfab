class WorkWeeks {
    constructor(containerTag, weeks) {
        this.container = document.querySelector(containerTag);
        this.weeks = weeks;
        this.workPackages = new WorkPackages();
        this.workPackageData = new WorkPackageData(this.workPackages);
        this.selectedWeek = this.getWeekNumber();
        this.init();
    }

    init() {
        this.render();
        if (this.weeks.length > 0) {
            this.selectWeek(this.selectedWeek);
        }
    }

    render() {
        const weeklist = this.weeks.map(week => `
            <button class="btn btn-outline-primary week-btn" onclick="workWeeks.selectWeek('${week}')">
                ${week}
            </button>
        `).join('');

        this.container.innerHTML = `
            <div class="week-selector">
                ${weeklist}
            </div>
        `;
    }

    selectWeek(week) {
        this.selectedWeek = week;
        this.workPackages.resetWorkPackageDetail();
        const buttons = document.querySelectorAll('.week-btn');
        buttons.forEach(btn => btn.classList.remove('active'));

        const selectedBtn = Array.from(buttons).find(btn =>
            btn.textContent.trim() === week
        );
        if (selectedBtn) {
            selectedBtn.classList.add('active');
        }
        // Notify WorkPackages class to load data for selected week
        if (this.workPackages) {
            this.workPackages.loadWorkPackageData(week);
            document.querySelector('tbody').innerHTML = '';
        }
    }

    getWeekNumber(date = new Date()) {
        const currentDate = new Date(date);
        const firstDayOfYear = new Date(currentDate.getFullYear(), 0, 1);

        const pastDays = Math.floor((currentDate - firstDayOfYear) / (24 * 60 * 60 * 1000));
        const weekNumber = Math.ceil((pastDays + firstDayOfYear.getDay() + 1) / 7);

        const yearStr = currentDate.getFullYear().toString().slice(2);
        const weekStr = weekNumber.toString().padStart(2, '0');

        return yearStr + weekStr;

    }
}