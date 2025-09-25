<?php
// Environment configuration
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development'); // Set via environment variable, defaults to development

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306'); // Based on your SQL dump - update as needed
define('DB_NAME', getenv('DB_NAME') ?: 'byp');
define('DB_USER', getenv('DB_USER') ?: 'root'); // Update as needed
define('DB_PASS', getenv('DB_PASS') ?: 'root'); // Update as needed - default for MAMP

// Site configuration
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost:8000'); // Adjust to your server
define('SITE_NAME', getenv('SITE_NAME') ?: 'Jupiter PHP Project');

// Security settings
define('HASH_COST', 12); // Increased for better security
define('TOKEN_LENGTH', 32);

// Security defaults
define('HTTPS_ONLY', filter_var(getenv('HTTPS_ONLY') ?: (ENVIRONMENT === 'production'), FILTER_VALIDATE_BOOLEAN));
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_ME_LIFETIME', 1209600); // 2 weeks (in seconds)
