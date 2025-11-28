<?php
session_start();
include("db_config.php"); // Đảm bảo đường dẫn này đúng

// --- 1. KIỂM TRA ĐĂNG NHẬP ---
if (!isset($_SESSION['user_id'])) {
    // Chuyển hướng người dùng về trang đăng nhập nếu chưa đăng nhập
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$orders = []; // Mảng chứa dữ liệu lịch sử đơn hàng
$error_message = null;

// --- 2. TRUY VẤN DỮ LIỆU TỪ CSDL ---

// Hàm format tiền tệ
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

if ($cn->connect_error) {
    $error_message = "Lỗi kết nối CSDL: " . $cn->connect_error;
} else {
    // Truy vấn các đơn hàng của người dùng hiện tại
    $sql_orders = "
        SELECT 
            id, total_amount, address, payment_method, 
            COALESCE(status, 'Đã đặt hàng') AS status, 
            COALESCE(order_date, NOW()) AS order_date 
        FROM orders 
        WHERE user_id = ?
        ORDER BY order_date DESC
    ";

    $stmt_orders = $cn->prepare($sql_orders);
    
    if ($stmt_orders === false) {
        $error_message = "Lỗi chuẩn bị truy vấn đơn hàng: " . $cn->error;
    } else {
        $stmt_orders->bind_param("i", $user_id);
        $stmt_orders->execute();
        $result_orders = $stmt_orders->get_result();

        // Lấy danh sách đơn hàng
        while ($order = $result_orders->fetch_assoc()) {
            $order_id = $order['id'];
            $order['items'] = []; // Thêm mảng con để chứa chi tiết sản phẩm

            // Truy vấn chi tiết sản phẩm cho từng đơn hàng
            $sql_items = "
                SELECT product_name, price, quantity 
                FROM order_items 
                WHERE order_id = ?
            ";
            $stmt_items = $cn->prepare($sql_items);
            
            if ($stmt_items === false) {
                 $order['items_error'] = "Lỗi truy vấn chi tiết sản phẩm: " . $cn->error;
            } else {
                $stmt_items->bind_param("i", $order_id);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();

                while ($item = $result_items->fetch_assoc()) {
                    $order['items'][] = $item;
                }
                $stmt_items->close();
            }

            $orders[] = $order;
        }
        $stmt_orders->close();
    }
}
$cn->close();

// --- TÍNH TOÁN CART COUNT CHO HEADER ---
// Sử dụng array_column cho PHP 5.5+
$cart_item_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$current_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đơn Hàng - Clothify</title>
    <link rel="stylesheet" href="assets/css/index1.css"> 
    <style>
        .order-history-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
        }
        .order-item {
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .order-header h3 {
            margin: 0;
            color: #ff9800; /* Màu cam */
            font-size: 1.2em;
        }
        .order-total {
            font-weight: bold;
            color: #e65100; /* Màu cam đậm */
            font-size: 1.1em;
        }
        .order-details ul {
            list-style: none;
            padding: 0;
        }
        .order-details ul li {
            padding: 5px 0;
            border-bottom: 1px dotted #f0f0f0;
            font-size: 0.95em;
        }
        .order-details ul li:last-child {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9em;
            background-color: #ffe0b2; /* Màu nền cam nhạt */
            color: #e65100;
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
                <li><a href="order_history.php" style="font-weight: bold;">Quản Lí Đơn Hàng</a></li>
                
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

    <div class="order-history-container">
        <h2>📜 Lịch Sử Đơn Hàng Của Bạn</h2>

        <?php if ($error_message): ?>
            <p style="color: red; text-align: center;"><?= htmlspecialchars($error_message) ?></p>
        <?php elseif (empty($orders)): ?>
            <p style="text-align: center; margin-top: 30px;">Bạn chưa có đơn hàng nào được ghi nhận.</p>
            <p style="text-align: center;"><a href="products.php">Bắt đầu mua sắm ngay!</a></p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-item">
                    <div class="order-header">
                        <h3>Đơn hàng #<?= htmlspecialchars($order['id']) ?></h3>
                        <span class="order-total"><?= format_currency($order['total_amount']) ?></span>
                    </div>
                    
                    <p>Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></p>
                    <p>Trạng thái: <span class="status-badge"><?= htmlspecialchars($order['status']) ?></span></p>
                    <p>Địa chỉ nhận hàng: <?= htmlspecialchars($order['address']) ?></p>
                    <p>Thanh toán: <?= htmlspecialchars($order['payment_method']) ?></p>

                    <div class="order-details">
                        <h4>Chi tiết sản phẩm:</h4>
                        <ul>
                            <?php if (!empty($order['items'])): ?>
                                <?php foreach ($order['items'] as $item): ?>
                                    <li>
                                        <?= htmlspecialchars($item['product_name']) ?> 
                                        (<?= format_currency($item['price']) ?> x <?= $item['quantity'] ?>)
                                        = <strong><?= format_currency($item['price'] * $item['quantity']) ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>Không tìm thấy chi tiết sản phẩm.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    
    <footer id="contact">
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>
</body>
</html>