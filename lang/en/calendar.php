<?php

return [
    'calendar' => 'Calendar',
    'holiday' => [
        'holiday' => 'Holiday',
        'module_title' => 'Manage all Holidays',
        'module_description' => 'Define holidays for your students & employees, Use to generate Attendance & Payroll Records.',
        'exists' => ':attribute is already marked as holiday.',
        'range_exists' => 'Holiday already exists between :start and :end.',
        'props' => [
            'name' => 'Name',
            'duration' => 'Duration',
            'days' => 'Days',
            'type' => 'Type',
            'type_range' => 'Range',
            'type_dates' => 'Dates',
            'type_weekend' => 'Weekend',
            'dates' => 'Dates',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'description' => 'Description',
        ],
    ],
    'celebration' => [
        'celebration' => 'Celebration',
        'celebrations' => 'Celebrations',
        'module_title' => 'List all Celebrations',
        'module_description' => 'Manage all Celebrations',
    ],
    'event' => [
        'event' => 'Event',
        'events' => 'Events',
        'module_title' => 'List all Events',
        'module_description' => 'Manage all Events',
        'props' => [
            'code_number' => 'Event #',
            'title' => 'Title',
            'type' => 'Type',
            'start_date' => 'Start Date',
            'start_time' => 'Start Time',
            'end_date' => 'End Date',
            'end_time' => 'End Time',
            'venue' => 'Venue',
            'is_public' => 'Is Public',
            'for_alumni' => 'For Alumni',
            'for' => 'For',
            'audience' => 'Audience',
            'description' => 'Description',
            'cover_image' => 'Cover Image',
        ],
        'type' => [
            'type' => 'Event Type',
            'types' => 'Event Types',
            'module_title' => 'Manage all Event Types',
            'module_description' => 'List all Event Types',
            'props' => [
                'name' => 'Name',
                'description' => 'Description',
            ],
        ],
        'config' => [
            'props' => [
                'number_prefix' => 'Event Number Prefix',
                'number_suffix' => 'Event Number Suffix',
                'number_digit' => 'Event Number Digit',
            ],
        ],
    ],
    'event_incharge' => [
        'event_incharge' => 'Event Incharge',
        'event_incharges' => 'Event Incharges',
        'module_title' => 'List all Event Incharges',
        'module_description' => 'Manage all Event Incharges',
    ],
    'config' => [
        'config' => 'Config',
        'props' => [
            'show_celebration_in_dashboard' => 'Show Celebration in Dashboard',
        ],
    ],
];
