<?php
session_start();      // Bắt đầu session
session_unset();      // Xóa toàn bộ session
session_destroy();    // Hủy session

// Quay về trang đăng nhập
header("Location: login.php");
exit();
?>
