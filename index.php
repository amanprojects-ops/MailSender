<?php
/**
 * Main Dashboard / Entry Point
 */
require_once 'config/db.php';
require_once 'config/functions.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch counts for dashboard stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM templates WHERE user_id = ?");
$stmt->execute([$user_id]);
$template_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM email_logs WHERE user_id = ? AND status = 'success'");
$stmt->execute([$user_id]);
$sent_count = $stmt->fetchColumn();

// Fetch last few logs
$stmt = $pdo->prepare("SELECT * FROM email_logs WHERE user_id = ? ORDER BY sent_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .icon-primary { background: rgba(99, 102, 241, 0.2); color: #818cf8; }
        .icon-accent { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        
        .nav-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid rgba(255,255,255,0.05);
            text-decoration: none;
            color: #fff;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .nav-card:hover {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 30px rgba(99, 102, 241, 0.3);
            border-color: transparent;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">
                <i class="bi bi-shield-lock-fill text-primary me-2"></i>SECURE MAILER
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-muted me-3 d-none d-md-inline">Welcome, <strong><?php echo hb($username); ?></strong></span>
                <a href="auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row g-4 mb-5">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="icon-box icon-primary"><i class="bi bi-file-earmark-text"></i></div>
                    <h3 class="fw-bold mb-1"><?php echo $template_count; ?></h3>
                    <p class="text-muted mb-0">Saved Templates</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="icon-box icon-accent"><i class="bi bi-check2-circle"></i></div>
                    <h3 class="fw-bold mb-1"><?php echo $sent_count; ?></h3>
                    <p class="text-muted mb-0">Emails Sent</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row h-100 g-3">
                    <div class="col-6">
                        <a href="send-mail/index.php" class="nav-card">
                            <i class="bi bi-send-fill fs-3 me-3"></i>
                            <div>
                                <h5 class="mb-0">Send Mail</h5>
                                <small class="opacity-75">Start campaign</small>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="smtp/settings.php" class="nav-card">
                            <i class="bi bi-gear-fill fs-3 me-3"></i>
                            <div>
                                <h5 class="mb-0">Settings</h5>
                                <small class="opacity-75">SMTP config</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h4 class="mb-4">Recent Email Logs</h4>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_logs)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No emails sent yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo hb($log['recipient']); ?></td>
                                        <td><?php echo hb($log['subject']); ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="<?php echo hb($log['error_message']); ?>">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, H:i', strtotime($log['sent_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
