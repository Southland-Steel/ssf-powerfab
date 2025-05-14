// workweeks/js/workweeks-stations.js

// Station class to encapsulate station-specific behavior
class Station {
    constructor(name, order, isPiecemarkStation = false) {
        this.name = name;
        this.order = order;
        this.isPiecemarkStation = isPiecemarkStation;
    }

    // Get the CSS status class based on completion status
    getStatusClass(completed, total) {
        if (completed === 0 && total === 0) {
            return 'status-complete';  // Return complete status for 0/0
        } else if (completed === 0) {
            return 'status-notstarted';
        } else if (completed >= total) {
            return 'status-complete';
        } else {
            return 'status-partial';
        }
    }

    // Calculate completion for this station type
    calculateCompletion(stationData, assemblyData) {
        if (!stationData) return { completed: 0, total: 0 };

        const completed = parseInt(stationData.StationQuantityCompleted) || 0;
        const total = parseInt(stationData.StationTotalQuantity) || 0;

        return { completed, total };
    }

    // Render a cell for this station type
    renderCell(stationData, assemblyData) {
        if (!stationData) {
            return `<td class="status-notstarted status-na">-</td>`;
        }

        const { completed, total } = this.calculateCompletion(stationData, assemblyData);
        const statusClass = this.getStatusClass(completed, total);

        // Calculate station-specific metrics
        const stationHours = calculateStationHours(
            assemblyData.RouteName,
            assemblyData.Category,
            parseFloat(assemblyData.TotalEstimatedManHours || 0)
        );

        const stationTotalHours = stationHours[this.name] || 0;
        const completionRatio = safeDivide(completed, total);
        const stationUsedHours = stationTotalHours * completionRatio;

        const assemblyWeight = parseFloat(assemblyData.TotalNetWeight || 0);
        const stationCompletedWeight = assemblyWeight * completionRatio;

        return `
        <td class="${statusClass}">
            ${completed} / ${total}<br>
            HRS: ${formatNumber(stationUsedHours)} / ${formatNumber(stationTotalHours)}<br>
            WT: ${formatNumberWithCommas(Math.round(stationCompletedWeight))}#
        </td>`;
    }
}

// PiecemarkStation class for NESTED, CUT, and KIT stations
class PiecemarkStation extends Station {
    constructor(name, order) {
        super(name, order, true);
    }

    // Override calculateCompletion for piecemark stations
    calculateCompletion(stationData, assemblyData) {
        if (!stationData || !stationData.Pieces || stationData.Pieces.length === 0) {
            return { completed: 0, total: 0, pieces: { completed: 0, total: 0 } };
        }

        const totalNeeded = parseInt(assemblyData.SequenceMainMarkQuantity) || 0;

        let completedAssemblies = 0;
        let totalPiecesCompleted = 0;
        let totalPiecesNeeded = 0;

        if (this.name === 'NESTED') {
            // Special calculation for nesting that accounts for cut parts
            completedAssemblies = calculateNestedCompletedAssemblies(stationData.Pieces);

            // Calculate total pieces and needed pieces based on what's not already cut
            stationData.Pieces.forEach(piece => {
                const totalNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded) || 0;
                const stillNeedsNesting = parseInt(piece.StillNeedsNesting) || 0;
                const alreadyNested = totalNeeded - stillNeedsNesting;

                totalPiecesCompleted += alreadyNested;
                totalPiecesNeeded += totalNeeded;
            });
        } else {
            // Standard calculation for CUT and KIT
            completedAssemblies = calculatePiecemarkCompletedAssemblies(stationData.Pieces, this.name);

            stationData.Pieces.forEach(piece => {
                const totalNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded) || 0;
                totalPiecesNeeded += totalNeeded;

                let pieceQty = 0;
                if (this.name === 'CUT') pieceQty = parseInt(piece.QtyCut) || 0;
                else if (this.name === 'KIT') pieceQty = parseInt(piece.QtyKitted) || 0;

                totalPiecesCompleted += pieceQty;
            });
        }

        return {
            completed: completedAssemblies,
            total: totalNeeded,
            pieces: {
                completed: totalPiecesCompleted,
                total: totalPiecesNeeded
            }
        };
    }

    // Override renderCell for piecemark stations
    renderCell(stationData, assemblyData) {
        if (!stationData) {
            return `<td class="status-notstarted status-na">-</td>`;
        }

        const { completed, total, pieces } = this.calculateCompletion(stationData, assemblyData);
        const statusClass = this.getStatusClass(completed, total);

        return `
        <td class="${statusClass}">
            <a href="#" class="station-details" data-station="${this.name}"
               data-assembly="${assemblyData.ProductionControlItemSequenceID}">
                ASM: ${completed} / ${total}
            </a>
            <br>PCS: ${pieces.completed} / ${pieces.total}
        </td>`;
    }
}

// Calculate how many assemblies can be completed based on nested pieces
function calculateNestedCompletedAssemblies(pieces) {
    if (!pieces || pieces.length === 0) return 0;

    // Calculate how many assemblies can be built based on each piece
    const assembliesByPiece = pieces.map(piece => {
        // Get the quantity completed for nesting (adjusted for what's already cut)
        const totalNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded) || 0;
        const stillNeedsNesting = parseInt(piece.StillNeedsNesting) || 0;
        const alreadyNested = totalNeeded - stillNeedsNesting;

        // Get how many pieces are needed per assembly
        const piecesPerAssembly = parseInt(piece.AssemblyEachQuantity) || 1;

        // Calculate how many assemblies can be built with this piece
        return Math.floor(alreadyNested / piecesPerAssembly);
    });

    // Return the minimum number of assemblies that can be built
    return Math.min(...assembliesByPiece);
}

// Calculate how many assemblies can be completed based on CUT or KIT pieces
function calculatePiecemarkCompletedAssemblies(pieces, stationType) {
    if (!pieces || pieces.length === 0) return 0;

    // Calculate how many assemblies can be built based on each piece
    const assembliesByPiece = pieces.map(piece => {
        // Get the quantity completed for this station
        let completedQty = 0;
        if (stationType === 'CUT') completedQty = parseInt(piece.QtyCut) || 0;
        else if (stationType === 'KIT') completedQty = parseInt(piece.QtyKitted) || 0;

        // Get how many pieces are needed per assembly
        const piecesPerAssembly = parseInt(piece.AssemblyEachQuantity) || 1;

        // Calculate how many assemblies can be built with this piece
        return Math.floor(completedQty / piecesPerAssembly);
    });

    // Return the minimum number of assemblies that can be built
    return Math.min(...assembliesByPiece);
}

// Calculate hours distribution for each station based on route and category
function calculateStationHours(route, category, totalHours) {
    // Define the distribution matrix based on route and category
    const distributions = {
        '04: SSF CUT & FAB': {
            'BEAMS': {
                'NESTED': 0.01,
                'CUT': 0.06,
                'FIT': 0.38,
                'WELD': 0.51,
                'FINAL QC': 0.04
            },
            'COLUMNS': {
                'NESTED': 0.01,
                'CUT': 0.06,
                'FIT': 0.40,
                'WELD': 0.49,
                'FINAL QC': 0.04
            },
            'DEFAULT': {
                'NESTED': 0.01,
                'CUT': 0.06,
                'FIT': 0.38,
                'WELD': 0.51,
                'FINAL QC': 0.04
            }
        },
        'DEFAULT': {
            'DEFAULT': {
                'NESTED': 0.01,
                'CUT': 0.06,
                'FIT': 0.38,
                'WELD': 0.51,
                'FINAL QC': 0.04
            }
        }
    };

    // Get the distribution for the specific route and category, or fall back to defaults
    const routeDist = distributions[route] || distributions['DEFAULT'];
    const categoryDist = routeDist[category] || routeDist['DEFAULT'];

    // Calculate hours for each station
    let result = {};
    orderedStations.forEach(station => {
        result[station] = totalHours * (categoryDist[station] || 0);
    });

    return result;
}

// Initialize station objects
const stations = [
    new PiecemarkStation('NESTED', 1),
    new PiecemarkStation('CUT', 2),
    new PiecemarkStation('KIT', 3),
    new Station('PROFIT', 4),
    new Station('ZEMAN', 5),
    new Station('FIT', 6),
    new Station('WELD', 7),
    new Station('FINAL QC', 8)
];

// Get a station object by name
function getStation(stationName) {
    return stations.find(s => s.name === stationName);
}

// Show piecemark details in a modal
function showPiecemarkDetails(stationName, productionControlItemSequenceId) {
    const assembly = projectData.find(a => a.ProductionControlItemSequenceID === productionControlItemSequenceId);
    if (!assembly) return;

    const modalTitle = `${stationName} Details for Assembly ${assembly.MainMark} <br>(Total Assemblies Needed: ${assembly.SequenceMainMarkQuantity})`;
    document.getElementById('piecemarkModalLabel').innerHTML = modalTitle;

    let tableHeader = `
    <tr>
        <th>Piece Mark</th>
        <th>Pieces per Assembly</th>
        <th>Total Piecemarks Needed</th>
        <th>Piecemarks Completed</th>
        <th>Status</th>
    </tr>`;

    const station = assembly.Stations.find(s => s.StationDescription === stationName);
    if (!station || !station.Pieces) return;

    let tableBody = '';
    let minCompletedAssemblies = Infinity;

    station.Pieces.forEach(piece => {
        let completed;
        let needed = piece.TotalPieceMarkQuantityNeeded;

        // Special handling for NESTED station
        if (stationName === 'NESTED') {
            // For nesting, show how many still need nesting out of the total
            const stillNeedsNesting = parseInt(piece.StillNeedsNesting) || 0;
            const alreadyNested = needed - stillNeedsNesting;
            completed = alreadyNested;
        } else if (stationName === 'CUT') {
            completed = piece.QtyCut;
        } else if (stationName === 'KIT') {
            completed = piece.QtyKitted;
        }

        const assembliesComplete = Math.floor(completed / piece.AssemblyEachQuantity);
        minCompletedAssemblies = Math.min(minCompletedAssemblies, assembliesComplete);

        const status = completed >= needed ? 'Complete' : `${((completed/needed) * 100).toFixed(1)}%`;

        tableBody += `
        <tr class="${completed >= needed ? '' : 'uncompleted-piecemark'}">
            <td>${piece.Shape}-${piece.PieceMark}</td>
            <td>${piece.AssemblyEachQuantity}</td>
            <td>${needed}</td>
            <td>${completed}</td>
            <td>${status}</td>
        </tr>`;
    });

    // Add summary row
    tableBody += `
    <tr class="table-info">
        <td colspan="4"><strong>Total Assemblies Complete:</strong></td>
        <td><strong>${minCompletedAssemblies === Infinity ? 0 : minCompletedAssemblies}</strong></td>
    </tr>`;

    document.getElementById('piecemarkTable').querySelector('thead').innerHTML = tableHeader;
    document.getElementById('piecemarkTable').querySelector('tbody').innerHTML = tableBody;

    const modal = new bootstrap.Modal(document.getElementById('piecemarkModal'));
    modal.show();
}