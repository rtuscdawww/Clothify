<?php
include("db_config.php");
session_start();
if (isset($_SESSION['user_role'])) {
    $current_role = $_SESSION['user_role'];
} else {
    // Đặt mặc định là 'user' để tránh lỗi Notice: Undefined variable
    $current_role = 'user'; 
}
// --- BỔ SUNG: Tính tổng số lượng sản phẩm cho Header ---
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cart_item_count += $item['quantity'];
        }
    }
}

$message = ""; // Biến lưu trữ thông báo
$cn_is_open = true; // Theo dõi trạng thái kết nối

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Lấy và làm sạch dữ liệu
    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $message_content = trim($_POST["message"]);

    // Kiểm tra kết nối trước khi sử dụng real_escape_string
    if (isset($cn) && $cn) {
        // Bảo mật: Sử dụng real_escape_string để ngăn chặn SQL Injection
        $safe_fullname = $cn->real_escape_string($fullname);
        $safe_email = $cn->real_escape_string($email);
        $safe_message = $cn->real_escape_string($message_content);

        // 2. Xây dựng và thực thi truy vấn INSERT
        $query = "INSERT INTO contact (fullname, email, message) 
                  VALUES ('$safe_fullname', '$safe_email', '$safe_message')";

        if ($cn->query($query) === TRUE) {
            $message = "success"; // Đánh dấu thành công
        } else {
            $message = "error"; // Đánh dấu thất bại
            // Ghi log lỗi để kiểm tra: error_log("MySQL Error: " . $cn->error);
        }
        
        // Đóng kết nối ngay sau khi thực thi truy vấn để giải phóng tài nguyên
        $cn->close(); 
        $cn_is_open = false;
    } else {
        $message = "error"; // Lỗi kết nối CSDL
    }
}

// Lấy thông tin session cho header
$username = isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : null;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - Clothify</title>
    <link rel="stylesheet" href="assets/css/contact.css"> 
</head>
<body>
    <header>
        <div class="logo">
            <img src="assets/images/logo.jpg" alt="logo" onerror="this.onerror=null; this.src='https://placehold.co/35x35/000000/FFFFFF?text=Logo';">
            <span>Clothify</span>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="products.php">Sản phẩm</a></li>
                <li><a href="clothify.php">Về Clothify</a></li>
                <li><a href="contact.php" class="active">Liên hệ</a></li>
               
                
                <?php if ($username): ?>
                    <li><a href="logout.php">Đăng xuất (<?= $username ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Đăng nhập</a></li>
                    <li><a href="register.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <a href="cart.php" class="cart-icon">🛒 <span id="cart-count"><?= $cart_item_count ?></span></a>
    </header>

    <section class="main-content-wrapper centered-page">
        <div class="contact-container form-card">
            <h2>💌 Liên hệ với Clothify</h2>
            <p>Chúng tôi luôn sẵn lòng lắng nghe ý kiến của bạn. Vui lòng điền vào mẫu dưới đây.</p>

            <?php if ($message === "success"): ?>
                <div class="alert success-alert">
                    Gửi liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.
                </div>
            <?php elseif ($message === "error"): ?>
                <div class="alert error-alert">
                    Đã xảy ra lỗi khi gửi liên hệ. Vui lòng kiểm tra kết nối CSDL và thử lại sau.
                </div>
            <?php endif; ?>

            <form method="post" action="contact.php">
                <label for="fullname">Họ và tên:</label>
                <input type="text" id="fullname" name="fullname" required> 

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required> 

                <label for="message">Nội dung chi tiết:</label>
                <textarea id="message" name="message" rows="6" required></textarea> 

                <button type="submit">Gửi liên hệ</button>
            </form>
        </div>
    </section>

    <footer id="contact-info">
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
// Đảm bảo kết nối database được đóng nếu nó vẫn mở (chỉ là biện pháp an toàn cuối cùng)
if (isset($cn) && $cn_is_open) {
    $cn->close();
}
?>