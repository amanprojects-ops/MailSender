<?php
/**
 * Templates List UI
 */
require_once '../config/db.php';
require_once '../config/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$message = '';

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$id, $user_id])) {
        $message = "Template deleted successfully.";
    }
}

// Fetch all templates
$stmt = $pdo->prepare("SELECT * FROM templates WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$templates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates Manager | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .template-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s;
        }
        .text-accent { color: var(--accent) !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">Secure Mailer</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Dashboard</a>
                <a class="nav-link" href="../smtp/settings.php">SMTP</a>
                <a class="nav-link active" href="index.php">Templates</a>
                <a class="nav-link" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text me-2"></i> Your Templates</h2>
            <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New Template</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($templates)): ?>
                <div class="col-12">
                    <div class="bg-card-dark p-5 rounded-3 text-center border border-secondary border-dashed">
                        <i class="bi bi-inbox-fill display-1 text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">No templates yet. Create your first one!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($templates as $tmpl): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="template-card">
                            <h4 class="text-accent mb-2"><?php echo hb($tmpl['template_name']); ?></h4>
                            <div class="mb-2">
                                <span class="badge bg-primary me-1"><?php echo hb($tmpl['language'] ?? 'English'); ?></span>
                                <span class="badge bg-secondary"><?php echo date('M d, Y', strtotime($tmpl['created_at'])); ?></span>
                            </div>
                            <p class="text-secondary small mb-3"><strong>Subject:</strong> <?php echo hb($tmpl['subject']); ?></p>
                            <div class="d-flex gap-2">
                                <a href="edit.php?id=<?php echo $tmpl['id']; ?>" class="btn btn-sm btn-outline-info w-50"><i class="bi bi-pencil me-1"></i> Edit</a>
                                <a href="?delete=<?php echo $tmpl['id']; ?>" class="btn btn-sm btn-outline-danger w-50" onclick="return confirm('Are you sure you want to delete this template?')"><i class="bi bi-trash me-1"></i> Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
