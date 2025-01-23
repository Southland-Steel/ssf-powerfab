<?php
header('Content-Type: application/json');

$ganttData = [
    'dateRange' => [
        'start' => '2024-12-15',
        'end' => '2025-05-30'
    ],
    'sequences' => [
        [
            'id' => 1,
            'project' => "10-00-123",
            'sequence' => "E301P",
            'pm' => "Matt",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-01-01",
                'end' => "2025-02-15",
                'percentage' => 40,
                'hours' => 1200
            ],
            'iff' => ['description' => "IFF", 'start' => "2024-12-21", 'percentage' => 100],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2024-12-15", 'percentage' => 65],
            'categorize' => ['description' => "Categorize", 'start' => "2024-12-18", 'percentage' => 100],
            'hasWorkPackage' => false,
            'wp' => ['start' => '2025-01-01', 'end' => '2025-02-15']
        ],
        [
            'id' => 2,
            'project' => "10-00-123",
            'sequence' => "E302P",
            'pm' => "Matt",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-20",
                'end' => "2025-04-05",
                'percentage' => 0,
                'hours' => 2500
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-01-21", 'percentage' => 50],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-01-15", 'percentage' => 0],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-18", 'percentage' => 0],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-02-20', 'end' => '2025-04-05']
        ],
        [
            'id' => 3,
            'project' => "10-00-124",
            'sequence' => "M101P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-01-15",
                'end' => "2025-03-01",
                'percentage' => 25,
                'hours' => 1800
            ],
            'iff' => ['description' => "IFF", 'start' => "2024-12-30", 'percentage' => 85],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2024-12-20", 'percentage' => 45],
            'categorize' => ['description' => "Categorize", 'start' => "2024-12-25", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-01-15', 'end' => '2025-03-01']
        ],
        [
            'id' => 4,
            'project' => "10-00-124",
            'sequence' => "M102P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-03-05",
                'end' => "2025-04-20",
                'percentage' => 0,
                'hours' => 3100
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-02-01", 'percentage' => 30],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-01-25", 'percentage' => 10],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-28", 'percentage' => 45],
            'hasWorkPackage' => false,
            'wp' => ['start' => '2025-03-05', 'end' => '2025-04-20']
        ],
        [
            'id' => 5,
            'project' => "10-00-125",
            'sequence' => "P201P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-01",
                'end' => "2025-03-15",
                'percentage' => 60,
                'hours' => 2200
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-01-10", 'percentage' => 90],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-01-05", 'percentage' => 75],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-07", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-02-01', 'end' => '2025-03-15']
        ],
        [
            'id' => 6,
            'project' => "10-00-125",
            'sequence' => "P202P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-03-20",
                'end' => "2025-05-05",
                'percentage' => 0,
                'hours' => 3800
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-02-15", 'percentage' => 40],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-10", 'percentage' => 20],
            'categorize' => ['description' => "Categorize", 'start' => "2025-02-12", 'percentage' => 0],
            'hasWorkPackage' => false,
            'wp' => ['start' => '2025-03-20', 'end' => '2025-05-05']
        ],
        [
            'id' => 7,
            'project' => "10-00-126",
            'sequence' => "C401P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-15",
                'end' => "2025-04-01",
                'percentage' => 30,
                'hours' => 3900
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-01-25", 'percentage' => 70],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-01-20", 'percentage' => 55],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-22", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-02-15', 'end' => '2025-04-01']
        ],
        [
            'id' => 8,
            'project' => "10-00-126",
            'sequence' => "C402P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-04-05",
                'end' => "2025-05-20",
                'percentage' => 0,
                'hours' => 3400
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-03-01", 'percentage' => 20],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-25", 'percentage' => 5],
            'categorize' => ['description' => "Categorize", 'start' => "2025-02-27", 'percentage' => 0],
            'hasWorkPackage' => false,
            'wp' => ['start' => '2025-04-05', 'end' => '2025-05-20']
        ],
        [
            'id' => 9,
            'project' => "10-00-127",
            'sequence' => "T501P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-03-01",
                'end' => "2025-04-15",
                'percentage' => 15,
                'hours' => 2600
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-02-10", 'percentage' => 60],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-05", 'percentage' => 35],
            'categorize' => ['description' => "Categorize", 'start' => "2025-02-07", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-03-01', 'end' => '2025-04-15']
        ],
        [
            'id' => 10,
            'project' => "10-00-127",
            'sequence' => "T502P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-04-20",
                'end' => "2025-05-30",
                'percentage' => 0,
                'hours' => 3900
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-03-15", 'percentage' => 10],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-03-10", 'percentage' => 0],
            'categorize' => ['description' => "Categorize", 'start' => "2025-03-12", 'percentage' => 0],
            'hasWorkPackage' => false,
            'wp' => ['start' => '2025-04-20', 'end' => '2025-05-30']
        ]
    ]
];

echo json_encode($ganttData);