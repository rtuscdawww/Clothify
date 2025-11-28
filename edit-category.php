<?php
include("db_config.php");
session_start();

// Hàm tạo slug từ tên
function createSlug($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/\s+/', '-', $str);
    return $str;
}

// ======================
// 1. XỬ LÝ THÊM DANH MỤC
// ======================
if (isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = createSlug($name);

    if ($name !== "") {
        $cn->query("INSERT INTO categories (name, slug) VALUES ('$name', '$slug')");
    }

    header("Location: edit-category.php");
    exit();
}

// ======================
// 2. XỬ LÝ SỬA DANH MỤC
// ======================
if (isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $slug = createSlug($name);

    if ($name !== "") {
        $cn->query("UPDATE categories SET name='$name', slug='$slug' WHERE id=$id");
    }

    header("Location: edit-category.php");
    exit();
}

// ======================
// 3. XỬ LÝ XÓA DANH MỤC
// ======================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Kiểm tra xem có sản phẩm thuộc danh mục không
    $check = $cn->query("SELECT COUNT(*) AS total FROM products WHERE categories_id = $id");
    $count = $check->fetch_assoc()['total'];

    if ($count == 0) {
        $cn->query("DELETE FROM categories WHERE id = $id");
    } else {
        echo "<script>alert('Không thể xóa vì vẫn còn sản phẩm thuộc danh mục này.');</script>";
    }

    header("Location: edit-category.php");
    exit();
}

// Lấy danh sách danh mục
$categories = $cn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa danh mục - Clothify</title>
    <link rel="stylesheet" href="assets/css/admin.css">

    <style>
        .category-wrapper {
            width: 90%;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th {
            background: #f8f8f8;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        .btn {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
        }

        .btn-edit {
            background: #007bff;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-add {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            display: inline-block;
            margin-top: 15px;
        }

        form {
            margin-top: 10px;
        }

        input[type="text"] {
            padding: 7px;
            width: 60%;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            padding: 7px 14px;
            background: #333;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="assets/images/logo.jpg" alt="logo">
        <span>Clothify</span>
    </div>
    <nav>
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="products1.php">Sản phẩm</a></li>
            <li><a href="clothify.php">Về Clothify</a></li>
            <li><a href="contact.php">Liên hệ</a></li>
        </ul>
    </nav>
</header>

<div class="category-wrapper">
    <h2>Chỉnh sửa danh mục</h2>

    <!-- FORM THÊM DANH MỤC -->
    <form method="POST">
        <h3>Thêm danh mục mới</h3>
        <input type="text" name="name" placeholder="Nhập tên danh mục..." required>
        <button type="submit" name="add_category">Thêm</button>
    </form>

    <h3 style="margin-top: 30px;">Danh sách danh mục</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Tên danh mục</th>
            <th>Slug</th>
            <th>Thao tác</th>
        </tr>

        <?php while ($cat = $categories->fetch_assoc()): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name']) ?></td>
                <td><?= htmlspecialchars($cat['slug']) ?></td>
                <td>
                    <!-- FORM SỬA -->
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                        <button type="submit" name="edit_category">Lưu</button>
                    </form>

                    <!-- NÚT XÓA -->
                    <a class="btn btn-delete" 
                       href="edit-category.php?delete=<?= $cat['id'] ?>"
                       onclick="return confirm('Bạn chắc muốn xóa danh mục này?')">
                        Xóa
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <a href="products1.php" class="btn-add">⬅ Quay lại danh sách sản phẩm</a>
</div>

</body>
</html>

<?php $cn->close(); ?>
