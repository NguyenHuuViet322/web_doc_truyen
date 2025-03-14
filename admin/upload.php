<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $author = $_POST['author'];

    // Xử lý upload ảnh bìa
    $cover_image = $_FILES['cover_image'];
    $cover_name = time() . '_' . $cover_image['name'];
    move_uploaded_file($cover_image['tmp_name'], '../uploads/comics/' . $cover_name);

    // Thêm truyện mới vào database
    $stmt = $conn->prepare("INSERT INTO comics (title, description, author, cover_image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $author, $cover_name]);
    $comic_id = $conn->lastInsertId();

    // Xử lý upload chapter
    if (isset($_FILES['chapter_images'])) {
        $chapter_images = $_FILES['chapter_images'];
        $image_names = [];

        foreach ($chapter_images['tmp_name'] as $key => $tmp_name) {
            $image_name = time() . '_' . $chapter_images['name'][$key];
            move_uploaded_file($tmp_name, '../uploads/chapters/' . $image_name);
            $image_names[] = $image_name;
        }

        // Lưu chapter vào database
        $stmt = $conn->prepare("INSERT INTO chapters (comic_id, chapter_number, images) VALUES (?, 1, ?)");
        $stmt->execute([$comic_id, json_encode($image_names)]);

        // Cập nhật latest_chapter
        $stmt = $conn->prepare("UPDATE comics SET latest_chapter = 1 WHERE id = ?");
        $stmt->execute([$comic_id]);
    }

    header('Location: manage-comics.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Truyện Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-image {
            max-width: 200px;
            margin: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Upload Truyện Mới</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Tên truyện</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Mô tả</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <div class="mb-3">
                <label>Tác giả</label>
                <input type="text" name="author" class="form-control">
            </div>
            <div class="mb-3">
                <label>Ảnh bìa</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*" required>
                <div id="cover-preview"></div>
            </div>
            <div class="mb-3">
                <label>Ảnh chapter 1</label>
                <input type="file" name="chapter_images[]" class="form-control" accept="image/*" multiple required>
                <div id="chapter-preview"></div>
            </div>
            <button type="submit" class="btn btn-primary">Upload Truyện</button>
        </form>
    </div>

    <script>
        // Preview ảnh bìa
        document.querySelector('input[name="cover_image"]').addEventListener('change', function(e) {
            const preview = document.getElementById('cover-preview');
            preview.innerHTML = '';
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });

        // Preview ảnh chapter
        document.querySelector('input[name="chapter_images[]"]').addEventListener('change', function(e) {
            const preview = document.getElementById('chapter-preview');
            preview.innerHTML = '';
            const files = e.target.files;
            for (let file of files) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'preview-image';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>