<?php
/**
 * Add Template UI & Controller
 */
require_once '../config/db.php';
require_once '../config/functions.php';

require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token'] ?? '');
    
    $name          = trim($_POST['template_name'] ?? '');
    $language      = trim($_POST['language'] ?? 'English');
    $template_type = $_POST['template_type'] === 'html' ? 'html' : 'plain';
    $subject       = trim($_POST['subject'] ?? '');
    $body          = $_POST['body'] ?? '';

    if (!empty($name) && !empty($subject) && !empty($body)) {
        $stmt = $pdo->prepare("INSERT INTO templates (user_id, template_name, language, template_type, subject, body) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $name, $language, $template_type, $subject, $body])) {
            $success = "Template saved! <a href='index.php' class='alert-link'>Back to list</a>";
        } else {
            $error = "Failed to save template.";
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
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Template | Secure Mailer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap" rel="stylesheet">
    <style>
        .template-form-card { padding: 2.5rem; }
        
        /* Type Toggle */
        .type-toggle label {
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 0.8rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .type-toggle input[type=radio] { display: none; }
        .type-toggle input[type=radio]:checked + label {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        
        /* Editor tabs */
        .editor-tabs { border-bottom: 1px solid rgba(255,255,255,0.1); }
        .editor-tab {
            padding: 0.5rem 1.2rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: var(--text-secondary);
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .editor-tab.active { border-bottom-color: var(--primary); color: var(--text-primary); }
        
        #htmlEditor { font-family: 'Courier New', monospace; font-size: 13px; }
        
        #previewFrame {
            width: 100%; min-height: 300px; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; background: white;
        }
        
        .placeholder-chips .chip {
            background: rgba(99,102,241,0.15);
            border: 1px solid rgba(99,102,241,0.3);
            color: #818cf8;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .placeholder-chips .chip:hover { background: rgba(99,102,241,0.3); }
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
            <div class="col-md-10 col-lg-9">
                <div class="template-form-card">
                    <h3 class="mb-4 d-flex align-items-center">
                        <i class="bi bi-plus-square me-2 text-primary"></i> Create New Template
                    </h3>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="templateForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="body" id="bodyHidden">

                        <!-- Row 1: Name + Language -->
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="template_name" class="form-control" placeholder="BCA Exam Notice" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Language</label>
                                <select name="language" class="form-select">
                                    <option value="English">English</option>
                                    <option value="Hindi">Hindi</option>
                                    <option value="Spanish">Spanish</option>
                                    <option value="French">French</option>
                                    <option value="German">German</option>
                                    <option value="Russian">Russian</option>
                                    <option value="Chinese">Chinese</option>
                                    <option value="Arabic">Arabic</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Template Type</label>
                                <div class="type-toggle d-flex gap-2">
                                    <input type="radio" name="template_type" id="typePlain" value="plain" checked>
                                    <label for="typePlain"><i class="bi bi-body-text"></i> Plain Text</label>
                                    
                                    <input type="radio" name="template_type" id="typeHtml" value="html">
                                    <label for="typeHtml"><i class="bi bi-code-slash"></i> HTML</label>
                                </div>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" id="subjectInput" class="form-control" placeholder="Subject for {{name}}..." required>
                        </div>

                        <!-- Placeholder Chips -->
                        <div class="mb-3">
                            <label class="form-label small">Quick Insert Placeholder</label>
                            <div class="placeholder-chips d-flex flex-wrap gap-2">
                                <span class="chip" onclick="insertPlaceholder('{{name}}')">{{name}}</span>
                                <span class="chip" onclick="insertPlaceholder('{{date}}')">{{date}}</span>
                                <span class="chip" onclick="insertPlaceholder('{{custom}}')">{{custom}}</span>
                            </div>
                        </div>

                        <!-- Editor (Plain Text mode) -->
                        <div id="plainEditor" class="mb-4">
                            <label class="form-label">Message</label>
                            <textarea id="plainTextarea" class="form-control" rows="12"
                                placeholder="Type your message here... Use {{name}}, {{date}}, {{custom}} as placeholders."></textarea>
                            <small class="text-muted">Plain text — each new line will appear as a new line in the email.</small>
                        </div>

                        <!-- Editor (HTML mode) -->
                        <div id="htmlEditorWrap" class="mb-4" style="display:none;">
                            <div class="editor-tabs d-flex mb-0">
                                <div class="editor-tab active" id="tabCode" onclick="switchTab('code')">
                                    <i class="bi bi-code me-1"></i> HTML Code
                                </div>
                                <div class="editor-tab" id="tabPreview" onclick="switchTab('preview')">
                                    <i class="bi bi-eye me-1"></i> Live Preview
                                </div>
                            </div>
                            <div id="codePanel">
                                <textarea id="htmlEditor" class="form-control" rows="14"
                                    placeholder="<p>Hello <strong>{{name}}</strong>,</p><p>Your content here...</p>"></textarea>
                            </div>
                            <div id="previewPanel" style="display:none;">
                                <iframe id="previewFrame" title="Email Preview"></iframe>
                            </div>
                            <small class="text-muted mt-1 d-block">Write full HTML. Use {{name}}, {{date}}, {{custom}} as placeholders.</small>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-save me-1"></i> Save Template
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let activeEditor = 'plain'; // 'plain' or 'html'

        // Switch between plain and HTML editors
        document.querySelectorAll('input[name="template_type"]').forEach(radio => {
            radio.addEventListener('change', function () {
                activeEditor = this.value;
                if (this.value === 'html') {
                    document.getElementById('plainEditor').style.display = 'none';
                    document.getElementById('htmlEditorWrap').style.display = 'block';
                } else {
                    document.getElementById('plainEditor').style.display = 'block';
                    document.getElementById('htmlEditorWrap').style.display = 'none';
                }
            });
        });

        // Switch HTML editor tabs (code / preview)
        function switchTab(tab) {
            document.getElementById('tabCode').classList.toggle('active', tab === 'code');
            document.getElementById('tabPreview').classList.toggle('active', tab === 'preview');
            document.getElementById('codePanel').style.display    = tab === 'code' ? 'block' : 'none';
            document.getElementById('previewPanel').style.display = tab === 'preview' ? 'block' : 'none';
            
            if (tab === 'preview') {
                const frame = document.getElementById('previewFrame');
                const html  = document.getElementById('htmlEditor').value;
                frame.srcdoc = html;
            }
        }

        // Insert placeholder at cursor position
        let lastFocused = document.getElementById('plainTextarea');
        document.getElementById('plainTextarea').addEventListener('focus', () => lastFocused = document.getElementById('plainTextarea'));
        document.getElementById('htmlEditor').addEventListener('focus', () => lastFocused = document.getElementById('htmlEditor'));
        document.getElementById('subjectInput').addEventListener('focus', () => lastFocused = document.getElementById('subjectInput'));

        function insertPlaceholder(ph) {
            const el = lastFocused;
            const start = el.selectionStart, end = el.selectionEnd;
            el.value = el.value.slice(0, start) + ph + el.value.slice(end);
            el.selectionStart = el.selectionEnd = start + ph.length;
            el.focus();
        }

        // Before submit: move correct textarea value into hidden input
        document.getElementById('templateForm').addEventListener('submit', function (e) {
            const val = activeEditor === 'html'
                ? document.getElementById('htmlEditor').value
                : document.getElementById('plainTextarea').value;
            document.getElementById('bodyHidden').value = val;

            if (!val.trim()) {
                e.preventDefault();
                alert('Please enter your email message content.');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
