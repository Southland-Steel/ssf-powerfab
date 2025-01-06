class WorkPackageData {
    constructor(options = {}) {
        // Configuration
        this.orderedStations = options.orderedStations || [
            'CUT', 'FIT', 'WELD', 'FINAL QC'
        ];
        this.apiUrl = 'ajax_ssf_workpackage_assembly_station_status.php';
        
        // State
        this.assemblyData = [];
        this.totals = this.initializeTotals();
        
        // Event callbacks
        this.onDataLoaded = null;
        this.onError = null;
    }

    initializeTotals() {
        return {
            totalHours: 0,
            completedHours: 0,
            remainingHours: 0,
            totalWeight: 0,
            completedWeight: 0,
            remainingWeight: 0,
            percentComplete: 0
        };
    }

    async loadData(workPackageId) {
        try {
            const response = await fetch(`${this.apiUrl}?workpackageid=${workPackageId}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const data = await response.json();
            
            // Sort data by completion status
            data.sort((a, b) => {
                const aCompleted = this.checkCompletion(a.Stations);
                const bCompleted = this.checkCompletion(b.Stations);
                if (aCompleted === bCompleted) return 0;
                return aCompleted ? 1 : -1;
            });

            this.assemblyData = data;
            this.calculateTotals();
            
            // Notify listeners
            if (this.onDataLoaded) {
                this.onDataLoaded(this.assemblyData, this.totals);
            }
        } catch (error) {
            console.error('Error loading data:', error);
            if (this.onError) {
                this.onError(error);
            }
        }
    }

    calculateTotals() {
        this.totals = this.initializeTotals();
        
        this.assemblyData.forEach(assembly => {
            const totalHours = parseFloat(assembly.TotalEstimatedManHours) || 0;
            const weight = parseFloat(assembly.TotalNetWeight) || 0;
            
            // Calculate station hours
            let assemblyStationHours = 0;
            assembly.Stations.forEach(station => {
                const stationName = station.StationDescription.toUpperCase();
                const stationHoursDistribution = this.calculateStationHours(assembly.RouteName, assembly.AssemblyManHoursEach);
                const stationHourRate = stationHoursDistribution[stationName] || 0;
                
                // Calculate hours for this station
                const stationHours = station.StationQuantityCompleted * stationHourRate;
                assemblyStationHours += stationHours;
            });
    
            // Check if all stations are complete for weight calculation
            const isAssemblyComplete = assembly.Stations.every(station => 
                station.StationQuantityCompleted === station.StationTotalQuantity
            );
    
            // Update totals
            this.totals.totalHours += totalHours;
            this.totals.totalWeight += weight;
            
            this.totals.completedHours += assemblyStationHours;
            // Only add weight if all stations are complete
            if (isAssemblyComplete) {
                this.totals.completedWeight += weight;
            }
        });
    
        // Calculate remaining values and completion percent based on hours
        this.totals.remainingHours = this.totals.totalHours - this.totals.completedHours;
        this.totals.remainingWeight = this.totals.totalWeight - this.totals.completedWeight;
        this.totals.percentComplete = this.totals.totalHours > 0 
            ? (this.totals.completedHours / this.totals.totalHours * 100) 
            : 0;
    
        return this.totals;
    }

    calculateStationTotals() {
        const totals = {};

        this.orderedStations.forEach(station => {
            totals[station] = {
                completed: 0,
                total: 0
            };
        });

        this.assemblyData.forEach(assembly => {
            const totalHours = assembly.TotalEstimatedManHours;
            const stationHours = this.calculateStationHours(assembly.RouteName, totalHours);

            assembly.Stations.forEach(station => {
                const stationName = station.StationDescription.toUpperCase();
                if (this.orderedStations.includes(stationName)) {
                    const stationTotalHours = stationHours[stationName] || 0;
                    const completionRatio = station.StationQuantityCompleted / station.StationTotalQuantity;
                    
                    totals[stationName].completed += stationTotalHours * completionRatio;
                    totals[stationName].total += stationTotalHours;
                }
            });
        });

        return totals;
    }

    calculateStationHours(route, totalHours) {
        route = trim(route);
        switch (route) {
            case '04: SSF CUT & FAB':
                return {
                    'FIT': totalHours * 0.38,
                    'WELD': totalHours * 0.58,
                    'FINAL QC': totalHours * 0.04,
                    'CUT': totalHours * 0.0001,
                    'IFF': totalHours * 0.0001,
                    'IFA': totalHours * 0.0001
                };
            case '00: PLANNED':
                return {
                    'FIT': totalHours * 0.38,
                    'WELD': totalHours * 0.58,
                    'FINAL QC': totalHours * 0.04,
                    'CUT': totalHours * 0.0001,
                    'IFF': totalHours * 0.0001,
                    'IFA': totalHours * 0.0001
                };
            default:
                return {
                    'FIT': 0,
                    'WELD': 0,
                    'FINAL QC': totalHours
                };
        }
    }

    getStationData(assembly, stationName) {
        const station = assembly.Stations.find(s =>
            s.StationDescription.toUpperCase() === stationName
        );

        if (!station) return null;

        const assemblyDefinition = this.calculateStationHours(assembly.RouteName, assembly.TotalEstimatedManHours);
        const assemblyDefinitionEach = this.calculateStationHours(assembly.RouteName, assembly.AssemblyManHoursEach);

        const stationHours = assemblyDefinition[stationName] || 0;
        const stationHoursEach = assemblyDefinitionEach[stationName] || 0;

        return {
            completion: `${station.StationQuantityCompleted}/${station.StationTotalQuantity}`,
            percentage: ((station.StationQuantityCompleted / station.StationTotalQuantity) * 100),
            hours: station.StationActualHours ?
                `HRS: ${Math.round(station.StationActualHours*10)/10}` : '',
            completed: station.StationQuantityCompleted,
            total: station.StationTotalQuantity,
            completedHours: stationHoursEach * station.StationQuantityCompleted,
            totalHours: stationHoursEach * station.StationTotalQuantity
        };
    }

    getStationStatus(stationData) {
        if (!stationData) return 'status-na';
        const percentage = parseFloat(stationData.percentage);
        if (percentage === 100) return 'status-complete';
        if (percentage > 0) return 'status-partial';
        return 'status-notstarted';
    }

    checkCompletion(stations) {
        const finalQCStation = [...stations].reverse().find(station =>
            station.StationDescription === "FINAL QC"
        );

        return finalQCStation &&
            finalQCStation.StationQuantityCompleted === finalQCStation.StationTotalQuantity;
    }

    formatNumber(num) {
        if (num >= 1000) {
            return Math.round(num);
        } else if (num >= 100) {
            return (Math.round(num * 10) / 10).toFixed(1);
        } else {
            return (Math.round(num * 100) / 100).toFixed(2);
        }
    }

    // Public methods for other classes to interact with
    getAssemblyData() {
        return this.assemblyData;
    }

    getTotals() {
        return this.totals;
    }

    getOrderedStations() {
        return this.orderedStations;
    }

    setDataLoadedCallback(callback) {
        this.onDataLoaded = callback;
    }

    setErrorCallback(callback) {
        this.onError = callback;
    }
}