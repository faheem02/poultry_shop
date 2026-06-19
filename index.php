<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /poultry_shop/dashboard/index.php');
} else {
    header('Location: /poultry_shop/auth/login.php');
}
exit;
