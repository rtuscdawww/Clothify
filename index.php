<?php
session_start();
// --- KHỞI TẠO BIẾN CHO PHẦN HIỂN THỊ TRÊN HEADER (SỬA LỖI UNDEFINED VARIABLE) ---
// Định nghĩa $current_role trước khi sử dụng trong HTML.
// Giả sử vai trò được lưu trong $_SESSION['user_role']. Nếu chưa có, đặt mặc định là 'user'.
if (isset($_SESSION['user_role'])) {
    $current_role = $_SESSION['user_role'];
} else {
    // Đặt mặc định là 'user' để tránh lỗi Notice: Undefined variable
    $current_role = 'user'; 
}

// --- 1. DỮ LIỆU SẢN PHẨM & LOGIC GIỎ HÀNG PHP ---

// Dữ liệu Sản phẩm (Bắt buộc phải định nghĩa trong PHP để có thể xử lý)
$all_products = [
    // Định nghĩa ID sản phẩm là key cho giỏ hàng
    1 => ['name' => 'Áo Thun Trơn - Đen', 'data_name' => 'ao thun den', 'price' => 300000, 'img' => 'assets/images/aothun/den.jpg'],
    2 => ['name' => 'Áo Sọc Đỏ - Nữ', 'data_name' => 'ao soc do nu', 'price' => 320000, 'img' => 'assets/images/aothun/socdo-nu.jpg'],
    3 => ['name' => 'Quần Ống Rộng - Đen', 'data_name' => 'quan ong rong den', 'price' => 400000, 'img' => 'assets/images/quan/quanjeans-ongrong-den.jpg'],
    4 => ['name' => 'Váy Caro - Đỏ', 'data_name' => 'vay caro do', 'price' => 390000, 'img' => 'assets/images/vay/vaycaro-do.jpg'],
    5 => ['name' => 'Jacket (Xám)', 'data_name' => 'jacket xam', 'price' => 900000, 'img' => 'assets/images/aokhoac/bomber-nau.jpg'],
];

// Khởi tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['add_to_cart']) && is_numeric($_GET['add_to_cart'])) {
    $product_id_to_add = (int)$_GET['add_to_cart'];

    if (isset($all_products[$product_id_to_add])) {
        // Lấy thông tin cần thiết từ mảng $all_products
        $product_data = $all_products[$product_id_to_add];
        $item_id = $product_id_to_add;
        
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$item_id] = [
                'name' => $product_data['name'],
                'price' => $product_data['price'],
                'quantity' => 1
            ];
        }
    }
    
    // Chuyển hướng người dùng về trang index.php để tránh việc refresh liên tục thêm sản phẩm
    header('Location: index.php');
    exit();
}

// --- 2. XỬ LÝ TÌM KIẾM VÀ SẮP XẾP PHP ---

$products_to_display = $all_products;

// 2.1. Xử lý Tìm kiếm (Theo tên sản phẩm)
$search_query = isset($_GET['search_q']) ? strtolower(trim($_GET['search_q'])) : '';

if (!empty($search_query)) {
    $filtered = [];
    $search_query_safe = $search_query;

    foreach ($products_to_display as $id => $product) {
        // Dùng strpos() để tìm kiếm chuỗi con (tương thích với PHP cũ)
        // Kiểm tra trong tên hiển thị và tên data_name không dấu
        if (strpos(strtolower($product['name']), $search_query_safe) !== false || strpos($product['data_name'], $search_query_safe) !== false) {
            $filtered[$id] = $product;
        }
    }
    $products_to_display = $filtered; // Cập nhật danh sách hiển thị
}
// ---------------------------------------------

// 2.2. Xử lý Sắp xếp (A-Z / Z-A)
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : '';

if ($sort_order) {
    usort($products_to_display, function($a, $b) use ($sort_order) {
        $nameA = strtolower($a['data_name']);
        $nameB = strtolower($b['data_name']);
        
        $comparison = strcmp($nameA, $nameB); // So sánh A-Z (Tăng dần)
        
        if ($sort_order == 'asc') {
            return $comparison; // A-Z
        } else {
            return -$comparison; // Z-A
        }
    });
}

// 2.3. Tính tổng số lượng sản phẩm trong giỏ hàng (cho hiển thị trên header)
$cart_item_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_item_count += $item['quantity'];
}

// --- 3. HIỂN THỊ HTML ---
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clothify - Shop Quần Áo Thời Trang</title>
    <link rel="stylesheet" href="assets/css/index1.css">
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
                <form action="role1.php" method="POST">
    <select name="role">
        <option value="user" <?= ($current_role == "user") ? "selected" : "" ?>>User</option>
        <option value="admin" <?= ($current_role == "admin") ? "selected" : "" ?>>Admin</option>
    </select>

    <button type="submit">Chọn</button>
</form>


                <?php
                  if (isset($_SESSION["username"])) {
                      echo "<li><a href='logout.php'>Đăng xuất (" . htmlspecialchars($_SESSION["username"]) . ")</a></li>";
                  } else {
                      echo "<li><a href='login.php'>Đăng nhập</a></li>";
                      echo "<li><a href='register.php'>Đăng ký</a></li>";
                  }
                ?>
            </ul>
        </nav>
        
        <a href="cart.php" class="cart-icon">🛒 <span id="cart-count"><?= $cart_item_count ?></span></a>
    </header>

    <section class="top-banner" id="sale">
        <form method="GET" action="index.php">
            <div class="search-box">
                <h2>Tìm kiếm sản phẩm</h2>
                <input type="text" name="search_q" id="searchInput" placeholder="🔍 Nhập tên sản phẩm..."
                       value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">Tìm</button>
            </div>
            
            <div class="sort-controls">
                <button type="submit" name="sort_order" value="asc">Sắp xếp A-Z (Tên)</button>
                <button type="submit" name="sort_order" value="desc">Sắp xếp Z-A (Tên)</button>
            </div>
        </form>
    </section>

    <section class="products" id="products">
        <h2>Sản phẩm nổi bật</h2>

        <div class="product-row">
            <?php if (empty($products_to_display)): ?>
                <p style="text-align: center; width: 100%;">Không tìm thấy sản phẩm nào.</p>
            <?php else: ?>
                <?php foreach ($products_to_display as $id => $product): ?>
                    <div class="product" data-name="<?= htmlspecialchars($product['data_name']) ?>">
                        <img src="<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p><?= number_format($product['price'], 0, ',', '.') ?>₫</p>
                        
                        <?php 
                            // Giữ lại tham số tìm kiếm/sắp xếp hiện tại trong URL sau khi thêm hàng
                            $current_params = http_build_query(['search_q' => $search_query, 'sort_order' => $sort_order]);
                            $cart_link = 'index.php?add_to_cart=' . $id . '&' . $current_params;
                        ?>
                        <a href="<?= $cart_link ?>" class="add-to-cart-link">Thêm vào giỏ</a>
                        
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer id="contact">
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>
</body>
</html>