<?php
/**
 * Send Email UI
 */
require_once '../config/db.php';
require_once '../config/functions.php';

require_login();

$user_id = $_SESSION['user_id'];

// Check SMTP settings first
$stmt = $pdo->prepare("SELECT smtp_user FROM smtp_settings WHERE user_id = ?");
$stmt->execute([$user_id]);
$smtp = $stmt->fetch();

if (!$smtp) {
    die("Error: Please configure your <a href='../smtp/settings.php'>SMTP settings</a> first.");
}

// Fetch Templates
$stmt = $pdo->prepare("SELECT * FROM templates WHERE user_id = ?");
$stmt->execute([$user_id]);
$templates = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .result-box {
            background: #000;
            border-radius: 8px;
            padding: 1.2rem;
            font-family: 'Courier New', Courier, monospace;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.1);
            display: none;
            color: #fff;
        }
        .status-success { color: #10b981; }
        .status-error { color: #ef4444; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">Secure Mailer</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Dashboard</a>
                <a class="nav-link" href="../smtp/settings.php">SMTP</a>
                <a class="nav-link" href="../templates/index.php">Templates</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="sender-card">
                    <h3 class="mb-4 d-flex align-items-center"><i class="bi bi-send-fill me-2 text-primary"></i> Send Emails</h3>
                    
                    <form id="mailForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="template_type" id="templateTypeField" value="plain">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">From (Auto-filled)</label>
                                <input type="text" class="form-control" value="<?php echo hb($smtp['smtp_user']); ?>" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subject (Preview)</label>
                                <input type="text" id="subjectPreview" class="form-control" name="subject" placeholder="Select a template or type custom" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Template (Optional)</label>
                            <select id="templatePicker" class="form-select">
                                <option value="">-- Choose Template --</option>
                                <?php foreach ($templates as $tmpl): ?>
                                    <option value="<?php echo $tmpl['id']; ?>"
                                        data-subject="<?php echo hb($tmpl['subject']); ?>"
                                        data-body="<?php echo hb($tmpl['body']); ?>"
                                        data-type="<?php echo hb($tmpl['template_type'] ?? 'plain'); ?>">
                                        <?php echo hb($tmpl['template_name']); ?>
                                        (<?php echo hb($tmpl['language'] ?? 'English'); ?> ·
                                        <?php echo strtoupper($tmpl['template_type'] ?? 'PLAIN'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Recipients (Email Addresses, comma separated - Max 5)</label>
                            <input type="text" id="recipients" name="recipients" class="form-control" placeholder="user1@example.com, user2@example.com" required>
                            <small class="text-muted">Enter up to 5 email addresses.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Email Message (HTML)</label>
                            <textarea id="bodyArea" name="body" class="form-control" rows="8" placeholder="Hello, this is a test message."></textarea>
                        </div>

                        <div class="card bg-dark border-secondary p-3 mb-4">
                            <h6 class="text-info mb-2"><i class="bi bi-info-circle me-1"></i> Placeholder Data</h6>
                            <div class="row g-2">
                                <div class="col-sm-4">
                                    <input type="text" name="name_val" class="form-control form-control-sm" placeholder="Name for {{name}}">
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" name="date_val" class="form-control form-control-sm" placeholder="Date for {{date}}">
                                </div>
                                <div class="col-sm-4">
                                    <input type="text" name="custom_val" class="form-control form-control-sm" placeholder="Custom for {{custom}}">
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="sendBtn" class="btn btn-primary px-5 py-2 fw-bold">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Launch Campaign
                        </button>
                    </form>

                    <div id="resultBox" class="result-box mt-4"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('templatePicker').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            if (this.value) {
                document.getElementById('subjectPreview').value = selected.getAttribute('data-subject');
                document.getElementById('bodyArea').value = selected.getAttribute('data-body');
                // Set template type hidden field
                document.getElementById('templateTypeField').value = selected.getAttribute('data-type') || 'plain';
            }
        });

        document.getElementById('mailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('sendBtn');
            const resultBox = document.getElementById('resultBox');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';
            resultBox.style.display = 'block';
            resultBox.innerHTML = 'Starting queue...<br>';

            const formData = new FormData(this);
            
            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'error') {
                    resultBox.innerHTML += `<span class="status-error mt-2 d-block">FATAL ERROR: ${data.message}</span>`;
                } else if (data.results) {
                    data.results.forEach(res => {
                        const style = res.status === 'success' ? 'status-success' : 'status-error';
                        resultBox.innerHTML += `<div class="${style}">[${res.status.toUpperCase()}] To: ${res.email} - ${res.message}</div>`;
                    });
                    resultBox.innerHTML += `<span class="text-info mt-2 d-block">Process completed. Successful: ${data.summary.success}, Failed: ${data.summary.failed}</span>`;
                }
            })
            .catch(err => {
                resultBox.innerHTML += `<span class="status-error">Error processing request.</span>`;
                console.error(err);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> Launch Campaign';
            });
        });
    </script>
</body>
</html>
