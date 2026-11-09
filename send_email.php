<?php
// ================================================
// send_email.php - Email Helper (Gmail — All Users)
// ================================================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

// ================================================
// SMTP CONFIGURATION — GMAIL
// ================================================
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_SECURE',    PHPMailer::ENCRYPTION_STARTTLS);
define('SMTP_EMAIL',     'chancerapa@gmail.com');     // Email ya Gmail yako
define('SMTP_PASSWORD',  'ewom yjcc qrao illi');      // App Password
define('SMTP_FROM_NAME', 'Cafeteria System');

// ================================================
// SEND EMAIL FUNCTION
// ================================================
function send_email($to_email, $to_name, $subject, $body_html) {
    $mail = new PHPMailer(true);

    try {
        // ============ SMTP CONFIGURATION ============
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // ============ FAST SETTINGS ============
        $mail->Timeout    = 15;
        $mail->Hostname   = 'localhost';
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        // ============ FROM (MUHIMU: lazimа iwe SMTP_EMAIL) ============
        // Gmail inaruhusu kutuma kwa yeyote KAMA "From" ni email yako halisi
        $mail->setFrom(SMTP_EMAIL, SMTP_FROM_NAME);
        $mail->addReplyTo(SMTP_EMAIL, SMTP_FROM_NAME);

        // ============ TO (Yeyote — Admin, Customer, Employee) ============
        $mail->addAddress($to_email, $to_name);

        // ============ CONTENT ============
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags($body_html);

        // ============ SEND ============
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email failed: {$mail->ErrorInfo}"];
    }
}

// ================================================
// SEND OTP EMAIL
// ================================================
function send_otp_email($to_email, $to_name, $otp, $user_type = 'user') {
    $subject = 'Your OTP Code - Cafeteria System';

    $type_labels = [
        'admin'    => 'Administrator',
        'employee' => 'Staff Member',
        'customer' => 'Customer',
        'user'     => 'User'
    ];
    $type_label = $type_labels[$user_type] ?? 'User';

    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
            .container {
                max-width: 500px; margin: 0 auto; background: white;
                border-radius: 10px; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #E07B39; }
            .header h1 { color: #E07B39; margin: 0; font-size: 24px; }
            .badge {
                display: inline-block; padding: 4px 12px;
                background: #fff8f0; color: #C86A2F;
                border-radius: 12px; font-size: 12px;
                font-weight: 600; text-transform: uppercase;
                margin-top: 10px;
            }
            .otp-box {
                text-align: center; padding: 30px 0; margin: 20px 0;
                background: #fff8f0; border-radius: 10px;
                border: 2px dashed #E07B39;
            }
            .otp-code {
                font-size: 42px; font-weight: 800;
                color: #C86A2F; font-family: "Courier New", monospace;
                letter-spacing: 12px;
            }
            .message { color: #666; line-height: 1.6; font-size: 14px; }
            .footer {
                text-align: center; color: #999; font-size: 12px;
                margin-top: 20px; padding-top: 20px;
                border-top: 1px solid #eee;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🍴 Cafeteria System</h1>
                <span class="badge">' . $type_label . '</span>
            </div>

            <p class="message">Hello <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
            <p class="message">Your One-Time Password (OTP) for password reset is:</p>

            <div class="otp-box">
                <div class="otp-code">' . $otp . '</div>
            </div>

            <p class="message">This OTP is valid for <strong>15 minutes</strong>. Do not share it with anyone.</p>
            <p class="message">If you did not request this, please ignore this email.</p>

            <div class="footer">
                &copy; ' . date('Y') . ' Cafeteria Management System
            </div>
        </div>
    </body>
    </html>';

    return send_email($to_email, $to_name, $subject, $body);
}

// ================================================
// SEND WELCOME EMAIL
// ================================================
function send_welcome_email($to_email, $to_name, $user_type = 'customer') {
    $subject = 'Welcome to Cafeteria System!';

    $type_labels = [
        'admin'    => 'Administrator',
        'employee' => 'Staff Member',
        'customer' => 'Customer'
    ];
    $type_label = $type_labels[$user_type] ?? 'User';

    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0; }
            .container {
                max-width: 500px; margin: 0 auto; background: white;
                border-radius: 10px; padding: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            }
            .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #E07B39; }
            .header h1 { color: #E07B39; margin: 0; }
            .footer {
                text-align: center; color: #999; font-size: 12px;
                margin-top: 20px; padding-top: 20px;
                border-top: 1px solid #eee;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🍴 Cafeteria System</h1>
            </div>
            <p>Hello <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
            <p>Welcome to Cafeteria System as a <strong>' . $type_label . '</strong>!</p>
            <p>Your account has been created successfully.</p>
            <div class="footer">
                &copy; ' . date('Y') . ' Cafeteria Management System
            </div>
        </div>
    </body>
    </html>';

    return send_email($to_email, $to_name, $subject, $body);
}
?>