<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /poultry_shop/auth/login.php');
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: /poultry_shop/auth/login.php?expired=1');
    exit;
}

$_SESSION['last_activity'] = time();
