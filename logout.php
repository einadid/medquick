<?php
require_once 'src/session.php';

// সব সেশন ভেরিয়েবল মুছে ফেলা
$_SESSION = [];

// সেশন কুকি ডিলিট করা
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// অবশেষে, সেশনটি ধ্বংস করা
session_destroy();

// লগইন পেজে রিডাইরেক্ট করা
redirect('login.php');