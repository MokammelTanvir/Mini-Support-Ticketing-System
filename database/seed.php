<?php
// Database Seed Script

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Department.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/TicketNote.php';

try {
    echo "Starting database seeding...\n";

    $userModel = new User();
    $departmentModel = new Department();
    $ticketModel = new Ticket();
    $noteModel = new TicketNote();

    // Create sample users
    echo "Creating users...\n";

    // Admin user
    $userModel->create([
        'name' => 'Admin User',
        'email' => 'admin@gmail.com',
        'password' => 'admin123',
        'role' => 'admin'
    ]);

    // Agent users
    $userModel->create([
        'name' => 'John Agent',
        'email' => 'john.agent@gmail.com',
        'password' => 'agent123',
        'role' => 'agent'
    ]);

    $userModel->create([
        'name' => 'Sarah Agent',
        'email' => 'sarah.agent@gmail.com',
        'password' => 'agent123',
        'role' => 'agent'
    ]);

    // Customer users (regular users who submit tickets)
    $userModel->create([
        'name' => 'Customer One',
        'email' => 'customer1@gmail.com',
        'password' => 'customer123',
        'role' => 'user'
    ]);

    $userModel->create([
        'name' => 'Customer Two',
        'email' => 'customer2@gmail.com',
        'password' => 'customer123',
        'role' => 'user'
    ]);

    echo "✓ Created 5 users\n";

    // Create sample departments
    echo "Creating departments...\n";

    $departments = [
        'Technical Support',
        'Billing',
        'General Inquiry',
        'Bug Reports',
        'Feature Requests'
    ];

    foreach ($departments as $dept) {
        $departmentModel->create(['name' => $dept]);
    }

    echo "✓ Created " . count($departments) . " departments\n";

    // Create sample tickets
    echo "Creating tickets...\n";

    $tickets = [
        [
            'title' => 'Login issues with mobile app',
            'description' => 'I cannot login to the mobile app. It shows "Invalid credentials" even with correct password.',
            'user_id' => 4, // Customer One
            'department_id' => 1, // Technical Support
            'status' => 'open'
        ],
        [
            'title' => 'Billing discrepancy in last invoice',
            'description' => 'There seems to be an error in my last invoice. I was charged twice for the same service.',
            'user_id' => 5, // Customer Two
            'department_id' => 2, // Billing
            'status' => 'in_progress'
        ],
        [
            'title' => 'Feature request: Dark mode',
            'description' => 'Can you please add dark mode to the application? It would be very helpful for night usage.',
            'user_id' => 4, // Customer One
            'department_id' => 5, // Feature Requests
            'status' => 'open'
        ],
        [
            'title' => 'Bug: Page not loading properly',
            'description' => 'The dashboard page is not loading properly in Chrome browser. Getting white screen.',
            'user_id' => 5, // Customer Two
            'department_id' => 4, // Bug Reports
            'status' => 'closed'
        ],
        [
            'title' => 'General question about pricing',
            'description' => 'What are the different pricing plans available? I need more information.',
            'user_id' => 2, // John Agent (agents can also create tickets)
            'department_id' => 3, // General Inquiry
            'status' => 'open'
        ]
    ];

    foreach ($tickets as $ticket) {
        $ticketModel->create($ticket);
    }

    echo "✓ Created " . count($tickets) . " tickets\n";

    // Assign some tickets to agents
    echo "Assigning tickets to agents...\n";

    $ticketModel->assignToAgent(2, 2); // Assign billing ticket to John
    $ticketModel->assignToAgent(4, 3); // Assign bug ticket to Sarah

    echo "✓ Assigned tickets to agents\n";

    // Create sample ticket notes
    echo "Creating ticket notes...\n";

    $notes = [
        [
            'ticket_id' => 1,
            'user_id' => 2, // John Agent
            'note' => 'I have received your ticket. Let me check this issue and get back to you soon.'
        ],
        [
            'ticket_id' => 2,
            'user_id' => 2, // John Agent
            'note' => 'I found the issue in your billing. The duplicate charge will be refunded within 3-5 business days.'
        ],
        [
            'ticket_id' => 2,
            'user_id' => 5, // Customer Two
            'note' => 'Thank you for the quick response! When should I expect the refund?'
        ],
        [
            'ticket_id' => 4,
            'user_id' => 3, // Sarah Agent
            'note' => 'This was a known issue that has been fixed in the latest update. Please clear your browser cache and try again.'
        ],
        [
            'ticket_id' => 4,
            'user_id' => 5, // Customer Two
            'note' => 'Thank you! The issue is resolved now.'
        ]
    ];

    foreach ($notes as $note) {
        $noteModel->create($note);
    }

    echo "✓ Created " . count($notes) . " ticket notes\n";

    echo "\n🎉 Database seeding completed successfully!\n";
    echo "\nSample login credentials:\n";
    echo "Admin: admin@gmail.com / admin123\n";
    echo "Agent: john.agent@gmail.com / agent123\n";
    echo "Agent: sarah.agent@gmail.com / agent123\n";
    echo "Customer: customer1@gmail.com / customer123\n";
    echo "Customer: customer2@gmail.com / customer123\n";
} catch (Exception $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
