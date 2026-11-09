<?php
// ================================================
// admin_profile.php - With Image Upload & Cropper
// ================================================
require_once 'config.php';
check_login();

// Prevent employees from accessing this page
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'employee') {
    header('Location: employee_dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$message_key = '';
$admin_id = $_SESSION['user_id'];

// ================================================
// FETCH ADMIN DATA
// ================================================
$admin_query = mysqli_query($conn, "SELECT * FROM user WHERE id = $admin_id");

if (!$admin_query) {
    die("SQL Error: " . mysqli_error($conn));
}

$admin = mysqli_fetch_assoc($admin_query);

if (!$admin) {
    die("Admin not found in database.");
}

// ================================================
// HANDLE PROFILE UPDATE
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
    $profile_picture_sql = '';

    if (empty($username)) {
        $message_key = 'admprof_error_username_empty';
        $message_type = 'error';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM user WHERE username = '$username' AND id != $admin_id");
        if (mysqli_num_rows($check) > 0) {
            $message_key = 'admprof_error_username_taken';
            $message_type = 'error';
        } else {
            // ========== HANDLE IMAGE (cropped base64) ==========
            if (!empty($_POST['cropped_image'])) {
                $cropped_data = $_POST['cropped_image'];

                if (preg_match('/^data:image\/(\w+);base64,/', $cropped_data, $matches)) {
                    $ext = strtolower($matches[1]);
                    $allowed = ['jpg', 'jpeg', 'jpe', 'jfif', 'jif', 'png', 'gif', 'webp'];

                    if (!in_array($ext, $allowed)) {
                        $message_key = 'admprof_error_image_type';
                        $message_type = 'error';
                    } else {
                        $image_data = substr($cropped_data, strpos($cropped_data, ',') + 1);
                        $image_data = base64_decode($image_data);

                        if ($image_data === false) {
                            $message_key = 'admprof_error_image_data';
                            $message_type = 'error';
                        } else {
                            $upload_dir = __DIR__ . '/uploads/profiles/';
                            if (!is_dir($upload_dir)) {
                                @mkdir($upload_dir, 0777, true);
                            }

                            if (!empty($admin['profile_picture'])) {
                                $old = __DIR__ . '/' . $admin['profile_picture'];
                                if (file_exists($old)) {
                                    @unlink($old);
                                }
                            }

                            $clean_ext = 'jpg';
                            if (in_array($ext, ['png', 'gif', 'webp'])) {
                                $clean_ext = $ext;
                            }

                            $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $clean_ext;
                            $upload_path = 'uploads/profiles/' . $new_filename;
                            $full_upload_path = $upload_dir . $new_filename;

                            if (file_put_contents($full_upload_path, $image_data)) {
                                $profile_picture_sql = ", profile_picture = '$upload_path'";
                            } else {
                                $message_key = 'admprof_error_image_save';
                                $message_type = 'error';
                            }
                        }
                    }
                }
            }

            // ========== UPDATE DATABASE ==========
            if ($message_type !== 'error') {
                $update = "UPDATE user SET username = '$username' $profile_picture_sql WHERE id = $admin_id";

                if (mysqli_query($conn, $update)) {
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $username;
                    $message_key = 'admprof_success_profile';
                    $message_type = 'success';

                    $admin_query = mysqli_query($conn, "SELECT * FROM user WHERE id = $admin_id");
                    $admin = mysqli_fetch_assoc($admin_query);

                    if (function_exists('log_activity')) {
                        log_activity($conn, 'update', 'user', $admin_id, 'Profile updated');
                    }
                } else {
                    $message = 'Error: ' . mysqli_error($conn);
                    $message_type = 'error';
                }
            }
        }
    }
}

// ================================================
// HANDLE PASSWORD CHANGE
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $password_matches = false;
    if (password_verify($current_password, $admin['password'])) {
        $password_matches = true;
    } elseif ($current_password === $admin['password']) {
        $password_matches = true;
    }

    if (!$password_matches) {
        $message_key = 'admprof_error_pw_wrong';
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message_key = 'admprof_error_pw_short';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message_key = 'admprof_error_pw_mismatch';
        $message_type = 'error';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = "UPDATE user SET password = '$hashed' WHERE id = $admin_id";
        if (mysqli_query($conn, $update)) {
            $message_key = 'admprof_success_pw';
            $message_type = 'success';
            if (function_exists('log_activity')) {
                log_activity($conn, 'update', 'user', $admin_id, 'Password changed');
            }
        } else {
            $message = 'Error: ' . mysqli_error($conn);
            $message_type = 'error';
        }
    }
}

// ================================================
// HANDLE PICTURE REMOVE
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    if (!empty($admin['profile_picture'])) {
        $old = __DIR__ . '/' . $admin['profile_picture'];
        if (file_exists($old)) {
            @unlink($old);
        }
    }
    $update = "UPDATE user SET profile_picture = NULL WHERE id = $admin_id";
    if (mysqli_query($conn, $update)) {
        $_SESSION['flash_message_key'] = 'admprof_success_photo_removed';
        $_SESSION['flash_type'] = 'success';
        header('Location: admin_profile.php');
        exit();
    }
}

// ================================================
// CHECK IF PICTURE EXISTS
// ================================================
$has_picture = false;
$picture_url = '';

if (!empty($admin['profile_picture'])) {
    $full_path = __DIR__ . '/' . $admin['profile_picture'];
    if (file_exists($full_path)) {
        $has_picture = true;
        $site_url = defined('SITE_URL') ? SITE_URL : '';
        $picture_url = $site_url . $admin['profile_picture'] . '?t=' . time();
    }
}

// Flash message
if (isset($_SESSION['flash_message_key'])) {
    $message_key = $_SESSION['flash_message_key'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message_key']);
    unset($_SESSION['flash_type']);
}

// ✅ Load language
$lang = $_SESSION['lang'] ?? 'en';

// ✅ Message translations
$messageTranslations = [
    'en' => [
        'admprof_error_username_empty'   => 'Username cannot be empty!',
        'admprof_error_username_taken'   => 'Username is already taken!',
        'admprof_error_image_type'       => 'Invalid cropped image type.',
        'admprof_error_image_data'       => 'Invalid cropped image data.',
        'admprof_error_image_save'       => 'Error saving cropped image.',
        'admprof_success_profile'        => 'Profile updated successfully!',
        'admprof_error_pw_wrong'         => 'Current password is incorrect!',
        'admprof_error_pw_short'         => 'Password must be at least 6 characters!',
        'admprof_error_pw_mismatch'      => 'Passwords do not match!',
        'admprof_success_pw'             => 'Password changed successfully!',
        'admprof_success_photo_removed'  => 'Profile picture removed!'
    ],
    'sw' => [
        'admprof_error_username_empty'   => 'Jina la mtumiaji haliwezi kuwa tupu!',
        'admprof_error_username_taken'   => 'Jina la mtumiaji linatumika tayari!',
        'admprof_error_image_type'       => 'Aina ya picha iliyokatwa si sahihi.',
        'admprof_error_image_data'       => 'Data ya picha iliyokatwa si sahihi.',
        'admprof_error_image_save'       => 'Kosa la kuhifadhi picha iliyokatwa.',
        'admprof_success_profile'        => 'Wasifu umesasishwa kikamilifu!',
        'admprof_error_pw_wrong'         => 'Neno la siri la sasa si sahihi!',
        'admprof_error_pw_short'         => 'Neno la siri liwe na herufi 6 au zaidi!',
        'admprof_error_pw_mismatch'      => 'Maneno ya siri hayafanani!',
        'admprof_success_pw'             => 'Neno la siri limebadilishwa kikamilifu!',
        'admprof_success_photo_removed'  => 'Picha ya wasifu imeondolewa!'
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
    <title data-i18n="admprof_title">Admin Profile - Cafeteria Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <script src="lang.js"></script>

    <style>
        .profile-container { max-width: 800px; margin: 0 auto; }

        .profile-card {
            background: white; border-radius: 8px; padding: 30px;
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 30px; padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .profile-avatar-container {
            position: relative; width: 100px; height: 100px;
        }

        .profile-avatar-img {
            width: 100px; height: 100px; border-radius: 50%;
            object-fit: cover; border: 4px solid #E07B39;
            box-shadow: 0 4px 15px rgba(224, 123, 57, 0.3); display: block;
        }

        .profile-avatar-placeholder {
            width: 100px; height: 100px;
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; color: white; font-size: 2.5rem;
            border: 4px solid #E07B39;
            box-shadow: 0 4px 15px rgba(224, 123, 57, 0.3);
        }

        .camera-btn {
            position: absolute; bottom: 0; right: 0;
            width: 32px; height: 32px; background: #E07B39;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; cursor: pointer;
            border: 3px solid white; transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .camera-btn:hover { background: #C86A2F; transform: scale(1.1); }
        .camera-btn i { color: white; font-size: 0.85rem; }

        .profile-info h2 { margin: 0 0 5px 0; color: #333; }
        .profile-info p { margin: 0; color: #999; font-size: 0.9rem; }

        .form-section { margin-bottom: 30px; }
        .form-section h3 {
            color: #333; font-size: 1.1rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }

        .form-group { margin-bottom: 15px; }
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: #333; font-size: 0.95rem;
        }
        .form-group label i { color: #E07B39; margin-right: 5px; }

        .form-group input {
            width: 100%; padding: 12px; border: 2px solid #ecf0f1;
            border-radius: 6px; font-size: 0.95rem; transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus { outline: none; border-color: #E07B39; }

        .alert {
            padding: 15px; border-radius: 6px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .btn-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }

        .btn {
            padding: 12px 24px; border: none; border-radius: 6px;
            font-weight: 600; cursor: pointer; transition: all 0.3s;
            display: inline-flex; align-items: center; gap: 8px;
            font-family: inherit; font-size: 0.95rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(224, 123, 57, 0.3);
        }
        .btn-danger {
            background: #dc3545; color: white;
        }
        .btn-danger:hover { background: #c82333; }

        .back-link {
            display: inline-block; margin-bottom: 20px; color: #E07B39;
            text-decoration: none; font-weight: 600; transition: all 0.3s;
        }
        .back-link:hover { color: #C86A2F; }

        .divider { height: 1px; background: #f0f0f0; margin: 30px 0; }

        .cropper-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .cropper-modal.active { display: flex; }

        .cropper-box {
            background: white; border-radius: 15px; padding: 20px;
            width: 100%; max-width: 550px;
            max-height: 90vh;
            display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .cropper-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 15px;
            padding-bottom: 12px; border-bottom: 2px solid #f0f0f0;
        }

        .cropper-header h3 {
            color: #333; font-size: 1.1em;
            display: flex; align-items: center; gap: 8px;
        }
        .cropper-header h3 i { color: #E07B39; }

        .cropper-close {
            background: none; border: none; font-size: 26px;
            cursor: pointer; color: #999; line-height: 1;
        }
        .cropper-close:hover { color: #e74c3c; }

        .cropper-image-container {
            flex: 1; max-height: 380px;
            background: #f0f0f0; border-radius: 10px;
            margin-bottom: 15px; overflow: hidden;
            display: flex; justify-content: center; align-items: center;
        }

        .cropper-image-container img {
            max-width: 100%; max-height: 100%; display: block;
        }

        .cropper-controls {
            display: flex; gap: 8px; flex-wrap: wrap;
            margin-bottom: 15px; justify-content: center;
        }

        .cropper-control-btn {
            background: #f0f0f0; border: none; border-radius: 8px;
            padding: 8px 12px; cursor: pointer; font-size: 12px;
            font-weight: 600; color: #333;
            display: inline-flex; align-items: center; gap: 5px;
            transition: all 0.25s;
            font-family: inherit;
        }
        .cropper-control-btn:hover { background: #E07B39; color: white; }

        .zoom-slider-container {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 15px; padding: 0 5px;
        }
        .zoom-slider-container label {
            font-size: 13px; color: #666; font-weight: 600;
        }

        .zoom-slider {
            flex: 1; height: 6px; border-radius: 3px;
            background: #e0e0e0; outline: none;
            -webkit-appearance: none;
        }
        .zoom-slider::-webkit-slider-thumb {
            -webkit-appearance: none; width: 20px; height: 20px;
            border-radius: 50%; background: #E07B39;
            cursor: pointer; box-shadow: 0 2px 6px rgba(224, 123, 57, 0.4);
        }
        .zoom-slider::-moz-range-thumb {
            width: 20px; height: 20px; border-radius: 50%;
            background: #E07B39; cursor: pointer; border: none;
        }

        .cropper-actions {
            display: flex; gap: 10px;
        }
        .cropper-actions button {
            flex: 1; padding: 12px; border: none;
            border-radius: 8px; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all 0.25s; font-family: inherit;
        }
        .btn-crop-save {
            background: linear-gradient(135deg, #E07B39 0%, #C86A2F 100%);
            color: white;
        }
        .btn-crop-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(224, 123, 57, 0.4);
        }
        .btn-crop-cancel { background: #95a5a6; color: white; }
        .btn-crop-cancel:hover { background: #7f8c8d; }

        .crop-info {
            text-align: center; font-size: 12px; color: #888;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="profile-container">
                <a href="admin_dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    <span data-i18n="admprof_back">Back to Dashboard</span>
                </a>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <div class="profile-card">
                    <!-- PROFILE HEADER WITH AVATAR -->
                    <div class="profile-header">
                        <div class="profile-avatar-container">
                            <?php if ($has_picture): ?>
                                <img src="<?php echo htmlspecialchars($picture_url); ?>"
                                     alt="Admin Picture" class="profile-avatar-img" id="currentAvatar">
                            <?php else: ?>
                                <div class="profile-avatar-placeholder" id="currentAvatar">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            <?php endif; ?>

                            <label for="profile_picture_input" class="camera-btn" title="Change profile picture">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>

                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($admin['username']); ?></h2>
                            <p data-i18n="admprof_administrator">Administrator Account</p>

                            <?php if ($has_picture): ?>
                            <form method="POST" style="margin-top: 8px;">
                                <button type="submit" name="remove_picture"
                                        class="btn btn-danger"
                                        style="padding: 5px 12px; font-size: 0.75rem;"
                                        onclick="return confirm('<?= $lang === 'sw' ? 'Ondoa picha yako ya wasifu?' : 'Remove your profile picture?' ?>');">
                                    <i class="fas fa-trash"></i>
                                    <span data-i18n="admprof_remove_photo">Remove Photo</span>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- UPDATE PROFILE SECTION -->
                    <div class="form-section">
                        <h3>
                            <i class="fas fa-user"></i>
                            <span data-i18n="admprof_update_title">Update Profile</span>
                        </h3>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="cropped_image" id="croppedImageInput">
                            <input type="file" id="profile_picture_input"
                                   accept="image/*" style="display: none;"
                                   onchange="openCropper(this)">

                            <div class="form-group">
                                <label for="username">
                                    <i class="fas fa-user"></i>
                                    <span data-i18n="admprof_username">Username</span>
                                </label>
                                <input type="text" id="username" name="username"
                                       value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-image"></i>
                                    <span data-i18n="admprof_profile_picture">Profile Picture</span>
                                </label>
                                <input type="file" id="profile_picture_main"
                                       class="form-control" accept="image/*"
                                       onchange="openCropper(this)">
                                <small style="color: #888; font-size: 12px;" data-i18n="admprof_max_size">
                                    Max: 5MB | Formats: JPG, JPEG, PNG, GIF, WEBP
                                </small>
                            </div>

                            <div class="btn-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    <span data-i18n="admprof_update_btn">Update Profile</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="divider"></div>

                    <!-- CHANGE PASSWORD SECTION -->
                    <div class="form-section">
                        <h3>
                            <i class="fas fa-lock"></i>
                            <span data-i18n="admprof_change_pw_title">Change Password</span>
                        </h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="current_password">
                                    <i class="fas fa-lock"></i>
                                    <span data-i18n="admprof_current_password">Current Password</span>
                                </label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">
                                    <i class="fas fa-key"></i>
                                    <span data-i18n="admprof_new_password">New Password</span>
                                </label>
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-key"></i>
                                    <span data-i18n="admprof_confirm_password">Confirm New Password</span>
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            <div class="btn-group">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i>
                                    <span data-i18n="admprof_change_pw_btn">Change Password</span>
                                </button>
                            </div>
                        </form>
                    </div>
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
                    <span data-i18n="admprof_crop_title">Adjust Your Photo</span>
                </h3>
                <button class="cropper-close" onclick="closeCropper()">&times;</button>
            </div>

            <p class="crop-info" data-i18n="admprof_crop_hint">
                Drag to move | Scroll to zoom
            </p>

            <div class="cropper-image-container">
                <img id="cropperImage" src="" alt="Image to crop">
            </div>

            <div class="cropper-controls">
                <button type="button" class="cropper-control-btn" onclick="rotateImage(-90)">
                    <i class="fas fa-undo"></i>
                    <span data-i18n="admprof_rotate_left">Left</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="rotateImage(90)">
                    <i class="fas fa-redo"></i>
                    <span data-i18n="admprof_rotate_right">Right</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="flipImage('h')">
                    <i class="fas fa-arrows-alt-h"></i>
                    <span data-i18n="admprof_flip_h">Flip H</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="flipImage('v')">
                    <i class="fas fa-arrows-alt-v"></i>
                    <span data-i18n="admprof_flip_v">Flip V</span>
                </button>
                <button type="button" class="cropper-control-btn" onclick="resetCropper()">
                    <i class="fas fa-sync-alt"></i>
                    <span data-i18n="admprof_reset">Reset</span>
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
                    <span data-i18n="admprof_cancel">Cancel</span>
                </button>
                <button type="button" class="btn-crop-save" onclick="applyCrop()">
                    <i class="fas fa-check"></i>
                    <span data-i18n="admprof_apply">Apply</span>
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
                    if (cropper) {
                        cropper.destroy();
                    }
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

        function rotateImage(degree) {
            if (cropper) cropper.rotate(degree);
        }

        function flipImage(direction) {
            if (!cropper) return;
            var data = cropper.getData();
            if (direction === 'h') {
                cropper.scaleX(data.scaleX === -1 ? 1 : -1);
            } else {
                cropper.scaleY(data.scaleY === -1 ? 1 : -1);
            }
        }

        function zoomImage(ratio) {
            if (cropper) cropper.zoomTo(parseFloat(ratio));
        }

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
                    newImg.className = 'profile-avatar-img';
                    newImg.id = 'currentAvatar';
                    currentAvatar.parentNode.replaceChild(newImg, currentAvatar);
                }
            }

            closeCropper();
            console.log('Image ready to save!');
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