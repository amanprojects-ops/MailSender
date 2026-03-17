<?php
/**
 * Edit Template UI & Controller
 */
require_once '../config/db.php';
require_once '../config/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Check if template exists and belongs to user
$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$template = $stmt->fetch();

if (!$template) {
    redirect('index.php');
}

// CSRF Token
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    $name          = trim($_POST['template_name'] ?? '');
    $language      = trim($_POST['language'] ?? 'English');
    $template_type = $_POST['template_type'] === 'html' ? 'html' : 'plain';
    $subject       = trim($_POST['subject'] ?? '');
    $body          = $_POST['body'] ?? '';

    if (!empty($name) && !empty($subject) && !empty($body)) {
        $stmt = $pdo->prepare("UPDATE templates SET template_name = ?, language = ?, template_type = ?, subject = ?, body = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$name, $language, $template_type, $subject, $body, $id, $user_id])) {
            $success = "Template updated successfully!";
            $template = ['template_name' => $name, 'language' => $language, 'template_type' => $template_type, 'subject' => $subject, 'body' => $body];
        } else {
            $error = "Failed to update template.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Edit Template | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap" rel="stylesheet">
    <style>
        .template-form-card {
            padding: 2.5rem;
        }
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
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="template-form-card">
                    <h3 class="mb-4 d-flex align-items-center"><i class="bi bi-pencil-square me-2 text-warning"></i> Edit Template</h3>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="template_name" class="form-control" value="<?php echo hb($template['template_name']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Language</label>
                                <select name="language" class="form-select">
                                    <?php 
                                    $langs = ['English', 'Hindi', 'Spanish', 'French', 'German', 'Russian', 'Chinese', 'Arabic'];
                                    foreach($langs as $l): ?>
                                        <option value="<?php echo $l; ?>" <?php echo ($template['language'] ?? 'English') === $l ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" value="<?php echo hb($template['subject']); ?>" required>
                            <small class="text-muted">Placeholders like {{name}}, {{date}}, {{custom}} are allowed.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Email Message (HTML allowed)</label>
                            <textarea name="body" class="form-control" rows="10" required><?php echo hb($template['body']); ?></textarea>
                            <small class="text-muted">Use {{name}}, {{date}}, {{custom}} as placeholders.</small>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-warning px-4 text-dark fw-bold">Update Template</button>
                            <a href="index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
