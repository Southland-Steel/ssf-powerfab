class WorkPackages {
    constructor() {
        this.container = document.querySelector('WorkPackages');
        this.container.innerHTML = `
                <div class="workpackage-pills"></div>
                <div class="workpackage-detail">
                    <div class="workpackage-container"></div>
                    <div class="workpackage-statistics"></div>
                </div>
            `;
        this.currentWorkPackagesData = null;
        this.addEventListeners();
    }

    initWorkPackageData(wpid) {
        if (!this.workPackageData) {
            this.workPackageData = new WorkPackageData();
        }
        this.workPackageData.loadData(wpid);
    }

    handleWorkPackageClick(wpNumber, wpid) {
        this.selectWorkPackage(wpNumber);
        this.initWorkPackageData(wpid);
    }

    async loadWorkPackageData(week) {
        try {
            const response = await fetch(`ajax_WorkPackages.php?week=${week}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error);
            }

            this.currentWorkPackagesData = result.data;
            this.renderWorkPackagePills(result.data);
        } catch (error) {
            console.error('Error loading work packages:', error);
            this.showError('Error loading work packages');
        }
    }

    getStationStatus(stationData) {
        if (!stationData) return 'status-na';
        const percentage = parseFloat(stationData.percentage);
        if (percentage === 100) return 'status-complete';
        if (percentage > 0) return 'status-partial';
        return 'status-notstarted';
    }

    renderWorkPackagePills(workPackages) {
        const container = this.container.querySelector('.workpackage-pills');

        if (!workPackages || !workPackages.length) {
            container.innerHTML = '<div class="no-data">No work packages found for this week</div>';
            return;
        }

        const navPillHTML = workPackages.map(wp => {
            const statusClasses = [
                wp.OnHold ? 'on-hold' : '',
                wp.ReleasedToFab ? 'released' : 'not-released',
                this.isOverdue(wp.DueDate) ? 'overdue' : ''
            ].filter(Boolean).join(' ');

            return `
            <div class="wp-item" data-wp-number="${wp.WorkPackageNumber}" data-wpid="${wp.WorkPackageID}" >
                <div class="wp-link active rounded ${statusClasses}" title="${statusClasses}">
                    <div class="d-flex flex-column align-items-center" style="position: relative">
                        <span class="fs-6 fw-bold" style="position: relative; top: -7px">${wp.WorkPackageNumber}</span>
                        <span class="text-muted small" style="position: relative; top: -11px" >${wp.Hours} hrs / ${wp.Weight} lbs</span>
                        <span class="small" style="position: absolute; bottom: -5px; right:0" >${wp.JobNumber}</span>
                    </div>
                </div>
            </div>
        `;
        }).join('');

        container.innerHTML = navPillHTML;

        // Select the first work package after rendering
        if (workPackages.length > 0) {
            //this.selectWorkPackage(workPackages[0].WorkPackageNumber);
        }
    }

    addEventListeners() {
        this.container.addEventListener('click', (e) => {
            const wpItem = e.target.closest('.wp-item');
            if (wpItem) {
                const wpNumber = wpItem.dataset.wpNumber;
                const wpid = wpItem.dataset.wpid;
                if (wpNumber) {
                    this.handleWorkPackageClick(wpNumber, wpid);
                }
            }
        });
    }

    selectWorkPackage(wpNumber) {
        const selectedWP = this.currentWorkPackagesData.find(wp => 
            wp.WorkPackageNumber === wpNumber
        );
    
        if (selectedWP) {
            this.$selectedWorkPackage = selectedWP;
            this.renderWorkPackageDetail(selectedWP);
            
            // Toggle selected class
            this.container.querySelectorAll('.wp-link').forEach(el => 
                el.classList.remove('selected')
            );
            
            this.container.querySelector(`[data-wp-number="${wpNumber}"] .wp-link`)
                ?.classList.add('selected');
        }
    }

    renderWorkPackageDetail(workPackage = this.currentWorkPackagesData[0]) {
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
                </div>
            </div>
        `;
        const totals = this.currentWorkPackagesData.calculateTotals();
        this.renderWorkPackageStatistics(workPackage,totals);
    }

    // render how many hours, weight, and work packages are in the selected work package and the total remaining
    renderWorkPackageStatistics(workPackage, totals) {
        const formatNumber = (num) => {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(num);
        };
    
        const statisticsContainer = this.container.querySelector('.workpackage-statistics');
        statisticsContainer.innerHTML = `
            <div class="statistics-group">
                <div class="statistics-item">
                    <span class="label">Total Hours:</span>
                    <span class="value">${formatNumber(totals.totalHours)}</span>
                    <span class="label">Completed:</span>
                    <span class="value">${formatNumber(totals.completedHours)}</span>
                    <span class="label">Remaining:</span>
                    <span class="value">${formatNumber(totals.remainingHours)}</span>
                </div>
                <div class="statistics-item">
                    <span class="label">Total Weight:</span>
                    <span class="value">${formatNumber(totals.totalWeight)}</span>
                    <span class="label">Completed:</span>
                    <span class="value">${formatNumber(totals.completedWeight)}</span>
                    <span class="label">Remaining:</span>
                    <span class="value">${formatNumber(totals.remainingWeight)}</span>
                </div>
                <div class="statistics-item">
                    <span class="label">Percent Complete:</span>
                    <span class="value">${formatNumber(totals.percentComplete)}%</span>
                </div>
            </div>
        `;
    }

    resetWorkPackageDetail() {
        this.container.querySelector('.workpackage-detail>.workpackage-container').innerHTML = '';
    }

    isOverdue(dueDate) {
        return new Date(dueDate) < new Date();
    }

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    showError(message) {
        const container = this.container.querySelector('.workpackage-pills');
        container.innerHTML = `<div class="error-message">${message}</div>`;
    }
}