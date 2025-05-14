// workweeks/js/workweeks-core.js

// Global variables for data storage
let projectData = [];
let currentWorkweek = '';

// Constants
const orderedStations = ['NESTED', 'CUT', 'KIT', 'PROFIT', 'ZEMAN', 'FIT', 'WELD', 'FINAL QC'];

// Initialize the workweeks module
function initializeWorkweeks(workweek) {
    // Set current workweek and update display
    currentWorkweek = workweek;
    document.getElementById('big-text').textContent = workweek;

    // Load available workweeks and set up click handlers
    loadAvailableWorkweeks()
        .then(() => {
            // Load data for the selected workweek
            return loadProjectData(workweek);
        })
        .catch(error => {
            console.error('Error initializing workweeks:', error);
            alert('Failed to initialize the workweek data. Please try again.');
        });
}

// Load available workweeks from the server
function loadAvailableWorkweeks() {
    return fetch('ajax/get_workweeks.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const weeks = data.weeks || [];
            renderWorkweekButtons(weeks, currentWorkweek);
        });
}

// Render workweek buttons
function renderWorkweekButtons(weeks, activeWeek) {
    const container = document.getElementById('activeFabWorkpackages');
    let html = '<strong>Work Weeks:</strong> ';

    weeks.forEach(week => {
        html += `
        <button class="week-btn ${week == activeWeek ? 'active' : ''}" onclick="loadProjectData('${week}')">
            ${week}
        </button>`;
    });

    container.innerHTML = html;

    // Add event listeners for workweek buttons
    document.querySelectorAll('.week-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.week-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Load project data for a specific workweek
function loadProjectData(workweek) {
    showLoading('Loading workweek data...');
    currentWorkweek = workweek;
    document.getElementById('big-text').textContent = workweek;

    // Reset filters
    disableAllFilters();

    // Clear existing data
    projectData = [];

    // Create a promise chain to load all data sequentially
    return fetchWorkweekData(workweek)
        .then(workweekData => {
            updateLoadingMessage('Loading nested data...');
            return Promise.all([
                Promise.resolve(workweekData),
                loadAllBatches('ajax/get_workweek_nested.php', workweek)
            ]);
        })
        .then(([workweekData, nestedData]) => {
            updateLoadingMessage('Loading cut data...');
            return Promise.all([
                Promise.resolve(workweekData),
                Promise.resolve(nestedData),
                loadAllBatches('ajax/get_workweek_cut.php', workweek)
            ]);
        })
        .then(([workweekData, nestedData, cutData]) => {
            updateLoadingMessage('Loading kit data...');
            return Promise.all([
                Promise.resolve(workweekData),
                Promise.resolve(nestedData),
                Promise.resolve(cutData),
                loadAllBatches('ajax/get_workweek_kit.php', workweek)
            ]);
        })
        .then(([workweekData, nestedData, cutData, kitData]) => {
            updateLoadingMessage('Processing data...');
            return processDataInChunks(workweekData, nestedData, cutData, kitData);
        })
        .then(mergedData => {
            projectData = mergedData;

            // Build indexes and update UI
            buildFilterIndex(projectData);
            updateUI();
            hideLoading();

            return projectData;
        })
        .catch(error => {
            console.error('Error loading project data:', error);
            alert('Error loading workweek data. Please try again.');
            hideLoading();
            throw error;
        });
}

// Fetch basic workweek data
function fetchWorkweekData(workweek) {
    return fetch(`ajax/get_workweek_data.php?workweek=${workweek}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(response => {
            return Array.isArray(response.items) ? response.items : [response.items];
        });
}

// Load all batches of data from a paginated endpoint
function loadAllBatches(url, workweek, offset = 0, accumulated = []) {
    return new Promise((resolve, reject) => {
        function loadBatch() {
            fetch(`${url}?workweek=${workweek}&offset=${offset}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(response => {
                    const newData = accumulated.concat(response.items || []);
                    updateLoadingMessage(`Loaded ${newData.length} records...`);

                    if (response.hasMore) {
                        offset = response.nextOffset;
                        accumulated = newData;
                        loadBatch();
                    } else {
                        resolve(newData);
                    }
                })
                .catch(reject);
        }

        loadBatch();
    });
}

// Process data in chunks to avoid UI blocking
function processDataInChunks(workweekData, nestedData, cutData, kitData) {
    console.log('Processing data chunks:', {
        workweekLength: workweekData.length,
        nestedLength: nestedData.length,
        cutLength: cutData.length,
        kitLength: kitData.length
    });

    const chunkSize = 1000;
    const chunks = [];

    // Create chunks of workweek data
    for (let i = 0; i < workweekData.length; i += chunkSize) {
        chunks.push(workweekData.slice(i, i + chunkSize));
    }

    // Create Maps for quick lookups
    const nestedMap = createLookupMap(nestedData);
    const cutMap = createLookupMap(cutData);
    const kitMap = createLookupMap(kitData);

    // Process chunks
    return new Promise(resolve => {
        let processedData = [];
        let currentChunk = 0;

        function processNextChunk() {
            if (currentChunk >= chunks.length) {
                resolve(processedData);
                return;
            }

            setTimeout(() => {
                const chunk = chunks[currentChunk];
                const mergedChunk = chunk.map(workweekItem => {
                    const pciseqId = workweekItem.ProductionControlItemSequenceID;
                    return mergeItemData(
                        workweekItem,
                        nestedMap.get(pciseqId) || [],
                        cutMap.get(pciseqId) || [],
                        kitMap.get(pciseqId) || []
                    );
                });

                processedData = processedData.concat(mergedChunk);
                currentChunk++;
                updateLoadingMessage(`Processing data: ${Math.round((currentChunk / chunks.length) * 100)}%`);
                processNextChunk();
            }, 0);
        }

        processNextChunk();
    });
}

// Create a lookup map for quick access to pieces by ProductionControlItemSequenceID
function createLookupMap(items) {
    const map = new Map();

    items.forEach(item => {
        const key = item.ProductionControlItemSequenceID;
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(item);
    });

    return map;
}

// Merge workweek item data with nested, cut, and kit data
// Merge workweek item data with nested, cut, and kit data
function mergeItemData(workweekItem, nestedPieces, cutPieces, kitPieces) {
    // If workweekItem is undefined or doesn't have necessary properties, return a default object
    if (!workweekItem) {
        console.warn("Received undefined workweekItem in mergeItemData");
        return {
            Stations: [],
            Pieces: [...nestedPieces, ...cutPieces, ...kitPieces]
        };
    }

    let updatedStations = workweekItem.Stations || [];

    // Here's the key change: For each piece in NESTED, calculate what still needs nesting
    // by subtracting the already cut pieces
    if (nestedPieces.length > 0) {
        // Create a modified version of nestedPieces with adjusted quantities
        const adjustedNestedPieces = nestedPieces.map(nestedPiece => {
            if (!nestedPiece) return null;

            // Find the matching cut piece data, if any
            const matchingCutPiece = cutPieces.find(cp =>
                cp && nestedPiece &&
                cp.PieceMark === nestedPiece.PieceMark &&
                cp.AssemblyMark === nestedPiece.AssemblyMark);

            // Calculate remaining pieces to nest
            const totalNeeded = parseInt(nestedPiece.TotalPieceMarkQuantityNeeded) || 0;
            const alreadyCut = matchingCutPiece ? (parseInt(matchingCutPiece.QtyCut) || 0) : 0;
            const stillNeedsNesting = Math.max(0, totalNeeded - alreadyCut);

            // Return a new object with the adjusted QtyNeeded
            return {
                ...nestedPiece,
                StillNeedsNesting: stillNeedsNesting
            };
        }).filter(p => p !== null); // Remove any null entries

        updateStation('NESTED', adjustedNestedPieces, updatedStations);
    }

    // Add or update CUT station
    if (cutPieces.length > 0) {
        updateStation('CUT', cutPieces, updatedStations);
    }

    // Add or update KIT station
    if (kitPieces.length > 0) {
        updateStation('KIT', kitPieces, updatedStations);
    }

    return {
        ...workweekItem,
        Stations: updatedStations,
        Pieces: [...nestedPieces, ...cutPieces, ...kitPieces]
    };
}

// Update a station with the latest data
function updateStation(stationType, pieces, stations) {
    const totalNeeded = pieces[0]?.SequenceQuantity || 0;

    // For nesting station, use a special calculation that accounts for what's already cut
    let completed;
    if (stationType === 'NESTED') {
        // Calculate completed based on the adjusted StillNeedsNesting values
        completed = Math.min(...pieces.map(p => {
            const originalTotal = parseInt(p.TotalPieceMarkQuantityNeeded) || 0;
            const stillNeedsNesting = parseInt(p.StillNeedsNesting) || 0;
            const alreadyNested = originalTotal - stillNeedsNesting;
            return Math.floor(alreadyNested / (p.AssemblyEachQuantity || 1));
        }));
    } else {
        // For CUT and KIT stations, use the standard calculation
        completed = Math.min(...pieces.map(p => {
            const qty = stationType === 'CUT' ? p.QtyCut : p.QtyKitted;
            return Math.floor((qty || 0) / (p.AssemblyEachQuantity || 1));
        }));
    }

    const stationData = {
        StationDescription: stationType,
        StationQuantityCompleted: completed,
        StationTotalQuantity: totalNeeded,
        Pieces: pieces
    };

    const index = stations.findIndex(s => s.StationDescription === stationType);
    if (index === -1) {
        stations.push(stationData);
    } else {
        stations[index] = stationData;
    }
}

// Show a loading overlay with a message
function showLoading(message) {
    // Remove any existing loading overlay
    hideLoading();

    // Create a new loading overlay
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.innerHTML = `<div class="loading-spinner">${message}</div>`;
    document.body.appendChild(overlay);
}

// Update the loading message
function updateLoadingMessage(message) {
    const spinner = document.querySelector('.loading-spinner');
    if (spinner) {
        spinner.textContent = message;
    }
}

// Hide the loading overlay
function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.parentNode.removeChild(overlay);
    }
}

// Disable all filter buttons during data loading
function disableAllFilters() {
    document.querySelectorAll('#bayFilter button, #wpFilter button, #routeFilter button, #categoryFilter button, #sequenceFilter button')
        .forEach(button => {
            button.disabled = true;
        });
}

// Update the UI with the loaded data
function updateUI() {
    createFilterButtons();
    createTableHeader();
    filterData();
}

// Helper function to safely divide numbers, avoiding division by zero
function safeDivide(numerator, denominator) {
    if (!denominator || isNaN(denominator)) return 0;
    const result = numerator / denominator;
    return isNaN(result) ? 0 : result;
}