<?php
require_once __DIR__ . '/config/db.php';
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pages/catalog.php');
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
}
exit;
