<?php
session_start();
include("db_config.php"); // Kết nối cơ sở dữ liệu

// 1. Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// 2. Tính tổng tiền giỏ hàng
$cart_total = 0;
foreach ($_SESSION['cart'] as $item) {
    $price = isset($item['price']) ? (float)$item['price'] : 0;
    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
    $cart_total += $price * $quantity;
}

// Hàm format tiền tệ
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . '₫';
}

// 3. Xử lý đặt hàng (Lưu CSDL và chuyển hướng)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Kiểm tra kết nối CSDL
    if ($cn->connect_error) die("Kết nối CSDL thất bại: " . $cn->connect_error);

    // Xử lý dữ liệu đầu vào
    $fullname = $cn->real_escape_string($_POST['fullname']);
    $phone = $cn->real_escape_string($_POST['phone']);
    $email = $cn->real_escape_string($_POST['email']);
    $address = $cn->real_escape_string($_POST['address']);
    $payment_method = $cn->real_escape_string($_POST['payment_method']);
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // --- LƯU BẢNG ORDERS ---
    $stmt = null;
    $sql_order = "";

    if ($user_id === null) {
        $sql_order = "
            INSERT INTO orders (fullname, email, phone, address, payment_method, total_amount)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $cn->prepare($sql_order);
        
        // KIỂM TRA LỖI SAU KHI PREPARE
        if ($stmt === false) {
            die("Lỗi SQL khi chuẩn bị (Orders-Guest): " . $cn->error . " | Query: " . $sql_order);
        }
        
        $stmt->bind_param("sssssd", $fullname, $email, $phone, $address, $payment_method, $cart_total);
    } else {
        $sql_order = "
            INSERT INTO orders (user_id, fullname, email, phone, address, payment_method, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $cn->prepare($sql_order);
        
        // KIỂM TRA LỖI SAU KHI PREPARE
        if ($stmt === false) {
            die("Lỗi SQL khi chuẩn bị (Orders-User): " . $cn->error . " | Query: " . $sql_order);
        }
        
        $stmt->bind_param("isssssd", $user_id, $fullname, $email, $phone, $address, $payment_method, $cart_total);
    }

    if ($stmt->execute()) {
        $order_id = $cn->insert_id;
        $stmt->close();

        // --- LƯU BẢNG ORDER_ITEMS ---
        $sql_item = "
            INSERT INTO order_items (order_id, product_name, price, quantity)
            VALUES (?, ?, ?, ?)
        ";
        $stmt_item = $cn->prepare($sql_item);
        
        // KIỂM TRA LỖI SAU KHI PREPARE
        if ($stmt_item === false) {
            die("Lỗi SQL khi chuẩn bị (Order_Items): " . $cn->error . " | Query: " . $sql_item);
        }

        foreach ($_SESSION['cart'] as $item) {
            $product_name = $cn->real_escape_string($item['name']);
            $price = (float)$item['price'];
            $quantity = (int)$item['quantity'];
            $stmt_item->bind_param("isdi", $order_id, $product_name, $price, $quantity);
            if (!$stmt_item->execute()) {
                echo "Lỗi lưu chi tiết đơn hàng! Order ID: $order_id. Lỗi: " . $stmt_item->error;
                exit();
            }
        }
        $stmt_item->close();

        // Lưu session đơn hàng tạm thời & xóa giỏ hàng
        $_SESSION['order_details'] = [
            'fullname' => $fullname,
            'email' => $email,
            'total' => $cart_total,
            'items' => $_SESSION['cart']
        ];
        unset($_SESSION['cart']);

        header('Location: order_success.php'); // CHUYỂN HƯỚNG
        exit();
    } else {
        echo "Lỗi tạo đơn hàng: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thanh Toán - Clothify</title>
<link rel="stylesheet" href="assets/css/index1.css">
<style>
body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f9f9f9; }
header { display:flex; justify-content:space-between; align-items:center; padding:15px 50px; background:#fff; border-bottom:1px solid #ddd; }
.logo img { height:50px; vertical-align:middle; }
.logo span { font-size:24px; font-weight:bold; margin-left:10px; color:#333; }
nav ul { list-style:none; margin:0; padding:0; display:flex; gap:20px; }
nav ul li a { text-decoration:none; color:#333; font-weight:500; }
.cart-icon { font-size:20px; text-decoration:none; color:#333; position:relative; }
#cart-count { background:#ff9800; color:#fff; font-size:12px; padding:2px 6px; border-radius:50%; position:absolute; top:-8px; right:-10px; }

.checkout-container {
    max-width:1000px; margin:40px auto; padding:20px;
    display:flex; gap:30px;
}
.billing-details, .order-summary { padding:20px; border-radius:6px; }
.billing-details { flex:2; background:#fff; border:1px solid #ddd; }
.order-summary { flex:1; background:#fff3e0; border:1px solid #ff9800; }
.billing-details h2, .order-summary h2 { color:#ff9800; border-bottom:2px solid #ddd; padding-bottom:10px; margin-bottom:20px; }
.form-group { margin-bottom:15px; }
.form-group label { display:block; margin-bottom:5px; font-weight:600; }
.form-group input, .form-group textarea, .form-group select { width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; }
.order-table { width:100%; border-collapse: collapse; margin-bottom:20px; }
.order-table th, .order-table td { padding:8px 0; border-bottom:1px dashed #ccc; font-size:0.9em; }
.order-total { font-size:1.5em; font-weight:bold; color:#e65100; margin-top:15px; text-align:right; }
.btn-place-order { width:100%; padding:15px; background:#ff9800; color:#fff; border:none; border-radius:5px; font-size:1.1em; cursor:pointer; margin-top:20px; }
.btn-place-order:hover { background:#e65100; }
footer { text-align:center; padding:20px; background:#fff; border-top:1px solid #ddd; font-size:14px; color:#555; margin-top:50px; }
</style>
</head>
<body>

<header>
<div class="logo"><img src="assets/images/logo.jpg" alt="logo"><span>Clothify</span></div>
<nav>
<ul>
<li><a href="index.php">Trang chủ</a></li>
<li><a href="products.php">Sản phẩm</a></li>
<li><a href="clothify.php">Về Clothify</a></li>
<li><a href="contact.php">Liên hệ</a></li>
<?php if(isset($_SESSION["username"])): ?>
<li><a href="logout.php">Đăng xuất (<?=htmlspecialchars($_SESSION["username"])?>)</a></li>
<?php else: ?>
<li><a href="login.php">Đăng nhập</a></li>
<li><a href="register.php">Đăng ký</a></li>
<?php endif; ?>
</ul>
</nav>
<a href="cart.php" class="cart-icon">🛒 <span id="cart-count"><?=isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'quantity')) : 0?></span></a>
</header>

<div class="checkout-container">
<div class="billing-details">
<h2>Chi tiết Thanh toán & Giao hàng</h2>
<form method="POST" action="">
<div class="form-group"><label for="fullname">Họ và Tên (*)</label>
<input type="text" id="fullname" name="fullname" required value="<?=isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : ''?>"></div>
<div class="form-group"><label for="phone">Số điện thoại (*)</label>
<input type="tel" id="phone" name="phone" required></div>
<div class="form-group"><label for="email">Email (*)</label>
<input type="email" id="email" name="email" required></div>
<div class="form-group"><label for="address">Địa chỉ Giao hàng (*)</label>
<textarea id="address" name="address" rows="3" required></textarea></div>
<div class="form-group"><label for="payment_method">Phương thức Thanh toán (*)</label>
<select id="payment_method" name="payment_method" required>
<option value="cod">Thanh toán khi nhận hàng (COD)</option>
<option value="bank_transfer">Chuyển khoản Ngân hàng</option>
<option value="visa">Thẻ Visa/Mastercard (chưa hỗ trợ)</option>
</select></div>
<button type="submit" name="place_order" class="btn-place-order">HOÀN TẤT ĐẶT HÀNG</button>
</form>
</div>

<div class="order-summary">
<h2>Đơn hàng của bạn</h2>
<table class="order-table">
<thead><tr><th>Sản phẩm</th><th style="text-align:right;">Thành tiền</th></tr></thead>
<tbody>
<?php foreach($_SESSION['cart'] as $item): ?>
<tr>
<td><?=htmlspecialchars($item['name'])?> (x<?=$item['quantity']?>)</td>
<td style="text-align:right"><?=format_currency($item['price']*$item['quantity'])?></td>
</tr>
<?php endforeach; ?>
<tr>
<td style="font-weight:bold; border-top:1px solid #333;">Tạm tính:</td>
<td style="text-align:right; font-weight:bold; border-top:1px solid #333;"><?=format_currency($cart_total)?></td>
</tr>
</tbody>
</table>
<div class="order-total">Tổng cộng: <?=format_currency($cart_total)?></div>
</div>
</div>

<footer>
<p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
<p>© 2025 Clothify Fashion. All rights reserved.</p>
</footer>

</body>
</html>