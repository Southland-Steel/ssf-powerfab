// Global Constants
const ORDERED_STATIONS = ['NESTED', 'MCUT', 'CUT', 'KIT', 'FIT', 'WELD', 'FINAL QC'];

// Stations that track hours
const HOUR_TRACKING_STATIONS = ['MCUT', 'CUT', 'FIT', 'FINAL QC'];

// Piece-based stations
const PIECE_BASED_STATIONS = ['NESTED', 'MCUT', 'CUT', 'KIT'];

// Assembly-based stations
const ASSEMBLY_BASED_STATIONS = ['FIT', 'WELD', 'FINAL QC'];

// Route distributions for assembly hours
const ROUTE_DISTRIBUTIONS = {
    'SHIP LOOSE': {
        'FIT': 0.80,      // 80% to FIT since no FINAL QC
        'WELD': 0.00,     // No welding for ship loose
        'FINAL QC': 0.00  // No final QC for ship loose
    },
    'BO': {
        'FIT': 0.00,      // No fitting for BO
        'WELD': 0.00,     // No welding for BO
        'FINAL QC': 0.80  // 80% to FINAL QC for BO
    },
    'DEFAULT': {
        'FIT': 0.40,      // 40% for normal routes
        'WELD': 0.00,     // Weld doesn't track hours
        'FINAL QC': 0.40  // 40% for normal routes
    }
};

// Piece hour percentages by station
const PIECE_HOUR_PERCENTAGES = {
    'NESTED': 0.00,    // No hours tracked for NESTED
    'MCUT': 0.20,      // 20% of piece hours
    'CUT': 0.20,       // 20% of piece hours
    'KIT': 0.00        // No hours tracked for KIT
};