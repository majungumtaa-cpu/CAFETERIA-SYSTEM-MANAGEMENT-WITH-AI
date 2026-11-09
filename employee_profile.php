 <?php
// ================================================
// employee_profile.php - With Image Cropper (Drag + Zoom)
// ================================================
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee_login.php');
    exit();
}

$employee_id = $_SESSION['user_id'];

if (isset($_GET['id']) && intval($_GET['id']) != $employee_id) {
    die('Unauthorized access!');
}

$message = '';
$message_type = '';
$message_key = '';

// GET EMPLOYEE DATA
$employee_query = mysqli_query($conn, "SELECT * FROM employee WHERE id = $employee_id");
$employee = mysqli_fetch_assoc($employee_query);

if (!$employee) {
    die('Employee not found!');
}

// HANDLE PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));

    if (empty($full_name)) {
        $message_key = 'empprof_error_fill';
        $message_type = 'error';
    } else {
        $profile_picture_sql = '';

        // OPTION 1: CROPPED IMAGE (base64)
        if (!empty($_POST['cropped_image'])) {
            $cropped_data = $_POST['cropped_image'];

            if (preg_match('/^data:image\/(\w+);base64,/', $cropped_data, $matches)) {
                $ext = strtolower($matches[1]);
                $allowed = ['jpg', 'jpeg', 'jpe', 'jfif', 'jif', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $message_key = 'empprof_error_image_type';
                    $message_type = 'error';
                } else {
                    $image_data = substr($cropped_data, strpos($cropped_data, ',') + 1);
                    $image_data = base64_decode($image_data);

                    if ($image_data === false) {
                        $message_key = 'empprof_error_image_data';
                        $message_type = 'error';
                    } else {
                        $upload_dir = __DIR__ . '/uploads/profiles/';
                        if (!is_dir($upload_dir)) {
                            @mkdir($upload_dir, 0777, true);
                        }

                        if (!empty($employee['profile_picture'])) {
                            $old_path = __DIR__ . '/' . $employee['profile_picture'];
                            if (file_exists($old_path)) @unlink($old_path);
                        }

                        $clean_ext = 'jpg';
                        if (in_array($ext, ['png', 'gif', 'webp'])) $clean_ext = $ext;

                        $new_filename = 'emp_' . $employee_id . '_' . time() . '.' . $clean_ext;
                        $upload_path = 'uploads/profiles/' . $new_filename;
                        $full_upload_path = $upload_dir . $new_filename;

                        if (file_put_contents($full_upload_path, $image_data)) {
                            $profile_picture_sql = ", profile_picture = '$upload_path'";
                        } else {
                            $message_key = 'empprof_error_image_save';
                            $message_type = 'error';
                        }
                    }
                }
            } else {
                $message_key = 'empprof_error_image_format';
                $message_type = 'error';
            }
        }
        // OPTION 2: REGULAR FILE UPLOAD
        elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'jpe', 'jfif', 'jif', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_picture']['name'];
            $filesize = $_FILES['profile_picture']['size'];
            $filetmp = $_FILES['profile_picture']['tmp_name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $message_key = 'empprof_error_invalid_type';
                $message_type = 'error';
            } elseif ($filesize > 5 * 1024 * 1024) {
                $message_key = 'empprof_error_file_large';
                $message_type = 'error';
            } else {
                $upload_dir = __DIR__ . '/uploads/profiles/';
                if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

                if (!is_writable($upload_dir)) {
                    $message_key = 'empprof_error_not_writable';
                    $message_type = 'error';
                } else {
                    if (!empty($employee['profile_picture'])) {
                        $old_path = __DIR__ . '/' . $employee['profile_picture'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }

                    $clean_ext = 'jpg';
                    if (in_array($ext, ['png', 'gif', 'webp'])) $clean_ext = $ext;

                    $new_filename = 'emp_' . $employee_id . '_' . time() . '.' . $clean_ext;
                    $upload_path = 'uploads/profiles/' . $new_filename;
                    $full_upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($filetmp, $full_upload_path)) {
                        $profile_picture_sql = ", profile_picture = '$upload_path'";
                    } else {
                        $message_key = 'empprof_error_upload';
                        $message_type = 'error';
                    }
                }
            }
        }

        // UPDATE DATABASE
        if ($message_type !== 'error') {
            $update_query = "UPDATE employee SET 
                                full_name = '$full_name', 
                                phone = '$phone' 
                                $profile_picture_sql 
                             WHERE id = $employee_id";

            if (mysqli_query($conn, $update_query)) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['phone'] = $phone;

                $message_key = 'empprof_success_update';
                $message_type = 'success';

                $employee_query = mysqli_query($conn, "SELECT * FROM employee WHERE id = $employee_id");
                $employee = mysqli_fetch_assoc($employee_query);
            } else {
                $message = 'Error updating profile: ' . mysqli_error($conn);
                $message_type = 'error';
            }
        }
    }
}

// HANDLE PROFILE PICTURE REMOVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    if (!empty($employee['profile_picture'])) {
        $old_path = __DIR__ . '/' . $employee['profile_picture'];
        if (file_exists($old_path)) @unlink($old_path);
    }

    $update = "UPDATE employee SET profile_picture = NULL WHERE id = $employee_id";
    if (mysqli_query($conn, $update)) {
        $_SESSION['flash_message_key'] = 'empprof_success_photo_removed';
        $_SESSION['flash_type'] = 'success';
        header('Location: employee_profile.php');
        exit();
    } else {
        $message = 'Error removing picture: ' . mysqli_error($conn);
        $message_type = 'error';
    }
}

// CHECK IF PICTURE EXISTS
$has_picture = false;
$picture_url = '';

if (!empty($employee['profile_picture'])) {
    $full_path = __DIR__ . '/' . $employee['profile_picture'];
    if (file_exists($full_path)) {
        $has_picture = true;
        $site_url = defined('SITE_URL') ? SITE_URL : '';
        $picture_url = $site_url . $employee['profile_picture'] . '?t=' . time();
    }
}

// Flash message
if (isset($_SESSION['flash_message_key'])) {
    $message_key = $_SESSION['flash_message_key'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message_key'], $_SESSION['flash_type']);
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'empprof_error_fill'                => 'Please fill in all required fields.',
        'empprof_error_image_type'          => 'Invalid cropped image type.',
        'empprof_error_image_data'          => 'Invalid cropped image data.',
        'empprof_error_image_save'          => 'Error saving cropped image.',
        'empprof_error_image_format'        => 'Invalid cropped image format.',
        'empprof_error_invalid_type'        => 'Invalid file type. Only JPG, JPEG, PNG, GIF, WEBP allowed.',
        'empprof_error_file_large'          => 'File too large. Maximum 5MB.',
        'empprof_error_not_writable'        => 'Upload folder is not writable.',
        'empprof_error_upload'              => 'Error uploading file.',
        'empprof_success_update'            => 'Profile updated successfully!',
        'empprof_success_photo_removed'     => 'Profile picture removed successfully!'
    ],
    'sw' => [
        'empprof_error_fill'                => 'Tafadhali jaza sehemu zote zinazohitajika.',
        'empprof_error_image_type'          => 'Aina ya picha iliyokatwa si sahihi.',
        'empprof_error_image_data'          => 'Data ya picha iliyokatwa si sahihi.',
        'empprof_error_image_save'          => 'Kosa la kuhifadhi picha iliyokatwa.',
        'empprof_error_image_format'        => 'Muundo wa picha iliyokatwa si sahihi.',
        'empprof_error_invalid_type'        => 'Aina ya faili si sahihi. Ruhusa: JPG, JPEG, PNG, GIF, WEBP.',
        'empprof_error_file_large'          => 'Faili ni kubwa mno. Upeo 5MB.',
        'empprof_error_not_writable'        => 'Folder ya kupakia haiwezi kuandikwa.',
        'empprof_error_upload'              => 'Kosa la kupakia faili.',
        'empprof_success_update'            => 'Wasifu umesasishwa kikamilifu!',
        'empprof_success_photo_removed'     => 'Picha ya wasifu imeondolewa kikamilifu!'
    ]
];
$message = $message_key && isset($messageTranslations[$lang][$message_key])
    ? $messageTranslations[$lang][$message_key]
    : $message;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="empprof_title">My Profile - Employee Portal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="lang.js"></script>

    <style>
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }

        .avatar-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(224, 123, 57, 0.3);
            border: 4px solid #E07B39;
            display: block;
        }

        .avatar-placeholder {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(224, 123, 57, 0.3);
        }

        .avatar-placeholder i {
            font-size: 4rem;
            color: white;
        }

        .camera-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 38px;
            height: 38px;
            background: #E07B39;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            border: 3px solid white;
            transition: all 0.3s;
        }

        .camera-btn:hover { background: #C86A2F; transform: scale(1.1); }
        .camera-btn i { color: white; font-size: 1rem; }

        .cropper-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .cropper-modal.active { display: flex; }

        .cropper-box {
            background: white;
            border-radius: 15px;
            padding: 20px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .cropper-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        .cropper-header h3 {
            color: #333;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cropper-header h3 i { color: #E07B39; }

        .cropper-close {
            background: none;
            border: none;
            font-size: 26px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
            line-height: 1;
        }

        .cropper-close:hover { color: #e74c3c; }

        .cropper-image-container {
            flex: 1;
            max-height: 400px;
            overflow: hidden;
            background: #f0f0f0;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .cropper-image-container img {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .cropper-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
            justify-content: center;
        }

        .cropper-control-btn {
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.25s;
            font-family: inherit;
        }

        .cropper-control-btn:hover {
            background: #E07B39;
            color: white;
        }

        .zoom-slider-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding: 0 5px;
        }

        .zoom-slider-container label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
            white-space: nowrap;
        }

        .zoom-slider {
            flex: 1;
            height: 6px;
            border-radius: 3px;
            background: #e0e0e0;
            outline: none;
            -webkit-appearance: none;
        }

        .zoom-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #E07B39;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(224, 123, 57, 0.4);
        }

        .zoom-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #E07B39;
            cursor: pointer;
            border: none;
        }

        .cropper-actions {
            display: flex;
            gap: 10px;
        }

        .cropper-actions button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s;
            font-family: inherit;
        }

        .btn-crop-save {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
        }

        .btn-crop-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(224, 123, 57, 0.4);
        }

        .btn-crop-cancel {
            background: #95a5a6;
            color: white;
        }

        .btn-crop-cancel:hover { background: #7f8c8d; }

        .crop-info {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }

        @media (max-width: 600px) {
            .cropper-box { padding: 15px; }
            .cropper-image-container { max-height: 300px; }
            .cropper-header h3 { font-size: 1em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'employee_sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>
                    <i class="fas fa-user-cog"></i>
                    <span data-i18n="empprof_title">My Profile</span>
                </h1>
                <span data-i18n="empprof_subtitle">Manage your profile information</span>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: 20px;">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-user-circle"></i>
                        <span data-i18n="empprof_employee_info">Employee Information</span>
                    </h2>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 40px; margin-bottom: 30px;">
                    <!-- PROFILE AVATAR -->
                    <div style="text-align: center;">
                        <div class="avatar-container">
                            <?php if ($has_picture): ?>
                                <img src="<?php echo htmlspecialchars($picture_url); ?>"
                                     alt="Profile Picture" class="avatar-img" id="currentAvatar">
                            <?php else: ?>
                                <div class="avatar-placeholder" id="currentAvatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>

                            <label for="profile_picture_input" class="camera-btn" title="Change profile picture">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>

                        <h3 style="margin-top: 10px; color: #333;">
                            <?php echo htmlspecialchars($employee['full_name']); ?>
                        </h3>
                        <p style="color: #E07B39; margin: 5px 0;">
                            <i class="fas fa-id-badge"></i>
                            <span data-i18n="empprof_id">ID:</span>
                            <?php echo $employee['id']; ?>
                        </p>

                        <?php if ($has_picture): ?>
                        <form method="POST" style="margin-top: 10px;">
                            <button type="submit" name="remove_picture"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('<?= $lang === 'sw' ? 'Ondoa picha yako ya wasifu?' : 'Remove your profile picture?' ?>');"
                                    style="font-size: 12px; padding: 5px 12px;">
                                <i class="fas fa-trash"></i>
                                <span data-i18n="empprof_remove_photo">Remove Photo</span>
                            </button>
                        </form>
                        <?php endif; ?>

                        <div style="margin-top: 20px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                            <div style="margin: 10px 0; color: #666;">
                                <strong data-i18n="empprof_account_status">Account Status:</strong>
                                <span style="color: #2ecc71; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i>
                                    <span data-i18n="empprof_active">Active</span>
                                </span>
                            </div>
                            <div style="margin: 10px 0; color: #666;">
                                <strong data-i18n="empprof_member_since">Member Since:</strong>
                                <span data-i18n="empprof_today">Today</span>
                            </div>
                        </div>
                    </div>

                    <!-- EDIT PROFILE FORM -->
                    <div>
                        <form method="POST" action="" enctype="multipart/form-data" id="profileForm">
                            <input type="hidden" name="cropped_image" id="croppedImageInput">

                            <input type="file"
                                   id="profile_picture_input"
                                   accept="image/*"
                                   style="display: none;"
                                   onchange="openCropper(this)">

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user"></i>
                                    <span data-i18n="empprof_full_name">Full Name</span>
                                </label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-phone"></i>
                                    <span data-i18n="empprof_phone">Phone Number</span>
                                </label>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-briefcase"></i>
                                    <span data-i18n="empprof_position">Position</span>
                                </label>
                                <input type="text" class="form-control" value="Employee" disabled>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-image"></i>
                                    <span data-i18n="empprof_profile_picture">Profile Picture</span>
                                </label>
                                <input type="file" id="profile_picture_main"
                                       class="form-control" accept="image/*"
                                       onchange="openCropper(this)">
                                <small style="color: #888; font-size: 12px;" data-i18n="empprof_max_size">
                                    Max: 5MB | Formats: JPG, JPEG, PNG, GIF, WEBP
                                </small>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    <span data-i18n="empprof_save">Save Changes</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #E07B39;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                        $orders_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM `order` WHERE employee_id = $employee_id"))['count'];
                        echo $orders_count;
                        ?>
                    </div>
                    <div class="stat-label" data-i18n="empprof_orders_created">Orders Created</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #2ecc71;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                        $revenue_data = mysqli_fetch_assoc(mysqli_query($conn, "
                            SELECT SUM(od.quantity * p.price) as total 
                            FROM `order` o
                            JOIN order_detail od ON o.id = od.order_id
                            JOIN product p ON od.product_id = p.id
                            WHERE o.employee_id = $employee_id
                        "));
                        echo '$' . number_format($revenue_data['total'] ?? 0, 2);
                        ?>
                    </div>
                    <div class="stat-label" data-i18n="empprof_total_revenue">Total Revenue</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                        $feedback_count = mysqli_fetch_assoc(mysqli_query($conn, "
                            SELECT COUNT(*) as count FROM feedback f
                            WHERE f.customer_id IN (SELECT DISTINCT customer_id FROM `order` WHERE employee_id = $employee_id)
                        "))['count'];
                        echo $feedback_count;
                        ?>
                    </div>
                    <div class="stat-label" data-i18n="empprof_feedback_received">Feedback Received</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value">
                        <?php
                        $avg_rating = mysqli_fetch_assoc(mysqli_query($conn, "
                            SELECT AVG(rating) as avg_rating FROM feedback f
                            WHERE f.customer_id IN (SELECT DISTINCT customer_id FROM `order` WHERE employee_id = $employee_id)
                        "))['avg_rating'];
                        echo number_format($avg_rating ?? 0, 1);
                        ?>
                        /5
                    </div>
                    <div class="stat-label" data-i18n="empprof_avg_rating">Average Rating</div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-link"></i>
                        <span data-i18n="empprof_quick_links">Quick Links</span>
                    </h2>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <a href="employee_orders.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                        <i class="fas fa-shopping-cart"></i><br>
                        <span data-i18n="empprof_my_orders">My Orders</span>
                    </a>
                    <a href="employee_shifts.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                        <i class="fas fa-clock"></i><br>
                        <span data-i18n="empprof_my_shifts">My Shifts</span>
                    </a>
                    <a href="employee_feedback.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                        <i class="fas fa-comments"></i><br>
                        <span data-i18n="empprof_feedback">Feedback</span>
                    </a>
                    <a href="employee_dashboard.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                        <i class="fas fa-home"></i><br>
                        <span data-i18n="empprof_dashboard">Dashboard</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- CROPPER MODAL -->
    <div class="cropper-modal" id="cropperModal">
        <div class="cropper-box">
            <div class="cropper-header">
                <h3>
                    <i class="fas fa-crop-alt"></i>
                    <span data-i18n="empprof_crop_title">Adjust Your Photo</span>
                </h3>
                <button class="cropper-close" onclick="closeCropper()">&times;</button>
            </div>

            <p class="crop-info" data-i18n="empprof_crop_hint">
                Drag to move | Scroll to zoom
            </p>

            <div class="cropper-image-container">
                <img id="cropperImage" src="" alt="Image to crop">
            </div>

            <div class="cropper-controls">
                <button type="button" class="cropper-control-btn" onclick="rotateImage(-90)">
                    <i class="fas fa-undo"></i>
                    <span data-i18n="empprof_rotate_left">Rotate Left</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="rotateImage(90)">
                    <i class="fas fa-redo"></i>
                    <span data-i18n="empprof_rotate_right">Rotate Right</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="flipImage('h')">
                    <i class="fas fa-arrows-alt-h"></i>
                    <span data-i18n="empprof_flip_h">Flip H</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="flipImage('v')">
                    <i class="fas fa-arrows-alt-v"></i>
                    <span data-i18n="empprof_flip_v">Flip V</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="resetCropper()">
                    <i class="fas fa-sync-alt"></i>
                    <span data-i18n="empprof_reset">Reset</span>
                </button>
            </div>

            <div class="zoom-slider-container">
                <label><i class="fas fa-search-minus"></i></label>
                <input type="range" class="zoom-slider" id="zoomSlider"
                       min="0.1" max="3" step="0.01" value="1"
                       oninput="zoomImage(this.value)">
                <label><i class="fas fa-search-plus"></i></label>
            </div>

            <div class="cropper-actions">
                <button type="button" class="btn-crop-cancel" onclick="closeCropper()">
                    <i class="fas fa-times"></i>
                    <span data-i18n="empprof_cancel">Cancel</span>
                </button>
                <button type="button" class="btn-crop-save" onclick="applyCrop()">
                    <i class="fas fa-check"></i>
                    <span data-i18n="empprof_apply">Apply & Preview</span>
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <script>
        var cropper = null;
        var cropperModal = document.getElementById('cropperModal');
        var cropperImage = document.getElementById('cropperImage');

        function openCropper(input) {
            if (!input.files || !input.files[0]) return;

            var file = input.files[0];

            if (file.size > 5 * 1024 * 1024) {
                alert('<?= $lang === "sw" ? "Faili ni kubwa mno. Upeo 5MB." : "File too large. Maximum 5MB." ?>');
                input.value = '';
                return;
            }

            var allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (allowed.indexOf(file.type) === -1) {
                alert('<?= $lang === "sw" ? "Aina ya faili si sahihi. Ruhusa: JPG, PNG, GIF, WEBP." : "Invalid file type. Only JPG, PNG, GIF, WEBP allowed." ?>');
                input.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                cropperImage.src = e.target.result;
                cropperModal.classList.add('active');

                setTimeout(function() {
                    if (cropper) cropper.destroy();

                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        background: false,
                        responsive: true,
                        zoomable: true,
                        scalable: true,
                        rotatable: true,
                        checkOrientation: false,
                        zoomOnWheel: true,
                        wheelZoomRatio: 0.1,
                        crop: function(event) {
                            var data = cropper.getImageData();
                            var ratio = data.width / data.naturalWidth;
                            document.getElementById('zoomSlider').value = ratio;
                        }
                    });
                }, 200);
            };
            reader.readAsDataURL(file);
        }

        function closeCropper() {
            cropperModal.classList.remove('active');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('profile_picture_input').value = '';
            document.getElementById('profile_picture_main').value = '';
        }

        function rotateImage(degree) { if (cropper) cropper.rotate(degree); }

        function flipImage(direction) {
            if (!cropper) return;
            var data = cropper.getData();
            if (direction === 'h') {
                cropper.scaleX(data.scaleX === -1 ? 1 : -1);
            } else {
                cropper.scaleY(data.scaleY === -1 ? 1 : -1);
            }
        }

        function zoomImage(ratio) { if (cropper) cropper.zoomTo(parseFloat(ratio)); }

        function resetCropper() {
            if (cropper) {
                cropper.reset();
                document.getElementById('zoomSlider').value = 1;
            }
        }

        function applyCrop() {
            if (!cropper) return;

            var canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });

            var croppedDataUrl = canvas.toDataURL('image/jpeg', 0.9);
            document.getElementById('croppedImageInput').value = croppedDataUrl;

            var currentAvatar = document.getElementById('currentAvatar');
            if (currentAvatar) {
                if (currentAvatar.tagName === 'IMG') {
                    currentAvatar.src = croppedDataUrl;
                } else if (currentAvatar.tagName === 'DIV') {
                    var newImg = document.createElement('img');
                    newImg.src = croppedDataUrl;
                    newImg.className = 'avatar-img';
                    newImg.id = 'currentAvatar';
                    currentAvatar.parentNode.replaceChild(newImg, currentAvatar);
                }
            }

            closeCropper();
            console.log('Image cropped and ready to save!');
        }

        cropperModal.addEventListener('click', function(e) {
            if (e.target === cropperModal) closeCropper();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && cropperModal.classList.contains('active')) {
                closeCropper();
            }
        });

        window.addEventListener('languageChanged', () => {
            if (typeof applyTranslations === 'function') {
                applyTranslations();
            }
        });
    </script>
</body>
</html>