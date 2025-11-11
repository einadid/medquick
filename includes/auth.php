<?php
session_start();
require 'db_connect.php';

// লগইন চেক
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// রোল চেক
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isSalesman() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'salesman';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

// লগআউট
function logout() {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
}

// লগইন প্রসেস
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
    $stmt->execute([$username, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        // রোল অনুযায়ী ড্যাশবোর্ডে রিডিরেক্ট
        if ($user['role'] == 'admin') {
            header('Location: ../admin/index.php');
        } elseif ($user['role'] == 'salesman') {
            header('Location: ../salesman/index.php');
        } else {
            header('Location: ../customer/index.php');
        }
        exit();
    } else {
        $error = "ভুল ইউজারনেম/পাসওয়ার্ড!";
    }
}
?>