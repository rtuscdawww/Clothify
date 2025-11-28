<?php
include("db_config.php");
session_start();

// Kiểm tra xem người dùng đã đăng nhập chưa (có thể kiểm tra admin)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy và làm sạch dữ liệu
    $name = $cn->real_escape_string($_POST['name']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image = $cn->real_escape_string($_POST['image']); // Đường dẫn hình ảnh

    // 🚩 BƯỚC SỬA 1: Thêm xác thực để kiểm tra giá không âm
    if ($price < 0) {
        $message = "❌ Lỗi: Giá sản phẩm không được là số âm!";
    } else {
        // Sử dụng Prepared Statement để bảo mật hơn (rất khuyến nghị)
        // Thay vì nối chuỗi trực tiếp (như code gốc), tôi sẽ dùng Prepared Statement 
        // để cải thiện bảo mật (ngăn chặn SQL Injection) và đảm bảo kiểu dữ liệu.

        $sql = "INSERT INTO products (name, price, categories_id, image) 
                VALUES (?, ?, ?, ?)";
        
        if ($stmt = $cn->prepare($sql)) {
            // "sdis" - string, double/float, integer, string
            $stmt->bind_param("sdis", $name, $price, $category_id, $image);

            if ($stmt->execute()) {
                $message = "✅ Thêm sản phẩm thành công!";
            } else {
                $message = "❌ Lỗi thực thi: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "❌ Lỗi chuẩn bị câu lệnh: " . $cn->error;
        }
    }
}

// Lấy danh mục để tạo dropdown
// Dùng Prepared Statement cho truy vấn SELECT đơn giản này cũng là một thói quen tốt, 
// nhưng trong trường hợp này, nó không xử lý đầu vào người dùng nên ít rủi ro hơn.
$categories_result = $cn->query("SELECT id, name FROM categories ORDER BY name ASC");

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm sản phẩm - Clothify</title>
    <link rel="stylesheet" href="assets/css/add.css">
    <style>
        /* Tùy chỉnh nhỏ để hiển thị rõ thông báo lỗi */
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Thêm sản phẩm mới</h1>
    <p class="<?php echo (strpos($message, '✅') !== false) ? 'success' : 'error'; ?>">
        <?= $message ?>
    </p>
    <form method="POST">
        <label>Tên sản phẩm: <input type="text" name="name" required></label><br>
        <label>Giá: <input type="number" name="price" required min="0" step="0.01"></label><br> 
        <label>Danh mục: 
            <select name="category_id" required>
                <?php 
                // Xử lý lỗi nếu không có kết quả
                if ($categories_result && $categories_result->num_rows > 0): 
                    while($cat = $categories_result->fetch_assoc()): 
                ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php 
                    endwhile; 
                else:
                ?>
                    <option value="" disabled>Không có danh mục nào</option>
                <?php endif; ?>
            </select>
        </label><br>
        <label>Đường dẫn ảnh: <input type="text" name="image" required></label><br>
        <button type="submit">Thêm sản phẩm</button>
    </form>
    <a href="products1.php">⬅ Quay lại danh sách sản phẩm</a>
</body>
</html>