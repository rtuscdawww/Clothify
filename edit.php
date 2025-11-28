<?php
include("db_config.php");
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";

// Lấy dữ liệu sản phẩm
$product_result = $cn->query("SELECT * FROM products WHERE id = $id");
if ($product_result->num_rows == 0) {
    die("Sản phẩm không tồn tại!");
}
$product = $product_result->fetch_assoc();

// Xử lý POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $cn->real_escape_string($_POST['name']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image = $cn->real_escape_string($_POST['image']);

    $sql = "UPDATE products SET name='$name', price=$price, categories_id=$category_id, image='$image' WHERE id=$id";
    if ($cn->query($sql)) {
        $message = "✅ Cập nhật sản phẩm thành công!";
        $product = ['name'=>$name, 'price'=>$price, 'categories_id'=>$category_id, 'image'=>$image];
    } else {
        $message = "❌ Lỗi: " . $cn->error;
    }
}

// Lấy danh mục để tạo dropdown
$categories_result = $cn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa sản phẩm - Clothify</title>
    <link rel="stylesheet" href="assets/css/edit.css">
</head>
<body>
    <h1>Sửa sản phẩm</h1>
    <p><?= $message ?></p>
    <form method="POST">
        <label>Tên sản phẩm: <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required></label><br>
        <label>Giá: <input type="number" name="price" value="<?= $product['price'] ?>" required min="0" step="0.01"></label><br>
        <label>Danh mục: 
            <select name="category_id" required>
                <?php while($cat = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id']==$product['categories_id']?'selected':'' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label><br>
        <label>Đường dẫn ảnh: <input type="text" name="image" value="<?= htmlspecialchars($product['image']) ?>" required></label><br>
        <button type="submit">Cập nhật sản phẩm</button>
    </form>
    <a href="products1.php">⬅ Quay lại danh sách sản phẩm</a>
</body>
</html>
