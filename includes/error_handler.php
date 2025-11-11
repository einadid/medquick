<?php
// Custom error handler function
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Don't show errors in production
    if (ENVIRONMENT === 'production') {
        error_log("Error: [$errno] $errstr in $errfile on line $errline");
        return true;
    }
    
    // Show detailed errors in development
    echo "<div style='background-color: #ffebee; border: 1px solid #ef9a9a; padding: 15px; margin: 10px; border-radius: 4px;'>";
    echo "<h3 style='color: #c62828; margin-top: 0;'>Error [$errno]: $errstr</h3>";
    echo "<p><strong>File:</strong> $errfile</p>";
    echo "<p><strong>Line:</strong> $errline</p>";
    
    // Show backtrace
    echo "<h4>Backtrace:</h4>";
    echo "<pre>";
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    echo "</pre>";
    
    echo "</div>";
    
    return true;
}

// Custom exception handler
function customExceptionHandler($exception) {
    // Don't show exceptions in production
    if (ENVIRONMENT === 'production') {
        error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        // Show user-friendly error page
        http_response_code(500);
        include 'views/errors/500.php';
        exit;
    }
    
    // Show detailed exceptions in development
    echo "<div style='background-color: #ffebee; border: 1px solid #ef9a9a; padding: 15px; margin: 10px; border-radius: 4px;'>";
    echo "<h3 style='color: #c62828; margin-top: 0;'>Exception: " . $exception->getMessage() . "</h3>";
    echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
    
    // Show backtrace
    echo "<h4>Stack trace:</h4>";
    echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    
    echo "</div>";
}

// Set error and exception handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Enable error reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>