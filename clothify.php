<?php
session_start();
if (isset($_SESSION['user_role'])) {
    $current_role = $_SESSION['user_role'];
} else {
    // Đặt mặc định là 'user' để tránh lỗi Notice: Undefined variable
    $current_role = 'user'; 
}
// --- BỔ SUNG: KHỞI TẠO VÀ TÍNH TOÁN GIỎ HÀNG CHO HEADER ---

// Khởi tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tính tổng số lượng sản phẩm trong giỏ hàng (khắc phục lỗi Notice)
$cart_item_count = 0;
if (is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        // Giả định mỗi item có key 'quantity'
        if (isset($item['quantity'])) {
            $cart_item_count += $item['quantity'];
        }
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
    <title>Về Clothify - Thời Trang Cho Mọi Cá Tính</title>
    <link rel="stylesheet" href="assets/css/index1.css"> 
    <style>
        /* Bố cục riêng cho trang Về Clothify */
        .content-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        .content-container h1 {
            font-size: 36px;
            color: #1e1e1e;
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #FF9800;
            padding-bottom: 10px;
        }
        .intro-text {
            font-size: 17px;
            text-align: center;
            margin-bottom: 50px;
        }
        .pillars {
            display: flex;
            justify-content: space-around;
            gap: 30px;
            margin-bottom: 50px;
        }
        .pillar-card {
            background: #fff3e0;
            border: 1px solid #FF9800;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            flex: 1;
        }
        .pillar-card h3 {
            color: #e65100;
            font-size: 24px;
            margin-bottom: 15px;
            border-bottom: 1px solid #FF9800;
            padding-bottom: 5px;
        }
        .commitment {
            margin-top: 50px;
            padding: 30px;
            background: #1e1e1e;
            color: white;
            border-radius: 10px;
        }
        .commitment h2 {
            color: #FF9800;
            margin-bottom: 15px;
        }
        .thank-you {
            text-align: center;
            margin-top: 40px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="assets/images/logo.jpg" alt="logo">
            <span>Clothify</span>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="products.php">Sản phẩm</a></li>
                <li><a href="clothify.php" class="active">Về Clothify</a></li>
                <li><a href="contact.php">Liên hệ</a></li>
               
                
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

    <section class="content-container">
        <h1>Về Clothify - Thời Trang Cho Mọi Cá Tính</h1>
        
        <p class="intro-text">
            Clothify được thành lập năm 2020 với sứ mệnh mang đến những bộ trang phục không chỉ hợp thời trang mà còn phản ánh đúng cá tính của người mặc. Chúng tôi tin rằng thời trang là một hình thức tự thể hiện bản thân, và mọi người đều xứng đáng có những lựa chọn chất lượng với giá cả phải chăng.
        </p>

        <div class="pillars">
            <div class="pillar-card">
                <h3>Sứ mệnh</h3>
                <p>Cung cấp các sản phẩm may mặc bền vững, sáng tạo, và đa dạng, giúp khách hàng tự tin tỏa sáng mỗi ngày.</p>
            </div>
            <div class="pillar-card">
                <h3>Tầm nhìn</h3>
                <p>Trở thành thương hiệu thời trang online hàng đầu tại Việt Nam, được yêu thích nhờ chất lượng dịch vụ và sự tôn trọng khách hàng.</p>
            </div>
        </div>

        <div class="commitment">
            <h2>Cam kết của chúng tôi</h2>
            <p>Chúng tôi cam kết 100% về chất lượng vải, đường may tỉ mỉ và chính sách đổi trả linh hoạt trong vòng 30 ngày. Đội ngũ chăm sóc khách hàng luôn sẵn sàng hỗ trợ bạn 24/7.</p>
        </div>

        <p class="thank-you">Cảm ơn bạn đã tin tưởng và đồng hành cùng Clothify!</p>

    </section>

    <footer id="contact-info">
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>

</body>
</html>