<?php
include("db_config.php"); // file kết nối database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tăng cường bảo mật: dùng prepared statements để thay thế
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $raw_password = $_POST["password"]; 
    $password = password_hash($raw_password, PASSWORD_DEFAULT);

    // Kiểm tra tên đăng nhập trùng (NÊN DÙNG PREPARED STATEMENT Ở ĐÂY)
    $stmt_check = $cn->prepare("SELECT username FROM users WHERE username=?");
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $check = $stmt_check->get_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Tên đăng nhập đã tồn tại!');</script>";
    } else {
        // Thực hiện INSERT
        $stmt_insert = $cn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("sss", $username, $email, $password);
        
        if ($stmt_insert->execute()) {
            echo "<script>alert('Đăng ký thành công! Hãy đăng nhập.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Lỗi khi đăng ký: " . $cn->error . "');</script>";
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
}
// Chú ý: Đóng kết nối ở cuối file
// $cn->close(); 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản</title>
    <link rel="stylesheet" href="assets/css/register.css"> 
</head>
<body class="login-page">
    <div class="login-container register-container">
        <h2>Đăng ký tài khoản</h2>
        <form method="post" action="">
            <label>Tên đăng nhập:</label>
            <input type="text" name="username" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Mật khẩu:</label>
            <input type="password" name="password" required>

            <button type="submit">Đăng ký</button>
        </form>

        <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
    </div>
</body>
</html>