
<?php
// Bật hiển thị lỗi PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình MySQLi để ném ra ngoại lệ (Exceptions) khi có lỗi CSDL.
// Điều này cực kỳ quan trọng để khối try-catch hoạt động với Transaction.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include("db_config.php"); 
session_start();

// --- 0. KIỂM TRA QUYỀN ADMIN (Nếu cần) ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    // Nếu bạn không muốn ai khác ngoài Admin truy cập:
    // header('Location: index.php'); 
    // exit();
}

// --- 1. XỬ LÝ TRUY VẤN CSDL ---
// ... (Giữ nguyên đoạn truy vấn SELECT đơn hàng) ...

$sql = "SELECT 
            o.id, 
            o.fullname, 
            o.phone, 
            o.address, 
            o.total_amount, 
            o.order_date,
            o.payment_method,
            u.email as user_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC";

try {
    $orders_result = $cn->query($sql);
} catch (Exception $e) {
    die("<h3>❌ Lỗi truy vấn CSDL ❌</h3><p>Lỗi MySQL: " . $e->getMessage() . "</p>");
}

// Hàm lấy chi tiết sản phẩm của một đơn hàng (order_id)
function getOrderItems($cn, $order_id) {
    $safe_id = (int)$order_id;
    $items_sql = "SELECT product_name, price, quantity FROM order_items WHERE order_id = $safe_id";
    // Sử dụng try-catch cho hàm này để xử lý lỗi truy vấn nếu cần
    try {
        $items_result = $cn->query($items_sql);
    } catch (Exception $e) {
        // Có thể ghi log lỗi tại đây
        return [];
    }
    
    $items = [];
    if ($items_result && $items_result->num_rows > 0) {
        while ($row = $items_result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

// --- 2. XỬ LÝ HÀNH ĐỘNG (Xóa đơn hàng) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $order_id_to_delete = (int)$_GET['id'];
    
    // Bắt đầu giao dịch (Transaction)
    $cn->begin_transaction();
    try {
        // BƯỚC 1: Xóa chi tiết đơn hàng (PHẢI LÀM TRƯỚC VÌ LÀ KHÓA NGOẠI)
        // Nếu lệnh này thất bại (ví dụ: lỗi khóa ngoại), nó sẽ ném ra Exception
        $cn->query("DELETE FROM order_items WHERE order_id = $order_id_to_delete");
        
        // BƯỚC 2: Xóa đơn hàng chính
        $cn->query("DELETE FROM orders WHERE id = $order_id_to_delete");
        
        // Hoàn tất nếu cả hai đều thành công
        $cn->commit();
        
        // Sử dụng tên tệp động (tên tệp hiện tại) để chuyển hướng
        $redirect_file = basename($_SERVER['PHP_SELF']); 
        header("Location: $redirect_file?success=deleted");
        exit();
    } catch (Exception $e) {
        $cn->rollback();
        // Hiển thị lỗi ra màn hình (chỉ dùng cho Admin)
        die("<h3>❌ LỖI XÓA ĐƠN HÀNG ❌</h3><p>Lỗi CSDL: " . $e->getMessage() . 
            "</p><p>Vui lòng kiểm tra lại cấu trúc bảng `orders` và `order_items`.</p>");
    }
}

// Tính tổng số lượng sản phẩm trong giỏ hàng (cho hiển thị trên header)
$cart_item_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Hàng - Clothify Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css"> <style>
        /* CSS cụ thể cho trang đơn hàng */
        .order-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .order-container h1 {
            color: #2c3e50;
            border-bottom: 2px solid #ff8c00;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .order-table th, .order-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .order-table th {
            background-color: #f2f2f2;
            color: #1e1e1e;
        }
        .order-detail-row td {
            background-color: #fff8e1; /* Màu nền cam nhạt cho chi tiết */
            border-top: none;
        }
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 14px;
        }
        .item-list li {
            padding: 3px 0;
            border-bottom: 1px dotted #ccc;
        }
        .item-list li:last-child {
            border-bottom: none;
        }
        .btn-delete-order {
            background-color: #dc3545; /* Đỏ */
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn-delete-order:hover {
            background-color: #c82333;
        }
    </style>
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
                <li><a href="products1.php">Sản phẩm</a></li>
                <li><a href="clothify.php">Về Clothify</a></li>
                <li><a href="contact.php">Liên hệ</a></li>
                <li><a href="manage_order.php" class="active">Quản lí đơn hàng</a></li>
            </ul>
        </nav>
        <a href="cart.php" class="cart-icon">🛒 <span id="cart-count"><?= $cart_item_count ?></span></a>
    </header>

    <div class="order-container">
        <h1>Quản Lý Đơn Hàng</h1>

        <?php if ($orders_result->num_rows == 0): ?>
            <p style="text-align: center; font-size: 1.2em;">Chưa có đơn hàng nào được đặt.</p>
        <?php else: ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thông tin Khách hàng</th>
                        <th>Địa chỉ & SĐT</th>
                        <th>Thời gian Đặt</th>
                        <th>Tổng tiền</th>
                        <th>Chi tiết Sản phẩm</th>
                        <th>Thanh toán</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($order = $orders_result->fetch_assoc()): ?>
                        <?php 
                            $order_items = getOrderItems($cn, $order['id']); 
                            $user_display = htmlspecialchars($order['fullname']) . 
                                             (empty($order['user_email']) ? '' : ' (' . htmlspecialchars($order['user_email']) . ')');
                            $current_file = basename($_SERVER['PHP_SELF']); // Lấy tên file hiện tại
                        ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td>
                                <strong><?= $user_display ?></strong><br>
                                <?= htmlspecialchars(isset($order['user_email']) ? $order['user_email'] : "") ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($order['address']) ?><br>
                                SĐT: <?= htmlspecialchars($order['phone']) ?>
                            </td>
                            <td><?= date("d/m/Y H:i", strtotime($order['order_date'])) ?></td>
                            <td style="font-weight: bold; color: #e65100;">
                                <?= number_format($order['total_amount'], 0, ',', '.') ?>₫
                            </td>
                            <td>
                                <ul class="item-list">
                                    <?php foreach ($order_items as $item): ?>
                                        <li>
                                            <?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td><?= htmlspecialchars($order['payment_method']) ?></td>
                            <td>
                                <a href="<?= $current_file ?>?action=delete&id=<?= $order['id'] ?>" 
                                   class="btn-delete-order"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng #<?= $order['id'] ?>? Hành động này không thể hoàn tác.')">
                                    Xóa
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <footer>
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
$cn->close();
?>