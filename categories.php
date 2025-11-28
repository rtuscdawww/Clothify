<?php
include("db_config.php"); // Kết nối Database
session_start();

// Lấy category_id từ URL. Nếu không có, mặc định là NULL (hiển thị tất cả)
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Lấy tất cả danh mục để tạo Menu lọc
$categories_result = $cn->query("SELECT * FROM categories ORDER BY name ASC");

// Xây dựng truy vấn SQL để lấy sản phẩm
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";

if ($category_id) {
    // Nếu có category_id, thêm điều kiện lọc
    $sql .= " WHERE p.category_id = $category_id";
}

$result = $cn->query($sql);

// Đóng kết nối
$cn->close();

// Tiêu đề trang
$page_title = $category_id ? "Sản phẩm theo danh mục" : "Tất cả sản phẩm";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Clothify</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    </head>

<body>

<header>
    <div class="logo">
        <img src="code/poster-thoi-trang-la-gi-bi-kip-thiet-ke-poster-thoi-trang-an-tuong-4.png" alt="logo">
        <span>Clothify</span>
    </div>

    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="products.php" class="active">Sản phẩm</a></li>
            <li><a href="sale.php">Khuyến mãi</a></li>
            <li><a href="contact.php">Liên hệ</a></li>

            <?php
            if (isset($_SESSION["username"])) {
                echo "<li><a href='logout.php'>Đăng xuất (" . $_SESSION["username"] . ")</a></li>";
            } else {
                echo "<li><a href='login.php'>Đăng nhập</a></li>";
                echo "<li><a href='register.php'>Đăng ký</a></li>";
            }
            ?>
        </ul>
    </nav>

    <div class="cart-icon" onclick="toggleCart()">🛒 <span id="cart-count">0</span></div>
</header>


<section class="page-title">
    <h1><?= $page_title ?></h1>
</section>

<section class="category-filter">
    <h2>Lọc theo danh mục:</h2>
    <a href="products.php" class="<?= $category_id === null ? 'active' : '' ?>">Tất cả</a>
    <?php while($cat = $categories_result->fetch_assoc()): ?>
        <a href="products.php?category_id=<?= $cat['id'] ?>" 
           class="<?= $category_id == $cat['id'] ? 'active' : '' ?>">
            <?= $cat['name'] ?>
        </a>
    <?php endwhile; ?>
</section>


<section class="products">
    <div class="product-row">

    <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="product">
                <img src="<?= $row['image'] ?>" alt="<?= $row['name'] ?>">
                <h3><?= $row['name'] ?></h3>
                <?php if ($row['category_name']): ?>
                    <small>Danh mục: <?= $row['category_name'] ?></small>
                <?php endif; ?>
                <p><?= number_format($row['price'], 0, ',', '.') ?>₫</p>
                <button onclick="addToCart('<?= $row['name'] ?>', <?= $row['price'] ?>)">Thêm vào giỏ</button>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Không tìm thấy sản phẩm nào trong danh mục này.</p>
    <?php endif; ?>

    </div>
</section>


<div class="cart" id="cart">
    <h2>🛍️ Giỏ Hàng</h2>
    <ul id="cart-items"></ul>
    <p id="cart-total">Tổng: 0₫</p>
    <button onclick="checkout()">Thanh Toán</button>
</div>


<footer id="contact">
    <p>📞 0909 123 456 • ✉️ support@clothify.vn</p>
    <p>© 2025 Clothify Fashion. All rights reserved.</p>
</footer>

<script src="js/script.js"></script>
</body>
</html>