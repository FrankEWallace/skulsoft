<?php

return [
    'communication' => 'Communication',
    'config' => [
        'config' => 'Config',
    ],
    'types' => [
        'sms' => 'SMS',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
    ],
    'failed' => 'Sorry, something went wrong. Please try again later.',
    'announcement' => [
        'announcement' => 'Announcement',
        'announcements' => 'Announcements',
        'module_title' => 'Manage all Announcements',
        'module_description' => 'Publish Announcements to Students and Employees',
        'props' => [
            'code_number' => 'Announcement #',
            'title' => 'Title',
            'description' => 'Description',
            'type' => 'Type',
            'is_public' => 'Is Public',
            'audience' => 'Audience',
            'published_at' => 'Published At',
        ],
        'config' => [
            'props' => [
                'number_prefix' => 'Announcement Number Prefix',
                'number_suffix' => 'Announcement Number Suffix',
                'number_digit' => 'Announcement Number Digit',
            ],
        ],
        'type' => [
            'type' => 'Announcement Type',
            'types' => 'Announcement Types',
            'module_title' => 'Manage all Announcement Types',
            'module_description' => 'List all Announcement Types',
            'props' => [
                'name' => 'Name',
                'description' => 'Description',
            ],
        ],
    ],
    'email' => [
        'email' => 'Email',
        'emails' => 'Emails',
        'module_title' => 'Manage all Emails',
        'module_description' => 'Compose and send Emails to Students and Employees',
        'no_recipient_found' => 'No recipient found.',
        'props' => [
            'audience' => 'Audience',
            'subject' => 'Subject',
            'content' => 'Content',
            'inclusion' => 'Inclusion',
            'exclusion' => 'Exclusion',
            'recipient' => 'Recipient',
        ],
    ],
    'sms' => [
        'sms' => 'SMS',
        'module_title' => 'Manage all SMS',
        'module_description' => 'Compose and send SMS to Students and Employees',
        'no_recipient_found' => 'No recipient found.',
        'props' => [
            'audience' => 'Audience',
            'subject' => 'Subject',
            'content' => 'Content',
            'inclusion' => 'Inclusion',
            'exclusion' => 'Exclusion',
            'recipient' => 'Recipient',
        ],
    ],
    'whatsapp' => [
        'whatsapp' => 'WhatsApp',
        'module_title' => 'Manage all WhatsApp',
        'module_description' => 'Compose and send WhatsApp to Students and Employees',
        'no_recipient_found' => 'No recipient found.',
        'props' => [
            'audience' => 'Audience',
            'subject' => 'Subject',
            'content' => 'Content',
            'inclusion' => 'Inclusion',
            'exclusion' => 'Exclusion',
            'recipient' => 'Recipient',
        ],
    ],
];
