// Calculator utility class
class Calculator {
    static safeDivide(numerator, denominator) {
        if (!denominator || isNaN(denominator)) return 0;
        const result = numerator / denominator;
        return isNaN(result) ? 0 : result;
    }

    static calculateCompletedAssemblies(pieces, stationName) {
        if (!pieces || pieces.length === 0) return 0;

        const assembliesByPiece = pieces.map(piece => {
            let completedQty = 0;
            if (stationName === 'NESTED') {
                completedQty = parseInt(piece.QtyNested) || 0;
            } else if (stationName === 'MCUT' || stationName === 'CUT') {
                completedQty = parseInt(piece.QtyCut) || 0;
            } else if (stationName === 'KIT') {
                completedQty = parseInt(piece.QtyKitted) || 0;
            }

            const piecesPerAssembly = parseInt(piece.AssemblyEachQuantity) || 1;
            return Math.floor(completedQty / piecesPerAssembly);
        });

        return Math.min(...assembliesByPiece);
    }

    static calculatePieceStationHours(pieces, stationName) {
        if (!pieces || pieces.length === 0) return 0;

        // Only calculate hours for stations that track them
        if (!HOUR_TRACKING_STATIONS.includes(stationName)) {
            return 0;
        }

        let totalHours = 0;

        pieces.forEach(piece => {
            const pieceHours = parseFloat(piece.ManHoursEach || 0);
            const totalPiecesNeeded = parseInt(piece.TotalPieceMarkQuantityNeeded || 0);
            const totalPieceHours = pieceHours * totalPiecesNeeded;

            // Get the percentage for this station
            const percentage = PIECE_HOUR_PERCENTAGES[stationName] || 0;
            totalHours += totalPieceHours * percentage;
        });

        return totalHours;
    }

    static calculateStationHours(route, category, totalAssemblyHours) {
        const distribution = ROUTE_DISTRIBUTIONS[route] || ROUTE_DISTRIBUTIONS['DEFAULT'];
        let result = {};

        ORDERED_STATIONS.forEach(station => {
            result[station] = 0;
        });

        Object.entries(distribution).forEach(([station, percentage]) => {
            if (result.hasOwnProperty(station)) {
                result[station] = totalAssemblyHours * percentage;
            }
        });

        return result;
    }

    static calculateTotalWeight(data) {
        return data.reduce((sum, assembly) => sum + parseFloat(assembly.TotalNetWeight || 0), 0);
    }

    static calculateCompletedWeight(data) {
        return data.reduce((sum, assembly) => {
            const assemblyWeight = parseFloat(assembly.TotalNetWeight || 0);
            const lastStation = assembly.Stations
                .filter(station => ORDERED_STATIONS.includes(station.StationDescription))
                .sort((a, b) => ORDERED_STATIONS.indexOf(b.StationDescription) - ORDERED_STATIONS.indexOf(a.StationDescription))[0];

            if (lastStation && lastStation.StationQuantityCompleted === lastStation.StationTotalQuantity) {
                return sum + assemblyWeight;
            }
            return sum;
        }, 0);
    }

    static calculateTotalProjectHours(data) {
        let totalHours = 0;

        data.forEach(assembly => {
            // Get assembly hours for FIT and FINAL QC
            const assemblyHours = parseFloat(assembly.TotalEstimatedManHours || 0);
            const stationHours = this.calculateStationHours(
                assembly.RouteName || 'DEFAULT',
                assembly.Category || 'DEFAULT',
                assemblyHours
            );

            // Add FIT and FINAL QC hours
            totalHours += (stationHours['FIT'] || 0) + (stationHours['FINAL QC'] || 0);

            // Add piece hours from CUT and MCUT stations
            if (assembly.Stations) {
                assembly.Stations.forEach(station => {
                    if (['MCUT', 'CUT'].includes(station.StationDescription) && station.Pieces) {
                        totalHours += this.calculatePieceStationHours(station.Pieces, station.StationDescription);
                    }
                });
            }
        });

        return totalHours;
    }

    static calculateTotalUsedHours(data) {
        let totalUsed = 0;

        data.forEach(assembly => {
            if (!assembly || !assembly.Stations) return;

            const assemblyHours = parseFloat(assembly.TotalEstimatedManHours || 0);
            const assemblyStationHours = this.calculateStationHours(
                assembly.RouteName || 'DEFAULT',
                assembly.Category || 'DEFAULT',
                assemblyHours
            );

            assembly.Stations.forEach(station => {
                if (!station || !ORDERED_STATIONS.includes(station.StationDescription)) return;

                const stationName = station.StationDescription;
                const stationTotal = parseFloat(station.StationTotalQuantity || 0);
                const stationCompleted = parseFloat(station.StationQuantityCompleted || 0);
                const completionRatio = this.safeDivide(stationCompleted, stationTotal);

                // Only track hours for specified stations
                if (HOUR_TRACKING_STATIONS.includes(stationName)) {
                    let stationAllocatedHours = 0;

                    if (['MCUT', 'CUT'].includes(stationName)) {
                        stationAllocatedHours = this.calculatePieceStationHours(station.Pieces, stationName);
                    } else if (['FIT', 'FINAL QC'].includes(stationName)) {
                        stationAllocatedHours = assemblyStationHours[stationName] || 0;
                    }

                    totalUsed += stationAllocatedHours * completionRatio;
                }
            });
        });

        return totalUsed;
    }

    static checkCompletion(stations) {
        const lastRelevantStation = [...stations].reverse().find(station =>
            station.StationDescription === "FINAL QC"
        );

        return lastRelevantStation &&
            lastRelevantStation.StationQuantityCompleted === lastRelevantStation.StationTotalQuantity;
    }
}