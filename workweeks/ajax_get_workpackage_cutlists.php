<?php
header('Content-Type: application/json');

// Simulate database delay
sleep(1);

$wp_id = $_GET['wp_id'] ?? null;

// Sample data
$data = [
    [
        'CutListNumber' => 'CL2024-1001',
        'Status' => 'In Progress',
        'TotalPieces' => 156,
        'TotalFeet' => 432.5,
        'CompletionPercent' => 65.4
    ],
    [
        'CutListNumber' => 'CL2024-1002',
        'Status' => 'Not Started',
        'TotalPieces' => 89,
        'TotalFeet' => 267.8,
        'CompletionPercent' => 0
    ],
    [
        'CutListNumber' => 'CL2024-1003',
        'Status' => 'Complete',
        'TotalPieces' => 45,
        'TotalFeet' => 178.2,
        'CompletionPercent' => 100
    ]
];

echo json_encode($data, JSON_PRETTY_PRINT);