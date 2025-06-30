// Modal Manager class
class ModalManager {
    constructor() {
        this.piecemarkModal = null;
        this.jsonModal = null;
    }

    init() {
        this.piecemarkModal = new bootstrap.Modal(document.getElementById('piecemarkModal'), {
            keyboard: false
        });
        this.jsonModal = new bootstrap.Modal(document.getElementById('jsonModal'), {
            keyboard: false
        });
    }

    showPiecemarkDetails(stationName, productionControlItemSequenceId) {
        const projectData = app.dataManager.getProjectData();
        const assembly = projectData.find(a => a.ProductionControlItemSequenceID === productionControlItemSequenceId);
        if (!assembly) return;

        const modalTitle = `${stationName} Details for Assembly ${assembly.MainMark} <br>(Total Assemblies Needed: ${assembly.SequenceMainMarkQuantity})`;
        $('#piecemarkModalLabel').html(modalTitle);

        let tableHeader = `
            <tr>
                <th>Piece Mark</th>
                <th>Pieces per Assembly</th>
                <th>Total Piecemarks Needed</th>
                <th>Piecemarks Completed</th>
                <th>Piece Hours Each</th>
                <th>Status</th>
            </tr>
        `;

        const station = assembly.Stations.find(s => s.StationDescription === stationName);
        if (!station || !station.Pieces) return;

        let tableBody = '';
        let minCompletedAssemblies = Infinity;

        station.Pieces.forEach(piece => {
            let completed;
            if (stationName === 'NESTED') completed = piece.QtyNested;
            else if (stationName === 'MCUT' || stationName === 'CUT') completed = piece.QtyCut;
            else if (stationName === 'KIT') completed = piece.QtyKitted;

            const needed = piece.TotalPieceMarkQuantityNeeded;
            const assembliesComplete = Math.floor(completed / piece.AssemblyEachQuantity);
            minCompletedAssemblies = Math.min(minCompletedAssemblies, assembliesComplete);

            const status = completed >= needed ? 'Complete' : `${((completed/needed) * 100).toFixed(1)}%`;
            const pieceHours = parseFloat(piece.ManHoursEach || 0).toFixed(2);

            tableBody += `
                <tr class="${completed >= needed ? '' : 'uncompleted-piecemark'}">
                    <td>${piece.Shape}-${piece.PieceMark}</td>
                    <td>${piece.AssemblyEachQuantity}</td>
                    <td>${needed}</td>
                    <td>${completed}</td>
                    <td>${pieceHours}</td>
                    <td>${status}</td>
                </tr>
            `;
        });

        // Add summary row
        tableBody += `
            <tr class="table-info">
                <td colspan="5"><strong>Total Assemblies Complete:</strong></td>
                <td><strong>${minCompletedAssemblies === Infinity ? 0 : minCompletedAssemblies}</strong></td>
            </tr>
        `;

        $('#piecemarkTable thead').html(tableHeader);
        $('#piecemarkTable tbody').html(tableBody);

        this.piecemarkModal.show();
    }

    showJsonModal(pciseqId) {
        const projectData = app.dataManager.getProjectData();
        const rowData = projectData.find(item =>
            item.ProductionControlItemSequenceID.toString() === pciseqId.toString()
        );

        if (rowData) {
            document.getElementById('jsonContent').innerHTML = Formatter.formatCollapsibleJson(rowData);
            this.jsonModal.show();
        }
    }
}