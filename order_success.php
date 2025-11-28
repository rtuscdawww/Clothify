<?php
session_start();

// Kiểm tra xem có thông tin đơn hàng vừa được tạo không
if (!isset($_SESSION['order_details'])) {
    header('Location: index.php');
    exit();
}

// Lấy thông tin đơn hàng
$order = $_SESSION['order_details'];

// Có thể xóa thông tin đơn hàng tạm thời nếu muốn
// unset($_SESSION['order_details']);

// Hàm định dạng tiền tệ
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

// Tính tổng số lượng sản phẩm
$item_count = 0;
foreach ($order['items'] as $item) {
    $item_count += $item['quantity'];
}

// Cập nhật số lượng giỏ hàng trên header
$cart_item_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng Thành công - Clothify</title>
    <link rel="stylesheet" href="assets/css/index1.css">
    <style>
        /*
        * Đã xóa CSS cho header/logo/nav/cart-icon để sử dụng style từ index1.css
        */
        body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f9f9f9; }

        .success-container {
            max-width:800px;
            margin:50px auto;
            padding:30px;
            background:#e8f5e9;
            border:1px solid #4CAF50;
            border-radius:10px;
            text-align:center;
            box-shadow:0 4px 15px rgba(0,0,0,0.1);
        }
        .success-container h1 { color:#4CAF50; font-size:36px; margin-bottom:10px; }
        .success-container p { font-size:1.1em; color:#333; margin-bottom:20px; }
        .order-details-box {
            background:#fff;
            border:1px solid #ddd;
            padding:20px;
            margin-top:20px;
            text-align:left;
            border-radius:6px;
        }
        .order-details-box h3 {
            border-bottom:1px dashed #ccc;
            padding-bottom:10px;
            color:#ff9800;
        }
        .order-details-box strong { color:#e65100; }
        .btn-home {
            display:inline-block;
            padding:12px 30px;
            background-color:#ff9800;
            color:#fff;
            text-decoration:none;
            border-radius:5px;
            font-weight:600;
            margin-top:30px;
            transition: background-color 0.3s;
        }
        .btn-home:hover { background-color:#e65100; }

        /*
        * Đã xóa CSS cho footer để sử dụng style từ index1.css
        */
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="assets/images/logo.jpg" alt="Clothify Logo">
        <span>Clothify</span>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="products.php">Sản phẩm</a></li>
            <li><a href="clothify.php">Về Clothify</a></li>
            <li><a href="contact.php">Liên hệ</a></li>
            <?php if (isset($_SESSION["username"])): ?>
                <li><a href="logout.php">Đăng xuất (<?= htmlspecialchars($_SESSION["username"]) ?>)</a></li>
            <?php else: ?>
                <li><a href="login.php">Đăng nhập</a></li>
                <li><a href="register.php">Đăng ký</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <a href="cart.php" class="cart-icon">🛒 <span id="cart-count"><?= $cart_item_count ?></span></a>
</header>

<div class="success-container">
    <h1>🎉 Đặt hàng Thành công!</h1>
    <p>Cảm ơn bạn, <strong><?= htmlspecialchars($order['fullname']) ?></strong>! Đơn hàng của bạn đã được tiếp nhận.</p>
    <p>Chúng tôi đã gửi email xác nhận đến địa chỉ <strong><?= htmlspecialchars($order['email']) ?></strong>.</p>

    <div class="order-details-box">
        <h3>Tóm tắt Đơn hàng</h3>
        <p><strong>Tổng cộng (<?= $item_count ?> sản phẩm):</strong> <?= format_currency($order['total']) ?></p>
        <p><strong>Sản phẩm:</strong></p>
        <ul>
            <?php foreach ($order['items'] as $item): ?>
                <li><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>) - <?= format_currency($item['price'] * $item['quantity']) ?></li>
            <?php endforeach; ?>
        </ul>
        <p style="margin-top:15px;">Chúng tôi sẽ liên hệ để xác nhận đơn hàng sớm nhất.</p>
    </div>

    <a href="index.php" class="btn-home">Quay lại Trang chủ</a>
</div>

<footer>
    <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
    <p>© 2025 Clothify Fashion. All rights reserved.</p>
</footer>

</body>
</html>