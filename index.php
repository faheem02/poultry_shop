<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /poultry_shop/pages/dashboard/index.php');
} else {
    header('Location: /poultry_shop/login.php');
}
exit;
