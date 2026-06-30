<?php
define('ROOT', dirname(__DIR__, 2));
define('DB_PATH', ROOT . '/data/inventory.db');
define('JAVA_API', 'http://127.0.0.1:8081');

$ROLES = [
    'manager' => 'Manager',
    'executive' => 'Executive Administration',
];