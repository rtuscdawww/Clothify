<?php
include("db_config.php");
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Xóa sản phẩm
if ($id > 0) {
    $cn->query("DELETE FROM products WHERE id=$id");
}

header("Location: products1.php");
exit();
?>
