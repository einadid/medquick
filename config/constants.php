<?php
// FILE: config/constants.php
// PURPOSE: Defines global constants for the application.

// --- Application Settings ---
define('APP_NAME', 'QuickMed');

// **CRITICAL:** Set your application's base URL here.
// For local XAMPP: http://localhost/quickmed
// For your live site: https://quickmed.free.nf
// **IMPORTANT: DO NOT add a trailing slash (/) at the end!**
define('BASE_URL', 'https://quickmed.free.nf'); // <<<--- পরিবর্তনটি এখানে


// --- User Roles ---
define('ROLE_ADMIN', 'admin');
define('ROLE_SHOP_ADMIN', 'shop_admin');
define('ROLE_SALESMAN', 'salesman');
define('ROLE_CUSTOMER', 'customer');


// --- Special Verification Codes ---
// These are used during signup to assign special roles.
define('VERIFICATION_CODE_ADMIN', 'QM-ADMIN-2025');
define('VERIFICATION_CODE_SHOP_ADMIN', 'QM-SHOPADMIN-2025');
define('VERIFICATION_CODE_SALESMAN', 'QM-SALESMAN-2025');