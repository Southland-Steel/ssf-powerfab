class WorkPackages {
    constructor(containerSelector) {
        // DOM elements
        this.container = document.querySelector(containerSelector);
        
        if (!this.container) {
            console.error(`WorkPackages container '${containerSelector}' not found`);
            return;
        }
        
        // Initialize container and state
        this.initializeContainer();
        this.currentWorkPackagesData = null;
        this.selectedWorkPackage = null;
        this.onWorkPackageSelected = null;
        
        // Bind event listeners
        this.attachEventListeners();
    }

    initializeContainer() {
        this.container.innerHTML = `
            <div class="workpackage-pills"></div>
            <div class="workpackage-detail">
                <div class="workpackage-container"></div>
                <div class="workpackage-statistics"></div>
            </div>
        `;
    }

    attachEventListeners() {
        this.container.addEventListener('click', (event) => {
            const wpItem = event.target.closest('.wp-item');
            if (wpItem) {
                const wpNumber = wpItem.dataset.wpNumber;
                const wpId = wpItem.dataset.wpid;
                if (wpNumber) {
                    this.handleWorkPackageClick(wpNumber, wpId);
                }
            }
        });
    }

    async loadWorkPackageData(weekNumber) {
        try {
            const response = await fetch(`ajax_WorkPackages.php?week=${weekNumber}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error);
            }

            this.currentWorkPackagesData = result.data;
            this.renderWorkPackagePills(result.data);
            
            // Select first work package by default
            if (result.data.length > 0) {
                this.selectWorkPackage(result.data[0].WorkPackageNumber);
            }
        } catch (error) {
            console.error('Error loading work packages:', error);
            this.showError('Error loading work packages');
        }
    }

    renderWorkPackagePills(workPackages) {
        const container = this.container.querySelector('.workpackage-pills');

        if (!workPackages || !workPackages.length) {
            container.innerHTML = '<div class="no-data">No work packages found for this week</div>';
            return;
        }

        const pillsHTML = workPackages.map(wp => {
            const statusClasses = [
                wp.OnHold ? 'on-hold' : '',
                wp.ReleasedToFab ? 'released' : 'not-released',
                this.isOverdue(wp.DueDate) ? 'overdue' : ''
            ].filter(Boolean).join(' ');

            return `
                <div class="wp-item" data-wp-number="${wp.WorkPackageNumber}" data-wpid="${wp.WorkPackageID}">
                    <div class="wp-link ${statusClasses}" title="${statusClasses}">
                        <div class="d-flex flex-column align-items-center">
                            <span class="fs-6 fw-bold">${wp.WorkPackageNumber}</span>
                            <span class="text-muted small">${wp.Hours} hrs / ${wp.Weight} lbs</span>
                            <span class="small job-number">${wp.JobNumber}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = pillsHTML;
    }

    handleWorkPackageClick(wpNumber, wpId) {
        this.selectWorkPackage(wpNumber);
        
        // Notify listeners
        if (this.onWorkPackageSelected) {
            this.onWorkPackageSelected(wpNumber, wpId);
        }
    }

    selectWorkPackage(wpNumber) {
        const selectedWP = this.currentWorkPackagesData.find(wp => 
            wp.WorkPackageNumber === wpNumber
        );
    
        if (selectedWP) {
            this.selectedWorkPackage = selectedWP;
            
            // Update UI to show selected state
            this.container.querySelectorAll('.wp-link').forEach(el => 
                el.classList.remove('selected')
            );
            
            const selectedPill = this.container.querySelector(`[data-wp-number="${wpNumber}"] .wp-link`);
            if (selectedPill) {
                selectedPill.classList.add('selected');
            }
        }
    }

    resetWorkPackageDetail() {
        const detailContainer = this.container.querySelector('.workpackage-detail>.workpackage-container');
        if (detailContainer) {
            detailContainer.innerHTML = '';
        }
        this.selectedWorkPackage = null;
    }

    // Helper methods
    isOverdue(dueDate) {
        return new Date(dueDate) < new Date();
    }

    showError(message) {
        const container = this.container.querySelector('.workpackage-pills');
        container.innerHTML = `<div class="error-message">${message}</div>`;
    }

    // Public methods for other classes to interact with
    getSelectedWorkPackage() {
        return this.selectedWorkPackage;
    }

    setWorkPackageSelectedCallback(callback) {
        this.onWorkPackageSelected = callback;
    }
}