/**
 * Machine Status Modal
 * Handles the display and updating of machine status information
 */

let machineModal = null;
let currentMachine = null;
let updateInterval = null;

/**
 * Initialize the modal when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create modal instance if it exists in DOM
    const modalElement = document.getElementById('machineStatusModal');
    if (modalElement) {
        machineModal = new bootstrap.Modal(modalElement);
    }
});

/**
 * Show machine modal with current status
 */
function showMachineModal(machineName) {
    currentMachine = machineName;

    // Show modal immediately with loading state
    if (machineModal) {
        machineModal.show();
        showModalLoading();

        // Load machine status
        loadMachineStatus(machineName);

        // No auto-refresh - user can manually refresh if needed
    } else {
        alert('Modal not initialized. Please refresh the page.');
    }
}

/**
 * Load machine status from server
 */
async function loadMachineStatus(machineName) {
    try {
        const response = await fetch(`ajax/get_machine_status.php?machine=${encodeURIComponent(machineName)}`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            showModalError(data.error);
        } else {
            updateModalContent(data);
        }

    } catch (error) {
        console.error('Error loading machine status:', error);
        showModalError('Failed to load machine status. Please try again.');
    }
}

/**
 * Update modal content with machine data
 */
function updateModalContent(data) {
    // Update modal title
    const modalTitle = document.querySelector('#machineStatusModal .modal-title');
    if (modalTitle) {
        modalTitle.innerHTML = `
            <i class="fas fa-server me-2"></i>
            ${data.machine}
            <span class="machine-status-indicator ${data.machine_status} ms-2"></span>
        `;
    }

    // Build modal body content
    const modalBody = document.querySelector('#machineStatusModal .modal-body');
    if (!modalBody) return;

    const activity = data.current_activity;
    const stats = data.today_stats;

    // Format running time
    const runningTime = activity.minutes_running !== null ? formatRunningTime(activity.minutes_running) : 'Unknown';

    // Build activity description
    let activityDescription = activity.status_text;
    if (activity.type === 'P' && activity.part_mark) {
        activityDescription = `Cutting part ${activity.part_mark}`;
    } else if (activity.type === 'N' && activity.nest) {
        activityDescription = `Loading bar for nest ${activity.nest}`;
    }

    modalBody.innerHTML = `
        <!-- Current Activity Section -->
        <div class="modal-status-section">
            <h6><i class="fas fa-play-circle me-2"></i>Current Activity</h6>
            <div class="current-activity">${activityDescription}</div>
            
            <div class="activity-details">
                <div class="detail-item">
                    <div class="detail-label">Nest Program</div>
                    <div class="detail-value">${activity.nest || 'N/A'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Job Number</div>
                    <div class="detail-value">${activity.job || 'N/A'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Drawing</div>
                    <div class="detail-value">${activity.drawing || 'N/A'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Profile</div>
                    <div class="detail-value">${activity.profile || 'N/A'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Material</div>
                    <div class="detail-value">${activity.material || 'N/A'}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Operator</div>
                    <div class="detail-value">${activity.operator || 'N/A'}</div>
                </div>
            </div>
            
            <div class="mt-3 text-muted small">
                <i class="fas fa-clock me-1"></i>
                Running for ${runningTime} • Last update: ${formatDateTime(activity.last_update)}
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="modal-status-section">
            <h6><i class="fas fa-history me-2"></i>Recent Activities</h6>
            <div class="recent-activities">
                ${data.recent_activities.map(act => `
                    <div class="activity-item">
                        <div class="activity-icon type-${act.Type}">
                            ${getActivityIcon(act.Type)}
                        </div>
                        <div class="flex-grow-1">
                            <div><strong>${act.Activity}</strong> <span class="badge bg-secondary ms-1">${act.Type}</span></div>
                            <small class="text-muted">
                                ${act.Nest_Program ? `Nest: ${act.Nest_Program}` : ''}
                                ${act.Profile ? ` | Profile: ${act.Profile}` : ''}
                                ${act.Part_Mark ? ` | Part: ${act.Part_Mark}` : ''}
                            </small>
                        </div>
                        <div class="activity-time">
                            ${formatTimeAgo(act.Timestamp)}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

/**
 * Show loading state in modal
 */
function showModalLoading() {
    const modalBody = document.querySelector('#machineStatusModal .modal-body');
    if (modalBody) {
        modalBody.innerHTML = `
            <div class="modal-loading">
                <div class="loading-spinner"></div>
            </div>
        `;
    }
}

/**
 * Show error state in modal
 */
function showModalError(errorMessage) {
    const modalBody = document.querySelector('#machineStatusModal .modal-body');
    if (modalBody) {
        modalBody.innerHTML = `
            <div class="modal-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${errorMessage}</p>
                <button class="btn btn-primary btn-sm" onclick="retryLoadMachineStatus()">
                    <i class="fas fa-redo me-1"></i> Retry
                </button>
            </div>
        `;
    }
}

/**
 * Retry loading machine status
 */
function retryLoadMachineStatus() {
    if (currentMachine) {
        showModalLoading();
        loadMachineStatus(currentMachine);
    }
}

/**
 * Start auto-updating modal content
 */
function startModalUpdates() {
    stopModalUpdates(); // Clear any existing interval
    updateInterval = setInterval(() => {
        if (currentMachine) {
            loadMachineStatus(currentMachine);
        }
    }, 10000); // Update every 10 seconds
}

/**
 * Stop auto-updating modal content
 */
function stopModalUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
}

/**
 * Get icon for activity type
 */
function getActivityIcon(type) {
    switch (type) {
        case 'N':
            return '<i class="fas fa-download"></i>';
        case 'P':
            return '<i class="fas fa-cut"></i>';
        case 'A':
            return '<i class="fas fa-stop"></i>';
        default:
            return '<i class="fas fa-question"></i>';
    }
}

/**
 * Format running time from minutes
 */
function formatRunningTime(minutes) {
    if (minutes < 60) {
        return `${minutes} minutes`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}

/**
 * Format time ago
 */
function formatTimeAgo(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
    return date.toLocaleDateString();
}

/**
 * Navigate to machine details page
 */
function viewMachineDetails() {
    if (currentMachine) {
        window.location.href = `machine-details.php?machine=${encodeURIComponent(currentMachine)}`;
    }
}