<?php
include("db_config.php");
session_start();
if (isset($_SESSION['user_role'])) {
    $current_role = $_SESSION['user_role'];
} else {
    // Đặt mặc định là 'user' để tránh lỗi Notice: Undefined variable
    $current_role = 'user'; 
}
// --- 1. XỬ LÝ GIỎ HÀNG (PHP SESSION) ---

// Khởi tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm sản phẩm vào giỏ hàng khi có tham số 'add_to_cart'
if (isset($_GET['add_to_cart']) && is_numeric($_GET['add_to_cart'])) {
    $product_id_to_add = (int)$_GET['add_to_cart'];

    // Lấy thông tin sản phẩm từ CSDL để thêm vào giỏ hàng
    $product_info_query = "SELECT id, name, price FROM products WHERE id = $product_id_to_add";
    $product_result = $cn->query($product_info_query);

    if ($product_result && $product_result->num_rows > 0) {
        $product_data = $product_result->fetch_assoc();
        $item_id = $product_data['id'];
        
        // Cập nhật/Thêm sản phẩm vào giỏ
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$item_id] = [
                'name' => $product_data['name'],
                'price' => $product_data['price'],
                'quantity' => 1
            ];
        }
        
        // Chuyển hướng người dùng về trang products.php sau khi thêm thành công
        // Giữ lại các tham số lọc/tìm kiếm hiện tại
        $redirect_params = [];
        if (isset($_GET['slug'])) $redirect_params['slug'] = $_GET['slug'];
        if (isset($_GET['search_q'])) $redirect_params['search_q'] = $_GET['search_q'];
        if (isset($_GET['sort_order'])) $redirect_params['sort_order'] = $_GET['sort_order'];
        
        header('Location: products.php?' . http_build_query($redirect_params));
        exit();
    }
}

// --- 2. XỬ LÝ LỌC DANH MỤC, TÌM KIẾM VÀ SẮP XẾP ---

// Lấy tham số
$category_slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$search_query = isset($_GET['search_q']) ? trim($_GET['search_q']) : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'name_asc'; // Mặc định A-Z
$category_id_filter = null;
$current_category_name = "Tất cả sản phẩm";
$where_clauses = [];
$order_clause = "";

// Lấy tất cả danh mục để tạo menu lọc
$categories_result = $cn->query("SELECT id, name, slug FROM categories ORDER BY name ASC");

// 2.1. Lọc theo Danh mục (Category Filter)
if ($category_slug) {
    $safe_slug = $cn->real_escape_string($category_slug);
    $slug_info = $cn->query("SELECT id, name FROM categories WHERE slug = '$safe_slug'");
    if ($slug_info && $slug_info->num_rows > 0) {
        $category_row = $slug_info->fetch_assoc();
        $category_id_filter = $category_row['id'];
        $current_category_name = $category_row['name'];
        $where_clauses[] = "p.categories_id = " . (int)$category_id_filter; 
    }
}

// 2.2. Tìm kiếm theo Tên (Search Filter)
if (!empty($search_query)) {
    // Sử dụng LIKE để tìm kiếm không phân biệt chữ hoa/chữ thường
    $safe_search = '%' . $cn->real_escape_string($search_query) . '%';
    $where_clauses[] = "p.name LIKE '$safe_search'";
}

// 2.3. Sắp xếp (Order By)
if ($sort_order == 'name_asc') {
    $order_clause = "ORDER BY p.name ASC";
} elseif ($sort_order == 'name_desc') {
    $order_clause = "ORDER BY p.name DESC";
}
// Có thể thêm sắp xếp theo giá ở đây nếu cần (price_asc, price_desc)

// --- 3. XÂY DỰNG VÀ THỰC THI TRUY VẤN CSDL ---

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.categories_id = c.id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " " . $order_clause;

$result = $cn->query($sql);

if ($result === false) {
    die("<h3>❌ Lỗi truy vấn CSDL ❌</h3><p>Lỗi MySQL: " . $cn->error . "</p>");
}

$page_title = $current_category_name;

// Tính tổng số lượng sản phẩm trong giỏ hàng (cho hiển thị trên header)
$cart_item_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_item_count += $item['quantity'];
}

// Hàm trợ giúp để xây dựng URL cho các nút sắp xếp
function buildSortUrl($cn, $new_sort, $current_slug, $current_search) {
    $params = [];
    if ($current_slug) $params['slug'] = $current_slug;
    if ($current_search) $params['search_q'] = urlencode($current_search);
    $params['sort_order'] = $new_sort;
    return 'products.php?' . http_build_query($params);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Clothify</title>
    <link rel="stylesheet" href="assets/css/products.css">
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
                <li><a href="products.php" class="active">Sản phẩm</a></li>
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
        <h1><?= htmlspecialchars($page_title) ?></h1>
    </section>

    <section class="main-content-wrapper">
        <aside class="sidebar-filter">
            <h2>Danh mục sản phẩm</h2>
            <div class="category-filter-list">
                <a href="products.php?<?= htmlspecialchars(http_build_query(['search_q' => $search_query, 'sort_order' => $sort_order])) ?>" 
                   class="<?= $category_slug === null ? 'active' : '' ?>">Tất cả sản phẩm</a>
                <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                    <?php $categories_result->data_seek(0); ?>
                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                        <?php
                            $cat_params = ['slug' => $cat['slug']];
                            if ($search_query) $cat_params['search_q'] = $search_query;
                            if ($sort_order) $cat_params['sort_order'] = $sort_order;
                        ?>
                        <a href="products.php?<?= http_build_query($cat_params) ?>" 
                           class="<?= $category_slug == $cat['slug'] ? 'active' : '' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </aside>

        <main class="product-listing-area">
            
            <div class="listing-header">
                <h1><?= htmlspecialchars($page_title) ?></h1>
                
                <form method="GET" action="products.php" class="search-sort-controls">
                    <?php if ($category_slug): ?>
                        <input type="hidden" name="slug" value="<?= htmlspecialchars($category_slug) ?>">
                    <?php endif; ?>

                    <div class="search-box">
                        <input type="text" name="search_q" placeholder="🔍 Tìm theo tên sản phẩm..."
                               value="<?= htmlspecialchars($search_query) ?>">
                        <button type="submit">Tìm</button>
                    </div>

                    <div class="sort-controls">
                        <a href="<?= buildSortUrl($cn, 'name_asc', $category_slug, $search_query) ?>"
                           class="btn-sort <?= $sort_order == 'name_asc' ? 'active-sort' : '' ?>">Sắp xếp A-Z</a>
                        
                        <a href="<?= buildSortUrl($cn, 'name_desc', $category_slug, $search_query) ?>"
                           class="btn-sort <?= $sort_order == 'name_desc' ? 'active-sort' : '' ?>">Sắp xếp Z-A</a>
                    </div>
                </form>
                </div>

            <section class="products">
                <div class="product-row">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <div class="product">
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                                <h3><?= htmlspecialchars($row['name']) ?></h3>
                                <?php if (!empty($row['category_name'])): ?>
                                    <small>Danh mục: <?= htmlspecialchars($row['category_name']) ?></small>
                                <?php endif; ?>
                                <p><?= number_format($row['price'], 0, ',', '.') ?>₫</p>
                                
                                <?php 
                                    // Tạo lại link thêm giỏ hàng, giữ lại tất cả các tham số hiện tại
                                    $cart_params = [
                                        'add_to_cart' => $row['id'],
                                        'slug' => $category_slug,
                                        'search_q' => $search_query,
                                        'sort_order' => $sort_order
                                    ];
                                    // Loại bỏ các tham số rỗng (null hoặc empty string)
                                    $cart_params = array_filter($cart_params);
                                    $cart_link = 'products.php?' . http_build_query($cart_params);
                                ?>
                                <a href="<?= $cart_link ?>" class="add-to-cart-link">Thêm vào giỏ</a>

                                
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="width: 100%; text-align: center; margin-top: 30px;">Không tìm thấy sản phẩm nào phù hợp.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </section>
    
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