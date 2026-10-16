<?php
/**
 * KaamMitra - Services Catalog
 */

$SERVICE_CATEGORIES = [
    'all' => 'All Services',
    'plumbing' => 'Plumbing',
    'electrical' => 'Electrical',
    'appliances' => 'Appliance Repair',
    'carpentry' => 'Carpentry',
    'cleaning' => 'Home Cleaning',
    'painting' => 'Painting & Waterproofing'
];

$SERVICES_DATA = [
    [
        'id' => 'plumbing-leakage',
        'category' => 'plumbing',
        'title' => 'Plumbing Repairs & Installation',
        'subtitle' => 'Leakages, tap fitting, flush tank, pipe blockages & water motor repair',
        'icon' => 'droplet',
        'badge' => 'Most Booked',
        'starting_price' => 199,
        'rating' => 4.88,
        'reviews' => '12.4k',
        'duration' => '30-45 mins',
        'features' => [
            'Tap repair, replacement & sensor taps',
            'Water leakage detection & pipeline repair',
            'Drainage unblocking & toilet jet spray fix',
            'Overhead water tank & motor installation'
        ],
        'sub_services' => [
            ['name' => 'Tap Repair / Replacement', 'price' => 199, 'time' => '30 mins'],
            ['name' => 'Drain / Pipe Blockage Removal', 'price' => 349, 'time' => '45 mins'],
            ['name' => 'Flush Tank Repair / Replacement', 'price' => 399, 'time' => '40 mins'],
            ['name' => 'Water Motor / Pump Servicing', 'price' => 499, 'time' => '60 mins'],
            ['name' => 'Complete Bathroom Plumbing Checkup', 'price' => 249, 'time' => '45 mins']
        ]
    ],
    [
        'id' => 'electrical-repairs',
        'category' => 'electrical',
        'title' => 'Electrical Repairs & Wiring',
        'subtitle' => 'Switchboards, MCB tripping, fan installation, chandelier & short circuits',
        'icon' => 'zap',
        'badge' => 'Express 30 Mins',
        'starting_price' => 149,
        'rating' => 4.92,
        'reviews' => '18.1k',
        'duration' => '30-60 mins',
        'features' => [
            'Switch, socket & regulator replacement',
            'Ceiling fan, exhaust fan & chandelier fitting',
            'MCB tripping & fuse box diagnostics',
            'Inverter wiring & full house wiring checkup'
        ],
        'sub_services' => [
            ['name' => 'Switchboard / Socket Repair', 'price' => 149, 'time' => '25 mins'],
            ['name' => 'Ceiling Fan Installation / Repair', 'price' => 199, 'time' => '30 mins'],
            ['name' => 'MCB & Fuse Box Diagnostics', 'price' => 299, 'time' => '40 mins'],
            ['name' => 'Inverter Installation & Wiring', 'price' => 599, 'time' => '60 mins'],
            ['name' => 'Full House Electrical Safety Audit', 'price' => 499, 'time' => '60 mins']
        ]
    ],
    [
        'id' => 'appliance-repair',
        'category' => 'appliances',
        'title' => 'AC & Appliance Repair',
        'subtitle' => 'Split/Window AC servicing, refrigerator, washing machine, microwave & geyser',
        'icon' => 'cpu',
        'badge' => 'Summer Essential',
        'starting_price' => 299,
        'rating' => 4.85,
        'reviews' => '9.7k',
        'duration' => '45-90 mins',
        'features' => [
            'Foam-jet AC power servicing & gas refilling',
            'Single / double door refrigerator cooling fix',
            'Front/top load washing machine repair',
            'Water geyser element & thermostat replacement'
        ],
        'sub_services' => [
            ['name' => 'AC Jet Pump Foam Servicing', 'price' => 499, 'time' => '45 mins'],
            ['name' => 'AC Gas Refill & Leak Fixing', 'price' => 1899, 'time' => '60 mins'],
            ['name' => 'Washing Machine Diagnostics & Fix', 'price' => 349, 'time' => '45 mins'],
            ['name' => 'Refrigerator Cooling Problem Fix', 'price' => 399, 'time' => '45 mins'],
            ['name' => 'Geyser Repair & Element Replacement', 'price' => 299, 'time' => '40 mins']
        ]
    ],
    [
        'id' => 'carpentry-services',
        'category' => 'carpentry',
        'title' => 'Carpentry & Furniture Assembly',
        'subtitle' => 'Door lock repair, modular furniture assembly, drawer slides & drill mounting',
        'icon' => 'hammer',
        'badge' => 'Certified Woodworkers',
        'starting_price' => 249,
        'rating' => 4.81,
        'reviews' => '6.3k',
        'duration' => '45-90 mins',
        'features' => [
            'Door hinges, handles & high-security lock fix',
            'IKEA & modular furniture assembly',
            'Wall drilling, TV mount, curtain rods & shelves',
            'Bed, sofa frame & cupboard woodwork repair'
        ],
        'sub_services' => [
            ['name' => 'Door Lock Repair / Installation', 'price' => 249, 'time' => '35 mins'],
            ['name' => 'TV Wall Mount Installation', 'price' => 299, 'time' => '30 mins'],
            ['name' => 'Furniture Assembly (Table/Bed/Wardrobe)', 'price' => 599, 'time' => '60 mins'],
            ['name' => 'Curtain Rod & Shelf Wall Drilling (3 items)', 'price' => 249, 'time' => '30 mins'],
            ['name' => 'Custom Wood Repair & Restoration', 'price' => 449, 'time' => '60 mins']
        ]
    ],
    [
        'id' => 'home-cleaning',
        'category' => 'cleaning',
        'title' => 'Deep Home & Kitchen Cleaning',
        'subtitle' => 'Intense bathroom descaling, kitchen degreasing, sofa shampooing & full home sanitize',
        'icon' => 'sparkles',
        'badge' => 'Eco-Friendly Chemicals',
        'starting_price' => 399,
        'rating' => 4.90,
        'reviews' => '11.8k',
        'duration' => '60-180 mins',
        'features' => [
            'Multi-stage bathroom stain & tile descaling',
            'Chimney, stove & slab kitchen degreasing',
            'Sofa & mattress dry clean + shampoo extraction',
            'Complete empty / furnished house deep cleaning'
        ],
        'sub_services' => [
            ['name' => 'Intense Bathroom Deep Cleaning', 'price' => 399, 'time' => '60 mins'],
            ['name' => 'Kitchen Deep Degreasing Service', 'price' => 799, 'time' => '90 mins'],
            ['name' => 'Sofa Shampooing (3-Seater)', 'price' => 599, 'time' => '60 mins'],
            ['name' => '1 BHK Full Home Deep Cleaning', 'price' => 1999, 'time' => '180 mins'],
            ['name' => 'Balcony & Floor Scrubbing', 'price' => 349, 'time' => '45 mins']
        ]
    ],
    [
        'id' => 'painting-waterproofing',
        'category' => 'painting',
        'title' => 'Painting & Wall Waterproofing',
        'subtitle' => 'Single room touch-up, dampness sealing, waterproof coating & full home painting',
        'icon' => 'paint-brush',
        'badge' => 'Free Site Consultation',
        'starting_price' => 699,
        'rating' => 4.79,
        'reviews' => '4.5k',
        'duration' => '1-3 days',
        'features' => [
            'Wall dampness diagnosis & chemical seal',
            'Single wall accent & stencil designs',
            'Rental move-in / move-out repaint',
            'Asian Paints / Berger certified painters'
        ],
        'sub_services' => [
            ['name' => 'Free Site Inspection & Laser Measurement', 'price' => 0, 'time' => '30 mins'],
            ['name' => 'Wall Dampness Waterproof Treatment', 'price' => 899, 'time' => '120 mins'],
            ['name' => 'Single Room Touch-up & Patch Painting', 'price' => 699, 'time' => '90 mins'],
            ['name' => 'Accent Wall Designer Painting', 'price' => 1499, 'time' => '180 mins'],
            ['name' => 'Complete House Repainting (Per Sq. Ft Quote)', 'price' => 2999, 'time' => 'Custom']
        ]
    ]
];
