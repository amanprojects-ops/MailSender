<?php
/**
 * Email Sending Engine
 * Handles SMTP authentication and PHPMailer logic.
 */
header('Content-Type: application/json');

require_once '../vendor/autoload.php';
require_once '../config/db.php';
require_once '../config/functions.php';
require_once '../config/encryption.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!is_logged_in()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Security: CSRF & Rate Limit (5s between batches)
validate_csrf_token($_POST['csrf_token'] ?? '');
// rate_limit(5); // Uncomment in prod

// Input Collection
$recipients_str = trim($_POST['recipients'] ?? '');
$subject        = trim($_POST['subject'] ?? '');
$body           = $_POST['body'] ?? '';
$template_type  = $_POST['template_type'] === 'html' ? 'html' : 'plain'; // html or plain
$name_val       = trim($_POST['name_val'] ?? 'Valued User');
$date_val       = trim($_POST['date_val'] ?? date('Y-m-d'));
$custom_val     = trim($_POST['custom_val'] ?? '');

if (empty($recipients_str) || empty($subject) || empty($body)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Parse Recipients (Max 5)
$recipients = array_map('trim', explode(',', $recipients_str));
$recipients = array_filter($recipients, function($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
});

if (count($recipients) > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Maximum 5 recipients allowed per request.']);
    exit;
}

if (empty($recipients)) {
    echo json_encode(['status' => 'error', 'message' => 'No valid email addresses provided.']);
    exit;
}

// Fetch SMTP Settings
$stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$smtp_row = $stmt->fetch();

if (!$smtp_row) {
    echo json_encode(['status' => 'error', 'message' => 'SMTP settings not configured.']);
    exit;
}

// Decrypt SMTP Password
$smtp_pass = $encryption->decrypt($smtp_row['smtp_pass']);

// Replace Placeholders — supports both {{name}} and {{ name }} (with spaces)
function replace_placeholders($text, $name, $date, $custom) {
    // Match placeholders with or without spaces: {{name}}, {{ name }}, {{  name  }}
    $text = preg_replace('/\{\{\s*name\s*\}\}/i',   $name,   $text);
    $text = preg_replace('/\{\{\s*date\s*\}\}/i',   $date,   $text);
    $text = preg_replace('/\{\{\s*custom\s*\}\}/i', $custom, $text);
    return $text;
}

// Convert plain-text line breaks to HTML <br> for proper email formatting
function format_body_for_email($text) {
    // If the text doesn't already look like HTML, apply nl2br
    if (strip_tags($text) === $text) {
        return nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }
    // If it's already HTML (user wrote HTML), return as-is
    return $text;
}

// Initialize Results
$results = [];
$summary = ['success' => 0, 'failed' => 0];

try {
    foreach ($recipients as $email) {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64'; 
            $mail->isSMTP();
            $mail->Host       = $smtp_row['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_row['smtp_user'];
            $mail->Password   = $smtp_pass;
            $mail->Timeout    = 20; 
            
            // Correct Encryption Mapping
            if ($smtp_row['encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } elseif ($smtp_row['encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            
            $mail->Port = $smtp_row['smtp_port'];

            // Extra options for local servers
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipient
            $mail->setFrom($smtp_row['smtp_user'], 'Mail Sender AP');
            $mail->addAddress($email);

            // Content with placeholders resolved
            $current_subject = replace_placeholders($subject, $name_val, $date_val, $custom_val);
            $raw_body        = replace_placeholders($body, $name_val, $date_val, $custom_val);

            if ($template_type === 'html') {
                // HTML template — wrap in UTF-8 envelope
                $html_body = <<<HTML
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, 'Noto Sans Devanagari', sans-serif; font-size: 15px; line-height: 1.8; color: #1a1a1a; background: #f9f9f9; }
        .container { max-width: 640px; margin: 30px auto; padding: 35px 40px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
        p { margin: 0 0 12px 0; }
    </style>
</head>
<body>
    <div class="container">
        {$raw_body}
    </div>
</body>
</html>
HTML;
                $mail->isHTML(true);
                $mail->Body    = $html_body;
                $mail->AltBody = strip_tags($raw_body);
            } else {
                // Plain text template — convert newlines to <br> inside a clean UTF-8 HTML envelope
                $formatted_body = format_body_for_email($raw_body);
                $html_body = <<<HTML
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, 'Noto Sans Devanagari', sans-serif; font-size: 15px; line-height: 1.8; color: #1a1a1a; background: #f9f9f9; }
        .container { max-width: 640px; margin: 30px auto; padding: 35px 40px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
    </style>
</head>
<body><div class="container">{$formatted_body}</div></body>
</html>
HTML;
                $mail->isHTML(true);
                $mail->Body    = $html_body;
                $mail->AltBody = $raw_body; // Actual plain text for AltBody
            }

            $mail->Subject = $current_subject;

            $mail->send();
            
            $results[] = ['email' => $email, 'status' => 'success', 'message' => 'Sent successfully'];
            $summary['success']++;

            $log_stmt = $pdo->prepare("INSERT INTO email_logs (user_id, recipient, subject, status) VALUES (?, ?, ?, 'success')");
            $log_stmt->execute([$user_id, $email, $current_subject]);

        } catch (Exception $e) {
            $results[] = ['email' => $email, 'status' => 'failed', 'message' => "Connection/Server Error: " . $mail->ErrorInfo];
            $summary['failed']++;

            $log_stmt = $pdo->prepare("INSERT INTO email_logs (user_id, recipient, subject, status, error_message) VALUES (?, ?, ?, 'failed', ?)");
            $log_stmt->execute([$user_id, $email, $current_subject, $mail->ErrorInfo]);
        }
    }
} catch (\Throwable $th) {
    echo json_encode(['status' => 'error', 'message' => 'System Level Error: ' . $th->getMessage()]);
    exit;
}

echo json_encode([
    'status' => 'complete',
    'results' => $results,
    'summary' => $summary
]);
?>
