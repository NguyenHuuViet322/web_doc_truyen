<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$success = '';
$error = '';

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Cập nhật cài đặt website
        if (isset($_POST['update_settings'])) {
            $site_name = trim($_POST['site_name']);
            $site_description = trim($_POST['site_description']);
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            $items_per_page = (int)$_POST['items_per_page'];

            // Validate
            if (empty($site_name)) {
                throw new Exception('Tên website không được để trống');
            }

            if ($items_per_page < 1) {
                throw new Exception('Số item trên mỗi trang phải lớn hơn 0');
            }

            // Cập nhật logo nếu có
            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
                $logo = uploadFile($_FILES['site_logo'], '../uploads/settings/');
                if ($logo) {
                    $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'site_logo'");
                    $stmt->execute([$logo]);
                }
            }

            // Cập nhật các cài đặt khác
            $settings = [
                'site_name' => $site_name,
                'site_description' => $site_description,
                'maintenance_mode' => $maintenance_mode,
                'items_per_page' => $items_per_page
            ];

            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = ?");
                $stmt->execute([$value, $key]);
            }

            $success = 'Cập nhật cài đặt thành công!';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Lấy cài đặt hiện tại
$settings = [];
$stmt = $conn->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['name']] = $row['value'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Cài đặt hệ thống</h1>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Tên website</label>
                                <input type="text" class="form-control" name="site_name"
                                    value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Mô tả website</label>
                                <textarea class="form-control" name="site_description" rows="3"><?php
                                                                                                echo htmlspecialchars($settings['site_description'] ?? '');
                                                                                                ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Logo website</label>
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/settings/<?php echo $settings['site_logo']; ?>"
                                            alt="Logo" style="max-height: 50px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="site_logo" accept="image/*">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số item trên mỗi trang</label>
                                <input type="number" class="form-control" name="items_per_page"
                                    value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>">
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="maintenance_mode"
                                    <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Bật chế độ bảo trì</label>
                            </div>

                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu thay đổi
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>