<?php
session_start();
session_unset();
session_destroy();
header('Location: /poultry_shop/auth/login.php');
exit;
