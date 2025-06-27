// workweeks/js/workweeks-filters.js

// Filter index for faster filtering
let filterIndex = {
    routes: new Map(),
    workPackages: new Map(),
    bays: new Map(),
    categories: new Map(),
    sequenceLots: new Map()
};

// Filter cache to avoid recomputing filtered indexes
let filterCache = new Map();

// Current filter values
let currentRouteFilter = 'all';
let currentWPFilter = 'all';
let currentBayFilter = 'all';
let currentCategoryFilter = 'all';
let currentSequenceFilter = 'all';

// Build filter indexes for faster filtering
function buildFilterIndex(data) {
    // Clear existing indexes
    Object.keys(filterIndex).forEach(key => filterIndex[key].clear());

    data.forEach((item, idx) => {
        // Index by Route
        const route = item.RouteName || 'undefined';
        if (!filterIndex.routes.has(route)) filterIndex.routes.set(route, new Set());
        filterIndex.routes.get(route).add(idx);

        // Index by Work Package
        const wp = item.WorkPackageNumber || 'undefined';
        if (!filterIndex.workPackages.has(wp)) filterIndex.workPackages.set(wp, new Set());
        filterIndex.workPackages.get(wp).add(idx);

        // Index by Bay
        const bay = item.Bay || 'undefined';
        if (!filterIndex.bays.has(bay)) filterIndex.bays.set(bay, new Set());
        filterIndex.bays.get(bay).add(idx);

        // Index by Category
        const category = item.Category || 'undefined';
        if (!filterIndex.categories.has(category)) filterIndex.categories.set(category, new Set());
        filterIndex.categories.get(category).add(idx);

        // Index by Sequence Lot
        const seqLot = item.SequenceDescription && item.LotNumber
            ? `${item.SequenceDescription} [${item.LotNumber}]`
            : 'undefined';
        if (!filterIndex.sequenceLots.has(seqLot)) filterIndex.sequenceLots.set(seqLot, new Set());
        filterIndex.sequenceLots.get(seqLot).add(idx);
    });

    console.log("Filter indexes built:", filterIndex);
}

// Create filter buttons for all filter types
function createFilterButtons() {
    createWPFilter();
    createRouteFilter();
    createBayFilter();
    createCategoryFilter();
    createSequenceFilter();
}

// Create work package filter buttons
function createWPFilter() {
    const workPackageNumbers = [...filterIndex.workPackages.keys()].filter(wp => wp !== 'undefined');
    let wpFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-wp="all" onclick="filterWP(\'all\', this)">All Work Packages</button>';

    // Create buttons for each WorkPackageNumber
    workPackageNumbers.forEach(wp => {
        // Find all items for this work package
        const wpIndexes = filterIndex.workPackages.get(wp);
        const wpItems = Array.from(wpIndexes).map(idx => projectData[idx]);

        // Check if any items are not released or on hold
        const isNotReleased = wpItems.some(item => item.ReleasedToFab === 0);
        const isOnHold = wpItems.some(item => item.OnHold === 1);

        // Create tooltip text if needed
        let tooltip = '';
        if (isNotReleased && isOnHold) {
            tooltip = 'Work Package not released and on hold';
        } else if (isNotReleased) {
            tooltip = 'Work Package not released';
        } else if (isOnHold) {
            tooltip = 'Work Package on hold';
        }

        // Add appropriate classes
        const extraClasses = [];
        if (isNotReleased) extraClasses.push('wpnotreleased');
        if (isOnHold) extraClasses.push('wponhold');

        wpFilterHtml += `<button class="btn btn-secondary me-2 mb-2 ${extraClasses.join(' ')}"
        data-wp="${wp}"
        onclick="filterWP('${wp}', this)"
        ${tooltip ? `title="${tooltip}"` : ''}>${wp}</button>`;
    });

    // Insert WorkPackageNumber buttons into #wpFilter
    document.getElementById('wpFilter').innerHTML = wpFilterHtml;
}

// Create bay filter buttons
function createBayFilter() {
    const bayNames = [...filterIndex.bays.keys()].filter(bay => bay !== 'undefined');
    let bayFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-bay="all" onclick="filterBay(\'all\', this)">All Bays</button>';
    let hasUndefined = filterIndex.bays.has('undefined');

    bayNames.forEach(bay => {
        bayFilterHtml += `<button class="btn btn-secondary me-2 mb-2" data-bay="${bay}" onclick="filterBay('${bay}', this)">${bay}</button>`;
    });

    if (hasUndefined) {
        bayFilterHtml += `<button class="btn btn-warning me-2 mb-2" data-bay="undefined" onclick="filterBay('undefined', this)">Undefined</button>`;
    }

    document.getElementById('bayFilter').innerHTML = bayFilterHtml;
}

// Create route filter buttons
function createRouteFilter() {
    const routes = [...filterIndex.routes.keys()].filter(route => route !== 'undefined');
    let filterHtml = '<button class="btn btn-primary me-2 mb-2" data-route="all" onclick="filterRoute(\'all\', this)">All Routes</button>';
    let hasUndefined = filterIndex.routes.has('undefined');

    routes.forEach(route => {
        filterHtml += `<button class="btn btn-secondary me-2 mb-2" data-route="${route}" onclick="filterRoute('${route}', this)">${route}</button>`;
    });

    if (hasUndefined) {
        filterHtml += `<button class="btn btn-warning me-2 mb-2" data-route="undefined" onclick="filterRoute('undefined', this)">Undefined</button>`;
    }

    document.getElementById('routeFilter').innerHTML = filterHtml;
}

// Create category filter buttons
function createCategoryFilter() {
    const categories = [...filterIndex.categories.keys()].filter(category => category !== 'undefined');
    let categoryFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-category="all" onclick="filterCategory(\'all\', this)">All Asm. Categories</button>';
    let hasUndefined = filterIndex.categories.has('undefined');

    categories.forEach(category => {
        categoryFilterHtml += `<button class="btn btn-secondary me-2 mb-2" data-category="${category}" onclick="filterCategory('${category}', this)">${category}</button>`;
    });

    if (hasUndefined) {
        categoryFilterHtml += `<button class="btn btn-warning me-2 mb-2" data-category="undefined" onclick="filterCategory('undefined', this)">Undefined</button>`;
    }

    document.getElementById('categoryFilter').innerHTML = categoryFilterHtml;
}

// Create sequence filter buttons
function createSequenceFilter() {
    const sequenceLots = [...filterIndex.sequenceLots.keys()].filter(seqLot => seqLot !== 'undefined');
    let sequenceFilterHtml = '<button class="btn btn-primary me-2 mb-2" data-seqlot="all" onclick="filterSequenceLot(\'all\', this)">All Sequences</button>';
    let hasUndefined = filterIndex.sequenceLots.has('undefined');

    sequenceLots.sort().forEach(seqLot => {
        const displaySeqLot = seqLot.replace('[', '<br>[');
        sequenceFilterHtml += `<button class="btn btn-secondary me-2 mb-2" data-seqlot="${seqLot}" onclick="filterSequenceLot('${seqLot}', this)">${displaySeqLot}</button>`;
    });

    if (hasUndefined) {
        sequenceFilterHtml += `<button class="btn btn-warning me-2 mb-2" data-seqlot="undefined" onclick="filterSequenceLot('undefined', this)">Undefined</button>`;
    }

    document.getElementById('sequenceFilter').innerHTML = sequenceFilterHtml;
}

// Filter by bay
function filterBay(bay, button) {
    currentBayFilter = bay;
    filterCache.clear(); // Clear cache when filter changes
    filterData();

    // Update button styles
    document.querySelectorAll('#bayFilter button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    button.classList.remove('btn-secondary');
    button.classList.add('btn-primary');
}

// Filter by work package
function filterWP(workPackage, button) {
    currentWPFilter = workPackage;
    filterCache.clear(); // Clear cache when filter changes
    filterData();

    // Update button styles
    document.querySelectorAll('#wpFilter button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    button.classList.remove('btn-secondary');
    button.classList.add('btn-primary');
}

// Filter by route
function filterRoute(route, button) {
    currentRouteFilter = route;
    filterCache.clear(); // Clear cache when filter changes
    filterData();

    // Update button styles
    document.querySelectorAll('#routeFilter button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    button.classList.remove('btn-secondary');
    button.classList.add('btn-primary');
}

// Filter by category
function filterCategory(category, button) {
    currentCategoryFilter = category;
    filterCache.clear(); // Clear cache when filter changes
    filterData();

    // Update button styles
    document.querySelectorAll('#categoryFilter button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    button.classList.remove('btn-secondary');
    button.classList.add('btn-primary');
}

// Filter by sequence lot
function filterSequenceLot(seqLot, button) {
    currentSequenceFilter = seqLot;
    filterCache.clear(); // Clear cache when filter changes
    filterData();

    // Update button styles
    document.querySelectorAll('#sequenceFilter button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    button.classList.remove('btn-secondary');
    button.classList.add('btn-primary');
}

// Get a key for the filter cache
function getFilterCacheKey() {
    return `${currentRouteFilter}|${currentWPFilter}|${currentBayFilter}|${currentCategoryFilter}|${currentSequenceFilter}`;
}

// Get filtered indexes based on current filters
function getFilteredIndexes() {
    const cacheKey = getFilterCacheKey();
    if (filterCache.has(cacheKey)) {
        return filterCache.get(cacheKey);
    }

    // Start with all indices
    let resultSet = new Set(Array.from({ length: projectData.length }, (_, i) => i));

    // Apply route filter
    if (currentRouteFilter !== 'all') {
        const routeIndices = filterIndex.routes.get(currentRouteFilter);
        if (routeIndices) {
            resultSet = new Set([...resultSet].filter(idx => routeIndices.has(idx)));
        }
    }

    // Apply work package filter
    if (currentWPFilter !== 'all') {
        const wpIndices = filterIndex.workPackages.get(currentWPFilter);
        if (wpIndices) {
            resultSet = new Set([...resultSet].filter(idx => wpIndices.has(idx)));
        }
    }

    // Apply bay filter
    if (currentBayFilter !== 'all') {
        const bayIndices = filterIndex.bays.get(currentBayFilter);
        if (bayIndices) {
            resultSet = new Set([...resultSet].filter(idx => bayIndices.has(idx)));
        }
    }

    // Apply category filter
    if (currentCategoryFilter !== 'all') {
        const categoryIndices = filterIndex.categories.get(currentCategoryFilter);
        if (categoryIndices) {
            resultSet = new Set([...resultSet].filter(idx => categoryIndices.has(idx)));
        }
    }

    // Apply sequence lot filter
    if (currentSequenceFilter !== 'all') {
        const seqLotIndices = filterIndex.sequenceLots.get(currentSequenceFilter);
        if (seqLotIndices) {
            resultSet = new Set([...resultSet].filter(idx => seqLotIndices.has(idx)));
        }
    }

    // Cache the result
    filterCache.set(cacheKey, resultSet);
    return resultSet;
}

// Apply filters and display filtered data
function filterData() {
    const filteredIndexes = getFilteredIndexes();
    const filteredData = Array.from(filteredIndexes).map(idx => projectData[idx]);
    displayTable(filteredData);
}