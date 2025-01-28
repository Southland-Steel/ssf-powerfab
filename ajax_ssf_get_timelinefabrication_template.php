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
                'start' => "2024-12-18",
                'end' => "2025-01-19",
                'percentage' => 98,
                'hours' => 1200
            ],
            'iff' => ['description' => "IFF", 'start' => "2024-12-12", 'percentage' => 100],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2024-12-15", 'percentage' => 65],
            'categorize' => ['description' => "Categorize", 'start' => "2024-12-18", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-01-08', 'end' => '2025-01-19']
        ],
        [
            'id' => 2,
            'project' => "10-00-123",
            'sequence' => "E302P",
            'pm' => "Matt",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-01-01",
                'end' => "2025-02-09",
                'percentage' => 0,
                'hours' => 2500
            ],
            'iff' => ['description' => "IFF", 'start' => "2024-12-12", 'percentage' => 50],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2024-12-13", 'percentage' => 0],
            'categorize' => ['description' => "Categorize", 'start' => "2024-12-15", 'percentage' => 0],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-01-22', 'end' => '2025-02-09']
        ],
        [
            'id' => 3,
            'project' => "10-00-124",
            'sequence' => "M101P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-01-22",
                'end' => "2025-02-23",
                'percentage' => 75,
                'hours' => 1800
            ],
            'iff' => ['description' => "IFF", 'start' => "2024-12-30", 'percentage' => 85],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-01-05", 'percentage' => 45],
            'categorize' => ['description' => "Categorize", 'start' => "2024-12-25", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-02-12', 'end' => '2025-02-23']
        ],
        [
            'id' => 4,
            'project' => "10-00-124",
            'sequence' => "M102P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-05",
                'end' => "2025-03-16",
                'percentage' => 30,
                'hours' => 3100
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-01-15", 'percentage' => 30],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-01-25", 'percentage' => 10],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-28", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-02-26', 'end' => '2025-03-16']
        ],
        [
            'id' => 5,
            'project' => "10-00-125",
            'sequence' => "P201P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-26",
                'end' => "2025-03-30",
                'percentage' => 10,
                'hours' => 2200
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-01-28", 'percentage' => 90],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-05", 'percentage' => 75],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-07", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-03-19', 'end' => '2025-03-30']
        ],
        [
            'id' => 6,
            'project' => "10-00-125",
            'sequence' => "P202P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-03-12",
                'end' => "2025-04-20",
                'percentage' => 0,
                'hours' => 3800
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-02-15", 'percentage' => 40],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-22", 'percentage' => 20],
            'categorize' => ['description' => "Categorize", 'start' => "2025-02-12", 'percentage' => 0],
            'hasWorkPackage' => false
        ],
        [
            'id' => 7,
            'project' => "10-00-126",
            'sequence' => "C401P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-04-02",
                'end' => "2025-05-11",
                'percentage' => 0,
                'hours' => 3900
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-03-12", 'percentage' => 70],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-03-20", 'percentage' => 55],
            'categorize' => ['description' => "Categorize", 'start' => "2025-01-22", 'percentage' => 100],
            'hasWorkPackage' => false
        ],
        [
            'id' => 8,
            'project' => "10-00-126",
            'sequence' => "C402P",
            'pm' => "Braie",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-04-23",
                'end' => "2025-05-30",
                'percentage' => 0,
                'hours' => 3400
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-03-29", 'percentage' => 20],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-04-05", 'percentage' => 5],
            'categorize' => ['description' => "Categorize", 'start' => "2025-02-27", 'percentage' => 0],
            'hasWorkPackage' => false
        ],
        [
            'id' => 9,
            'project' => "10-00-127",
            'sequence' => "T501P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-12",
                'end' => "2025-03-16",
                'percentage' => 15,
                'hours' => 2600
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-01-25", 'percentage' => 60],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-05", 'percentage' => 35],
            'categorize' => ['description' => "Categorize", 'start' => "2025-02-07", 'percentage' => 100],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-03-05', 'end' => '2025-03-16']
        ],
        [
            'id' => 10,
            'project' => "10-00-127",
            'sequence' => "T502P",
            'pm' => "Trevor",
            'fabrication' => [
                'description' => "Fabrication",
                'start' => "2025-02-26",
                'end' => "2025-03-30",
                'percentage' => 0,
                'hours' => 3900
            ],
            'iff' => ['description' => "IFF", 'start' => "2025-02-15", 'percentage' => 10],
            'nsi' => ['description' => "Non-Stock Items", 'start' => "2025-02-20", 'percentage' => 0],
            'categorize' => ['description' => "Categorize", 'start' => "2025-03-12", 'percentage' => 0],
            'hasWorkPackage' => true,
            'wp' => ['start' => '2025-03-19', 'end' => '2025-03-30']
        ]
    ]
];

echo json_encode($ganttData);