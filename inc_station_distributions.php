<?php

function calculateStationHours($route, $totalHours) {
    switch ($route) {
        case 'MANUAL':
            return [
                'CUT' =>
                'FIT' => $totalHours * 0.38,
                'WELD' => $totalHours * 0.58,
                'FINAL QC' => $totalHours * 0.04
            ];
        default:
            return [
                'FIT' => 0,
                'WELD' => 0,
                'FINAL QC' => $totalHours
            ];
    }
}