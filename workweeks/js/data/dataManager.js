// Data Manager class
class DataManager {
    constructor() {
        this.projectData = [];
        this.workweekData = [];
        this.allNestedData = [];
        this.allCutData = [];
        this.allKitData = [];
    }

    loadProjectData(workweek) {
        const self = this;
        document.getElementById('big-text').textContent = workweek;
        app.loadingOverlay.show('Loading workweek data...');
        app.filterRenderer.disableAllFilters();

        $.ajax({
            url: 'ajax_get_ssf_workweeks.php',
            method: 'GET',
            dataType: 'json',
            data: { workweek: workweek }
        })
            .then(function(response) {
                self.workweekData = Array.isArray(response.items) ? response.items : [response.items];
                app.loadingOverlay.updateMessage('Loading nested data...');
                return self.loadAllBatches('ajax_get_ssf_workweek_nested.php', workweek);
            })
            .then(function(nestedData) {
                self.allNestedData = nestedData || [];
                app.loadingOverlay.updateMessage('Loading cut data...');
                return self.loadAllBatches('ajax_get_ssf_workweek_cut.php', workweek);
            })
            .then(function(cutData) {
                self.allCutData = cutData || [];
                app.loadingOverlay.updateMessage('Loading kit data...');
                return self.loadAllBatches('ajax_get_ssf_workweek_kit.php', workweek);
            })
            .then(function(kitData) {
                self.allKitData = kitData || [];
                app.loadingOverlay.updateMessage('Processing data...');
                return self.processDataInChunks();
            })
            .then(function(mergedData) {
                self.projectData = mergedData;
                app.filterManager.clearCache();
                app.filterManager.buildIndex(self.projectData);
                self.updateUI();
            })
            .fail(function(error) {
                console.error('Error:', error);
                alert('Error loading data. Please try again.');
            })
            .always(function() {
                app.loadingOverlay.hide();
            });
    }

    loadAllBatches(url, workweek, offset = 0, accumulated = []) {
        const deferred = $.Deferred();
        const self = this;

        function loadBatch() {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                data: { workweek: workweek, offset: offset }
            })
                .done(function(response) {
                    const newData = accumulated.concat(response.items || []);
                    app.loadingOverlay.updateMessage(`Loaded ${newData.length} records...`);

                    if (response.hasMore) {
                        offset = response.nextOffset;
                        accumulated = newData;
                        loadBatch();
                    } else {
                        deferred.resolve(newData);
                    }
                })
                .fail(function(error) {
                    deferred.reject(error);
                });
        }

        loadBatch();
        return deferred.promise();
    }

    processDataInChunks() {
        const self = this;
        const chunkSize = 1000;
        const chunks = [];

        for (let i = 0; i < this.workweekData.length; i += chunkSize) {
            chunks.push(this.workweekData.slice(i, i + chunkSize));
        }

        const nestedMap = new Map();
        const cutMap = new Map();
        const kitMap = new Map();

        this.allNestedData.forEach(item => {
            const key = item.ProductionControlItemSequenceID;
            if (!nestedMap.has(key)) nestedMap.set(key, []);
            nestedMap.get(key).push(item);
        });

        this.allCutData.forEach(item => {
            const key = item.ProductionControlItemSequenceID;
            if (!cutMap.has(key)) cutMap.set(key, []);
            cutMap.get(key).push(item);
        });

        this.allKitData.forEach(item => {
            const key = item.ProductionControlItemSequenceID;
            if (!kitMap.has(key)) kitMap.set(key, []);
            kitMap.get(key).push(item);
        });

        const deferred = $.Deferred();
        let processedData = [];
        let currentChunk = 0;

        function processNextChunk() {
            if (currentChunk >= chunks.length) {
                deferred.resolve(processedData);
                return;
            }

            setTimeout(() => {
                const chunk = chunks[currentChunk];
                const mergedChunk = chunk.map(workweekItem => {
                    const pciseqId = workweekItem.ProductionControlItemSequenceID;
                    return self.mergeItemData(
                        workweekItem,
                        nestedMap.get(pciseqId) || [],
                        cutMap.get(pciseqId) || [],
                        kitMap.get(pciseqId) || []
                    );
                });

                processedData = processedData.concat(mergedChunk);
                currentChunk++;
                app.loadingOverlay.updateMessage(`Processing data: ${Math.round((currentChunk / chunks.length) * 100)}%`);
                processNextChunk();
            }, 0);
        }

        processNextChunk();
        return deferred.promise();
    }

    mergeItemData(workweekItem, nestedPieces, cutPieces, kitPieces) {
        let updatedStations = workweekItem.Stations || [];

        if (nestedPieces.length > 0) {
            this.updateStation('NESTED', nestedPieces, updatedStations);
        }

        if (cutPieces.length > 0) {
            const mainCutPieces = cutPieces.filter(p => p.isMainPiece == 1);
            const regularCutPieces = cutPieces.filter(p => p.isMainPiece != 1);

            if (mainCutPieces.length > 0) {
                this.updateStation('MCUT', mainCutPieces, updatedStations);
            }

            if (regularCutPieces.length > 0) {
                this.updateStation('CUT', regularCutPieces, updatedStations);
            }
        }

        if (kitPieces.length > 0) {
            this.updateStation('KIT', kitPieces, updatedStations);
        }

        return {
            ...workweekItem,
            Stations: updatedStations,
            Pieces: [...nestedPieces, ...cutPieces, ...kitPieces]
        };
    }

    updateStation(stationType, pieces, stations) {
        const totalNeeded = pieces[0]?.SequenceQuantity || 0;
        const completed = Math.min(...pieces.map(p => {
            const qty = stationType === 'NESTED' ? p.QtyNested :
                (stationType === 'MCUT' || stationType === 'CUT') ? p.QtyCut :
                    stationType === 'KIT' ? p.QtyKitted : 0;
            return Math.floor((qty || 0) / (p.AssemblyEachQuantity || 1));
        }));

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

    updateUI() {
        app.filterRenderer.createAllFilters(this.projectData);
        app.tableRenderer.createTableHeader(this.projectData);
        app.filterManager.applyFilters();
    }

    getProjectData() {
        return this.projectData;
    }
}