<?php
// config/job_filters.php

return [
    // Job columns configuration
    'job_columns' => [
        'title', 'description', 'company_name', 'salary_min', 'salary_max',
        'is_remote', 'job_type', 'status', 'published_at'
    ],

    // Column types
    'numeric_columns' => ['salary_min', 'salary_max'],
    'boolean_columns' => ['is_remote'],
    'date_columns' => ['published_at', 'created_at', 'updated_at'],

    // Enum values
    'enum_columns' => [
        'job_type' => ['full-time', 'part-time', 'contract', 'freelance'],
        'status' => ['draft', 'published', 'archived']
    ],

    // Relationship configuration
    'relationship_filters' => ['locations', 'languages', 'categories'],

    // Valid operators
    'valid_operators' => [
        'string' => ['=', '!=', 'LIKE'],
        'numeric' => ['=', '!=', '>', '<', '>=', '<='],
        'boolean' => ['=', '!='],
        'enum' => ['=', '!=', 'IN'],
        'date' => ['=', '!=', '>', '<', '>=', '<='],
        'relationship' => ['HAS_ANY', 'HAS_ALL']
    ]
];
