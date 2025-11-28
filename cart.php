<?php
session_start();
// Đảm bảo file db_config.php được include nếu bạn cần kết nối CSDL tại đây.
// include("db_config.php"); 

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- XỬ LÝ HÀNH ĐỘNG GIỎ HÀNG (Cập nhật, Xóa) ---

// 1. Xử lý Xóa sản phẩm
if (isset($_GET['remove_item']) && isset($_SESSION['cart'][$_GET['remove_item']])) {
    $item_id = $_GET['remove_item'];
    unset($_SESSION['cart'][$item_id]);
    header('Location: cart.php'); 
    exit();
}

// 2. Xử lý Cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $id => $quantity) {
        $id = (int)$id;
        $quantity = (int)$quantity;

        if (isset($_SESSION['cart'][$id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$id]['quantity'] = $quantity;
            } else {
                // Xóa sản phẩm nếu số lượng là 0
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    header('Location: cart.php'); 
    exit();
}

// --- TÍNH TOÁN TỔNG TIỀN ---
$cart_total = 0;
$cart_item_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_item_count += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng Của Bạn - Clothify</title>
    <link rel="stylesheet" href="assets/css/index1.css"> 
    <style>
        /* CSS cụ thể cho trang Giỏ hàng */
        .cart-container h1 {
               color: #FF9800;
               
               font-size: 32px; /* Có thể điều chỉnh kích thước */
               margin-bottom: 20px;
               padding-bottom: 10px;
               border-bottom: 2px solid #ddd; /* Đường kẻ dưới để phân tách */
     }
        .cart-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .cart-table th, .cart-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .cart-table th {
            background-color: #f2f2f2;
            color: #1e1e1e;
        }
        .cart-summary {
            margin-top: 20px;
            padding: 20px;
            background-color: #fff3e0; /* Cam nhạt */
            border: 1px solid #ff9800; /* Viền cam */
            border-radius: 6px;
            text-align: right;
        }
        .cart-summary h3 {
            margin: 0 0 10px 0;
            color: #1e1e1e;
        }
        .cart-summary strong {
            font-size: 24px;
            color: #e65100;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .action-buttons button, .action-buttons a {
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-update {
            background-color: #1e1e1e;
            color: white;
            border: none;
        }
        .btn-update:hover { background-color: #333; }

        .btn-checkout {
            background-color: #ff9800;
            color: white;
            border: none;
        }
        .btn-checkout:hover { background-color: #e65100; }

        .btn-continue {
            background-color: #ccc;
            color: #333;
            border: none;
        }
        .btn-continue:hover { background-color: #bbb; }

        .btn-remove {
            color: red;
            text-decoration: none;
            font-size: 14px;
            margin-left: 10px;
            padding: 5px;
        }
        input[type="number"] {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="assets\images\logo.jpg" alt="logo">
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

    <div class="cart-container">
        <h1>🛍️ Giỏ Hàng Của Bạn</h1>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <p style="text-align: center; margin-top: 30px; font-size: 1.2em;">Giỏ hàng của bạn hiện đang trống. <a href="products.php">Tiếp tục mua sắm ngay!</a></p>
        <?php else: ?>
            
            <form method="POST" action="cart.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= number_format($item['price'], 0, ',', '.') ?>₫</td>
                                <td>
                                    <input type="number" 
                                           name="qty[<?= $id ?>]" 
                                           value="<?= htmlspecialchars($item['quantity']) ?>" 
                                           min="0">
                                </td>
                                <td>
                                    <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫
                                </td>
                                <td>
                                    <a href="cart.php?remove_item=<?= $id ?>" class="btn-remove">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="action-buttons">
                    <a href="products.php" class="btn-continue">Tiếp tục mua sắm</a>
                    <button type="submit" name="update_cart" class="btn-update">Cập nhật Giỏ hàng</button>
                </div>
            </form>

            <div class="cart-summary">
                <h3>Tổng Cộng:</h3>
                <strong><?= number_format($cart_total, 0, ',', '.') ?>₫</strong>
                <a href="checkout.php" class="action-buttons btn-checkout">Tiến hành Thanh toán</a>
            </div>

        <?php endif; ?>
    </div>

    <footer>
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>
</body>
</html>