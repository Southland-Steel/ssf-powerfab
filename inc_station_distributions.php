<?php

function calculateStationHours($route, $totalHours) {
    switch ($route) {
        case '04: SSF CUT & FAB':
            return [
                'FIT' => $totalHours * 0.35,
                'WELD' => $totalHours * 0.55,
                'FINAL QC' => $totalHours * 0.10
            ];
        default:
            return [
                'FIT' => 0,
                'WELD' => 0,
                'FINAL QC' => $totalHours
            ];
    }
}