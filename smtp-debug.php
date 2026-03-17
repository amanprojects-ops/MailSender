<?php
/**
 * SMTP Connection Debugger
 * Run this file directly: http://localhost/all-in-one/mailSender/smtp-debug.php
 * DELETE this file after debugging!
 */
require_once 'config/db.php';
require_once 'config/functions.php';
require_once 'config/encryption.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Fetch SMTP from DB
$user_id = 1; // Change if needed
$stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$smtp_row = $stmt->fetch();

if (!$smtp_row) {
    die("<b>ERROR:</b> No SMTP settings found for user_id = $user_id. Please configure SMTP first.");
}

$smtp_pass = $encryption->decrypt($smtp_row['smtp_pass']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SMTP Debugger</title>
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: monospace; padding: 2rem; }
        h2 { color: #38bdf8; }
        .box { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .ok { color: #10b981; }
        .fail { color: #f87171; }
        .warn { color: #fbbf24; }
        pre { margin: 0; white-space: pre-wrap; word-break: break-all; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 0.5rem 1rem; border-bottom: 1px solid #334155; }
        td:first-child { color: #94a3b8; width: 200px; }
    </style>
</head>
<body>
<h2>🔍 SMTP Diagnostic Tool</h2>

<div class="box">
    <h3>Saved SMTP Settings</h3>
    <table>
        <tr><td>Host</td><td><?= htmlspecialchars($smtp_row['smtp_host']) ?></td></tr>
        <tr><td>Username</td><td><?= htmlspecialchars($smtp_row['smtp_user']) ?></td></tr>
        <tr><td>Password</td><td><?= str_repeat('*', strlen($smtp_pass)) ?> (<?= strlen($smtp_pass) ?> chars)</td></tr>
        <tr><td>Port</td><td><?= $smtp_row['smtp_port'] ?></td></tr>
        <tr><td>Encryption</td><td><?= strtoupper($smtp_row['encryption']) ?></td></tr>
    </table>
</div>

<?php
// Step 1: Test raw TCP socket connectivity
$host = $smtp_row['smtp_host'];
$port = (int)$smtp_row['smtp_port'];
echo "<div class='box'><h3>Step 1: TCP Socket Connection Test</h3>";
echo "<p>Connecting to <b>{$host}:{$port}</b>...</p>";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if ($socket) {
    echo "<p class='ok'>✅ TCP Socket Connected to {$host}:{$port}</p>";
    fclose($socket);
} else {
    echo "<p class='fail'>❌ FAILED to connect to {$host}:{$port}</p>";
    echo "<p class='fail'>Error #{$errno}: {$errstr}</p>";
    echo "<p class='warn'>⚠️ This means your server/firewall is BLOCKING port {$port}. Try changing the port.</p>";
}
echo "</div>";

// Step 2: PHPMailer Full Debug
echo "<div class='box'><h3>Step 2: PHPMailer Full SMTP Debug</h3><pre>";

$mail = new PHPMailer(true);
$mail->SMTPDebug  = SMTP::DEBUG_SERVER; // Full debug output
$mail->Debugoutput = 'echo';
$mail->isSMTP();
$mail->CharSet    = 'UTF-8';
$mail->Host       = $host;
$mail->SMTPAuth   = true;
$mail->Username   = $smtp_row['smtp_user'];
$mail->Password   = $smtp_pass;
$mail->Timeout    = 20;

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

$mail->Port = $port;
$mail->SMTPOptions = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];

try {
    if ($mail->smtpConnect()) {
        echo "\n</pre><p class='ok'>✅ FULL SMTP AUTHENTICATION SUCCESSFUL! Your settings are correct.</p>";
        echo "<p class='warn'>Now send a test email using the main app. The issue might be something else.</p>";
        $mail->smtpClose();
    } else {
        echo "\n</pre><p class='fail'>❌ SMTP Connection failed (smtpConnect returned false).</p>";
    }
} catch (\Exception $e) {
    echo "\n</pre><p class='fail'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='fail'>ErrorInfo: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
}
echo "</div>";

// Step 3: PHP extension checks
echo "<div class='box'><h3>Step 3: PHP Configuration</h3><table>";
echo "<tr><td>PHP Version</td><td>" . PHP_VERSION . "</td></tr>";
echo "<tr><td>OpenSSL</td><td class='" . (extension_loaded('openssl') ? 'ok' : 'fail') . "'>" . (extension_loaded('openssl') ? '✅ Enabled' : '❌ MISSING') . "</td></tr>";
echo "<tr><td>allow_url_fopen</td><td class='" . (ini_get('allow_url_fopen') ? 'ok' : 'fail') . "'>" . (ini_get('allow_url_fopen') ? '✅ On' : '❌ Off') . "</td></tr>";
echo "<tr><td>SMTP ext</td><td class='" . (function_exists('mail') ? 'ok' : 'fail') . "'>" . (function_exists('mail') ? '✅ mail() available' : '❌ mail() not available') . "</td></tr>";
echo "</table></div>";
?>

<p class="warn">⚠️ <b>Important:</b> Delete this file after debugging! It exposes your SMTP settings.</p>
</body>
</html>
