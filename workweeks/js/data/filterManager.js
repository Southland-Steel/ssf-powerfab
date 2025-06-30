// Filter Manager class
class FilterManager {
    constructor() {
        this.currentRouteFilter = 'all';
        this.currentWPFilter = 'all';
        this.currentBayFilter = 'all';
        this.currentCategoryFilter = 'all';
        this.currentSequenceFilter = 'all';
        this.filterCache = new Map();
        this.filterIndex = {
            routes: new Map(),
            workPackages: new Map(),
            bays: new Map(),
            categories: new Map(),
            sequenceLots: new Map()
        };
    }

    buildIndex(data) {
        // Clear existing indexes
        Object.keys(this.filterIndex).forEach(key => this.filterIndex[key].clear());

        data.forEach((item, idx) => {
            // Index by Route
            const route = item.RouteName || 'undefined';
            if (!this.filterIndex.routes.has(route)) this.filterIndex.routes.set(route, new Set());
            this.filterIndex.routes.get(route).add(idx);

            // Index by Work Package
            const wp = item.WorkPackageNumber || 'undefined';
            if (!this.filterIndex.workPackages.has(wp)) this.filterIndex.workPackages.set(wp, new Set());
            this.filterIndex.workPackages.get(wp).add(idx);

            // Index by Bay
            const bay = item.Bay || 'undefined';
            if (!this.filterIndex.bays.has(bay)) this.filterIndex.bays.set(bay, new Set());
            this.filterIndex.bays.get(bay).add(idx);

            // Index by Category
            const category = item.Category || 'undefined';
            if (!this.filterIndex.categories.has(category)) this.filterIndex.categories.set(category, new Set());
            this.filterIndex.categories.get(category).add(idx);

            // Index by Sequence Lot
            const seqLot = item.SequenceDescription && item.LotNumber
                ? `${item.SequenceDescription} [${item.LotNumber}]`
                : 'undefined';
            if (!this.filterIndex.sequenceLots.has(seqLot)) this.filterIndex.sequenceLots.set(seqLot, new Set());
            this.filterIndex.sequenceLots.get(seqLot).add(idx);
        });
    }

    getFilterCacheKey() {
        return `${this.currentRouteFilter}|${this.currentWPFilter}|${this.currentBayFilter}|${this.currentCategoryFilter}|${this.currentSequenceFilter}`;
    }

    getFilteredIndexes(projectData) {
        const cacheKey = this.getFilterCacheKey();
        if (this.filterCache.has(cacheKey)) {
            return this.filterCache.get(cacheKey);
        }

        // Start with all indices
        let resultSet = new Set(Array.from({ length: projectData.length }, (_, i) => i));

        // Apply route filter
        if (this.currentRouteFilter !== 'all') {
            const routeIndices = this.filterIndex.routes.get(this.currentRouteFilter);
            if (routeIndices) {
                resultSet = new Set([...resultSet].filter(idx => routeIndices.has(idx)));
            }
        }

        // Apply work package filter
        if (this.currentWPFilter !== 'all') {
            const wpIndices = this.filterIndex.workPackages.get(this.currentWPFilter);
            if (wpIndices) {
                resultSet = new Set([...resultSet].filter(idx => wpIndices.has(idx)));
            }
        }

        // Apply bay filter
        if (this.currentBayFilter !== 'all') {
            const bayIndices = this.filterIndex.bays.get(this.currentBayFilter);
            if (bayIndices) {
                resultSet = new Set([...resultSet].filter(idx => bayIndices.has(idx)));
            }
        }

        // Apply category filter
        if (this.currentCategoryFilter !== 'all') {
            const categoryIndices = this.filterIndex.categories.get(this.currentCategoryFilter);
            if (categoryIndices) {
                resultSet = new Set([...resultSet].filter(idx => categoryIndices.has(idx)));
            }
        }

        // Apply sequence lot filter
        if (this.currentSequenceFilter !== 'all') {
            const seqLotIndices = this.filterIndex.sequenceLots.get(this.currentSequenceFilter);
            if (seqLotIndices) {
                resultSet = new Set([...resultSet].filter(idx => seqLotIndices.has(idx)));
            }
        }

        // Cache the result
        this.filterCache.set(cacheKey, resultSet);
        return resultSet;
    }

    applyFilters() {
        const projectData = app.dataManager.getProjectData();
        const filteredIndexes = this.getFilteredIndexes(projectData);
        const filteredData = Array.from(filteredIndexes).map(idx => projectData[idx]);
        app.tableRenderer.populateTable(filteredData);
    }

    clearCache() {
        this.filterCache.clear();
    }

    setRouteFilter(route) {
        this.currentRouteFilter = route;
        this.clearCache();
        this.applyFilters();
    }

    setWPFilter(workPackage) {
        this.currentWPFilter = workPackage;
        this.clearCache();
        this.applyFilters();
    }

    setBayFilter(bay) {
        this.currentBayFilter = bay;
        this.clearCache();
        this.applyFilters();
    }

    setCategoryFilter(category) {
        this.currentCategoryFilter = category;
        this.clearCache();
        this.applyFilters();
    }

    setSequenceFilter(seqLot) {
        this.currentSequenceFilter = seqLot;
        this.clearCache();
        this.applyFilters();
    }
}