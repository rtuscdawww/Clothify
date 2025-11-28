<?php
include("db_config.php");
session_start();

// Nếu đã đăng nhập, chuyển thẳng đến trang lựa chọn quyền
if (isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($cn, $_POST["username"]);
    $password = $_POST["password"];

    $result = $cn->query("SELECT * FROM users WHERE username='$username'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            // *** Chuyển hướng đến ROLE.PHP sau khi xác thực thành công ***
            header("Location: index.php");
            exit;
            
        } else {
            echo "<script>alert('Sai mật khẩu!');</script>";
        }
    } else {
        echo "<script>alert('Tài khoản không tồn tại!');</script>";
    }
}

$cn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Đăng nhập</h2>
        <form method="post" action="">
            <label>Tên đăng nhập:</label>
            <input type="text" name="username" required>

            <label>Mật khẩu:</label>
            <input type="password" name="password" required>

            <button type="submit">Đăng nhập</button> 
        </form>

        <p>Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
        
        
    </div>
</body>
</html>