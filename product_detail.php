<?php
include("db_config.php");
session_start();

// Thiết lập role mặc định
$current_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'; 

// --- 1. XỬ LÝ GIỎ HÀNG (PHP SESSION) ---

// Khởi tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm sản phẩm vào giỏ hàng khi submit form POST
// CHỈ KIỂM TRA DỮ LIỆU TỪ $_POST (đúng với method="POST" của form)
if (isset($_POST['add_to_cart']) && is_numeric($_POST['add_to_cart'])) {
    $product_id_to_add = (int)$_POST['add_to_cart'];
    $quantity_to_add = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity_to_add < 1) $quantity_to_add = 1;

    // Lấy thông tin sản phẩm từ CSDL để thêm vào giỏ hàng
    $product_info_query = "SELECT id, name, price FROM products WHERE id = $product_id_to_add";
    $product_result = $cn->query($product_info_query);

    if ($product_result && $product_result->num_rows > 0) {
        $product_data = $product_result->fetch_assoc();
        $item_id = $product_data['id'];
        
        // Cập nhật/Thêm sản phẩm vào giỏ
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] += $quantity_to_add;
        } else {
            $_SESSION['cart'][$item_id] = [
                'name' => $product_data['name'],
                'price' => $product_data['price'],
                'quantity' => $quantity_to_add
            ];
        }
        
        // Chuyển hướng người dùng về trang chi tiết sản phẩm sau khi thêm thành công (PRG pattern)
        // Dùng header('Location: ...') để tránh việc thêm sản phẩm lặp lại khi F5
        header('Location: product_detail.php?id=' . $item_id);
        exit();
    }
}


// --- 2. XỬ LÝ LẤY THÔNG TIN CHI TIẾT SẢN PHẨM ---

// Lấy ID sản phẩm từ URL (sử dụng 'id')
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    header('Location: products.php');
    exit();
}

// Truy vấn thông tin chi tiết sản phẩm
$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p 
        LEFT JOIN categories c ON p.categories_id = c.id
        WHERE p.id = $product_id";

$result = $cn->query($sql);

if ($result === false) {
    die("<h3>❌ Lỗi truy vấn CSDL ❌</h3><p>Lỗi MySQL: " . $cn->error . "</p>");
}

if ($result->num_rows == 0) {
    $page_title = "Không tìm thấy sản phẩm";
    $product = null;
} else {
    $product = $result->fetch_assoc();
    $page_title = $product['name'];
}

// Tính tổng số lượng sản phẩm trong giỏ hàng
$cart_item_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_item_count += $item['quantity'];
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Clothify</title>
    <link rel="stylesheet" href="assets/css/products.css"> 
    <link rel="stylesheet" href="assets/css/product_detail.css"> 
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

    <section class="page-title">
        <h1>Chi tiết sản phẩm</h1>
    </section>

    <main class="product-detail-container">
        <?php if ($product): ?>
            <div class="product-image-area">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <div class="product-info-area">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                
                <?php if (!empty($product['category_name'])): ?>
                    <p class="category-link">
                        Danh mục: 
                        <a href="products.php?slug=<?= htmlspecialchars($product['category_slug']) ?>">
                            <?= htmlspecialchars($product['category_name']) ?>
                        </a>
                    </p>
                <?php endif; ?>

                <p class="price"><?= number_format($product['price'], 0, ',', '.') ?>₫</p>
                
                <div class="description">
                    <h3>Mô tả sản phẩm</h3>
                    <p>
    <?php echo nl2br(htmlspecialchars(isset($product['description']) ? $product['description'] : 'Sản phẩm này chưa có mô tả chi tiết.')); ?>
</p>
                </div>

                <form method="POST" action="product_detail.php?id=<?= $product['id'] ?>" class="add-to-cart-form">
                    <input type="hidden" name="add_to_cart" value="<?= $product['id'] ?>"> 
                    <div class="quantity-control">
                        <label for="quantity">Số lượng:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="100" required>
                    </div>
                    <button type="submit" class="btn-add-to-cart">🛒 Thêm vào Giỏ hàng</button>
                </form>
            </div>
        <?php else: ?>
            <p class="not-found-message">Không tìm thấy sản phẩm này. Vui lòng quay lại <a href="products.php">trang sản phẩm</a>.</p>
        <?php endif; ?>
    </main>
    
    <footer id="contact">
        <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
        <p>© 2025 Clothify Fashion. All rights reserved.</p>
    </footer>

    <script src="js/script.js"></script> 
</body>
</html>
<?php
$cn->close();
?>