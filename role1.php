<?php
session_start();

// Sửa lỗi: Thay thế Null Coalescing Operator (??)
// bằng toán tử ternary (tương thích với PHP 5.x)
$role = isset($_POST['role']) ? $_POST['role'] : 'user'; 

// Lưu role nếu muốn
$_SESSION['role'] = $role;

// Điều hướng theo role
if ($role == "admin") {
    header("Location: products1.php");
    exit();
} else {
    header("Location: products.php");
    exit();
}
?>