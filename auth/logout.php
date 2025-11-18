<?php
require_once '../includes/functions.php';
require_once '../classes/Auth.php';

$auth = new Auth();
$auth->logout();


setFlash('success', 'Logged out successfully');
redirect('/auth/login.php');