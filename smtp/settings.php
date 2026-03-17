<?php
/**
 * SMTP Settings Controller & UI
 */
require_once '../config/db.php';
require_once '../config/functions.php';
require_once '../config/encryption.php';

require_login();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// CSRF Token
$csrf_token = generate_csrf_token();

// Fetch existing settings
$stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$smtp = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    $host = trim($_POST['smtp_host'] ?? '');
    $user = trim($_POST['smtp_user'] ?? '');
    $pass = $_POST['smtp_pass'] ?? '';
    $port = intval($_POST['smtp_port'] ?? 587);
    $encryption_type = $_POST['encryption'] ?? 'tls';

    if (!empty($host) && !empty($user) && !empty($pass)) {
        // Encrypt password
        $encrypted_pass = $encryption->encrypt($pass);

        if ($smtp) {
            // Update
            $stmt = $pdo->prepare("UPDATE smtp_settings SET smtp_host = ?, smtp_user = ?, smtp_pass = ?, smtp_port = ?, encryption = ? WHERE user_id = ?");
            if ($stmt->execute([$host, $user, $encrypted_pass, $port, $encryption_type, $user_id])) {
                $message = "SMTP settings updated successfully!";
            }
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO smtp_settings (user_id, smtp_host, smtp_user, smtp_pass, smtp_port, encryption) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $host, $user, $encrypted_pass, $port, $encryption_type])) {
                $message = "SMTP settings saved successfully!";
            }
        }
        // Refresh local data
        $smtp = ['smtp_host' => $host, 'smtp_user' => $user, 'smtp_port' => $port, 'encryption' => $encryption_type];
    } else {
        $error = "Host, Username, and Password are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">Secure Mailer</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Dashboard</a>
                <a class="nav-link active" href="settings.php">SMTP Settings</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="settings-card">
                    <h3 class="mb-4 d-flex align-items-center">
                        <i class="bi bi-gear-fill me-2 text-accent"></i> SMTP Configuration
                    </h3>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" value="<?php echo hb($smtp['smtp_host'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">SMTP Username (Email)</label>
                            <input type="email" name="smtp_user" class="form-control" placeholder="your-email@gmail.com" value="<?php echo hb($smtp['smtp_user'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">SMTP Password / App Password</label>
                            <div class="input-group">
                                <input type="password" name="smtp_pass" class="form-control" placeholder="••••••••" required>
                                <span class="input-group-text bg-dark border-secondary"><i class="bi bi-lock-fill text-muted"></i></span>
                            </div>
                            <small class="text-muted">Passwords are stored using AES-256-CBC encryption.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Port</label>
                                <input type="number" id="smtpPort" name="smtp_port" class="form-control" value="<?php echo hb($smtp['smtp_port'] ?? '587'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Encryption</label>
                                <select id="smtpEncryption" name="encryption" class="form-select">
                                    <option value="tls" <?php echo ($smtp['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Port 587) ← Gmail</option>
                                    <option value="ssl" <?php echo ($smtp['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                    <option value="none" <?php echo ($smtp['encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None (Port 25)</option>
                                </select>
                                <small id="encHint" class="mt-1 d-block"></small>
                            </div>
                        </div>

                        <!-- Quick-fill guide -->
                        <div class="p-3 mb-3 rounded-3" style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2);">
                            <p class="mb-2 small fw-bold" style="color: #818cf8;"><i class="bi bi-info-circle me-1"></i> Quick Fill for Common Providers</p>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickFill('smtp.gmail.com', 587, 'tls')">Gmail</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickFill('smtp.office365.com', 587, 'tls')">Outlook</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickFill('smtp.mail.yahoo.com', 587, 'tls')">Yahoo</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="quickFill('smtp.zoho.com', 587, 'tls')">Zoho</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-2 py-2">
                            <i class="bi bi-save2 me-1"></i> Save Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const portEl = document.getElementById('smtpPort');
        const encEl = document.getElementById('smtpEncryption');
        const hint  = document.getElementById('encHint');

        const hints = {
            tls:  '✅ Uses STARTTLS. Default port: 587. Recommended for Gmail.',
            ssl:  '✅ Uses SMTPS (Implicit SSL). Default port: 465.',
            none: '⚠️ No encryption. Default port: 25. Not recommended.'
        };

        encEl.addEventListener('change', function () {
            const val = this.value;
            hint.textContent = hints[val] || '';
            hint.style.color = val === 'none' ? '#fbbf24' : '#10b981';
            if (val === 'tls')  portEl.value = 587;
            if (val === 'ssl')  portEl.value = 465;
            if (val === 'none') portEl.value = 25;
        });

        // Show hint on load
        hint.textContent = hints[encEl.value] || '';
        hint.style.color = encEl.value === 'none' ? '#fbbf24' : '#10b981';

        function quickFill(host, port, enc) {
            document.querySelector('[name=smtp_host]').value = host;
            portEl.value = port;
            encEl.value  = enc;
            hint.textContent = hints[enc] || '';
            hint.style.color = '#10b981';
        }
    </script>
</body>
</html>
