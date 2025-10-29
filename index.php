<?php
/**
 * Root index.php - Forwards requests to public/index.php
 *
 * This file ensures that accessing the domain root works correctly
 * even if .htaccess is not processed by the server.
 *
 * For Hostinger: This is a fallback if Document Root cannot be set to public/
 */

// Forward the request to public/index.php
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

chdir(__DIR__ . '/public');
require __DIR__ . '/public/index.php';
