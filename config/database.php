<?php
return [
    // READ FROM .ENV FILE - DO NOT EDIT HERE DIRECTLY
    'host' => getenv('DB_HOST'),
    'dbname' => getenv('DB_NAME'),
    'user' => getenv('DB_USER'),
    'pass' => getenv('DB_PASS'),
    'options' => [
        "Database" => getenv('DB_NAME'), // Sync with dbname
        "CharacterSet" => "UTF-8",
    ]
];
