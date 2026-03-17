# 📬 Secure Mailer

> A lightweight, security-first SMTP email sender built with PHP + PHPMailer. No bloat, no framework overhead — raw PDO, session-based auth, AES-256 encrypted SMTP credentials, and a clean dark-mode UI.

---

## ✨ Features

| Feature | Detail |
|---|---|
| 🔐 Secure Authentication | Session-based login with `password_hash` / `password_verify` (bcrypt) |
| 🔒 SMTP Password Encryption | AES-256-CBC encryption via OpenSSL before storing in DB |
| 📝 Template Manager | Create, edit, delete reusable email templates |
| 🌐 Template Types | **Plain Text** and **HTML** modes with a live preview editor |
| 🔖 Dynamic Placeholders | `{{name}}`, `{{date}}`, `{{custom}}` — resolved at send time |
| 📨 Bulk Send | Send to up to **5 recipients** per request via comma-separated list |
| 🛡️ CSRF Protection | Every form protected with a cryptographically random token |
| 🚦 Rate Limiting | Session-based rate limiter to prevent request flooding |
| 📋 Email Logs | Per-user send history with status (success / failed) on the dashboard |
| 🔍 SMTP Debugger | Built-in diagnostic tool for troubleshooting SMTP connectivity |
| 🌑 Dark UI | Premium dark-mode interface using Bootstrap 5 + Inter font |

---

## 🏗️ Project Structure

```
mailSender/
│
├── index.php                  # 🏠 Main dashboard (stats + recent logs)
├── composer.json              # PHP dependency manifest (PHPMailer 6.9)
├── database.sql               # Database schema & default admin seeder
├── smtp-debug.php             # 🔍 SMTP diagnostic tool (delete after use!)
│
├── auth/
│   ├── login.php              # Login controller + UI
│   ├── signup.php             # Registration controller + UI
│   └── logout.php             # Session destroy + redirect
│
├── config/
│   ├── db.php                 # PDO connection (utf8mb4, strict mode)
│   ├── functions.php          # Helpers: hb(), CSRF, rate_limit, redirect, is_logged_in
│   └── encryption.php         # AES-256-CBC encrypt/decrypt for SMTP passwords
│
├── smtp/
│   └── settings.php           # SMTP config form (save / update) with quick-fill presets
│
├── templates/
│   ├── index.php              # Template list (cards with edit/delete)
│   ├── add.php                # Create template — Plain Text or HTML editor
│   └── edit.php               # Edit existing template
│
├── send-mail/
│   ├── index.php              # Compose & send UI (template picker, recipient list)
│   └── process.php            # ⚙️  Email sending engine (PHPMailer, logs, JSON response)
│
├── assets/
│   ├── css/
│   │   └── style.css          # Global dark-mode design system
│   └── js/
│
└── vendor/                    # Composer-managed packages (PHPMailer)
```

---

## 🗄️ Database Schema

Database name: **`mail_sender_db`** — UTF-8mb4 Unicode collation.

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `username` | VARCHAR(100) UNIQUE | |
| `password_hash` | VARCHAR(255) | bcrypt via `password_hash()` |
| `created_at` | TIMESTAMP | auto |

### `smtp_settings`
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `user_id` | INT FK → users | CASCADE DELETE |
| `smtp_host` | VARCHAR(255) | e.g. `smtp.gmail.com` |
| `smtp_user` | VARCHAR(255) | sender email address |
| `smtp_pass` | TEXT | AES-256-CBC encrypted |
| `smtp_port` | INT | 587 / 465 / 25 |
| `encryption` | ENUM(`tls`,`ssl`,`none`) | default `tls` |
| `created_at` | TIMESTAMP | auto |

### `templates`
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `user_id` | INT FK → users | CASCADE DELETE |
| `template_name` | VARCHAR(100) | Display name |
| `language` | VARCHAR(20) | English, Hindi, etc. |
| `template_type` | ENUM(`plain`,`html`) | default `plain` |
| `subject` | VARCHAR(255) | Supports placeholders |
| `body` | TEXT | Raw plain text or raw HTML |
| `created_at` | TIMESTAMP | auto |

### `email_logs`
| Column | Type | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `user_id` | INT FK → users | CASCADE DELETE |
| `recipient` | VARCHAR(255) | |
| `subject` | VARCHAR(255) | Placeholder-resolved subject |
| `status` | ENUM(`success`,`failed`) | |
| `error_message` | TEXT | Populated on failure |
| `sent_at` | TIMESTAMP | auto |

---

## ⚙️ Installation & Setup

### Requirements

- **PHP** ≥ 7.4 (8.x recommended)
- **MySQL** / MariaDB 5.7+
- **Composer**
- **XAMPP** (or any Apache/Nginx + PHP stack)
- PHP extensions: `openssl`, `pdo_mysql`, `mbstring`

---

### Step 1 — Clone / Copy the Project

Place the project inside your web root:

```
C:\xampp\htdocs\www\all-in-one\mailSender\
```

Or, if using Apache directly:

```
C:\xampp\htdocs\mailSender\
```

---

### Step 2 — Install PHP Dependencies

Open a terminal inside the project root and run:

```bash
composer install
```

This will install **PHPMailer 6.9** into the `vendor/` directory.

---

### Step 3 — Create the Database

1. Start **MySQL** from the XAMPP Control Panel.
2. Open **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Click **Import** → select `database.sql` → click **Go**.

This will:
- Create the `mail_sender_db` database
- Create all 4 tables (`users`, `smtp_settings`, `templates`, `email_logs`)
- Insert a default admin account

**Default credentials:**
```
Username: admin
Password: admin123
```

> ⚠️ **Change the default password immediately after first login.**

---

### Step 4 — Configure Database Connection

Open `config/db.php` and update if your MySQL credentials differ:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // ← your MySQL username
define('DB_PASS', '');        // ← your MySQL password
define('DB_NAME', 'mail_sender_db');
```

---

### Step 5 — Configure Encryption Key

Open `config/encryption.php` and change the secret key to something unique and strong:

```php
private $secretKey = "A_VERY_SECRET_KEY_CHANGEME_123456";
//                    ↑ Replace this with a random 32+ character string
```

> ⚠️ **Critical:** If you change this key after SMTP passwords have been saved, they will no longer decrypt correctly. Set this once before any SMTP configuration.

---

### Step 6 — Access the App

Start Apache and MySQL from XAMPP Control Panel, then open:

```
http://localhost/all-in-one/mailSender/
```

---

## 🚀 Usage Guide

### 1. Login
Navigate to the app URL. You will be redirected to `auth/login.php`. Use your credentials.

---

### 2. Configure SMTP (`smtp/settings.php`)

Before sending any emails, configure your SMTP credentials:

| Provider | Host | Port | Encryption |
|---|---|---|---|
| **Gmail** | `smtp.gmail.com` | `587` | TLS |
| **Outlook / Office 365** | `smtp.office365.com` | `587` | TLS |
| **Yahoo** | `smtp.mail.yahoo.com` | `587` | TLS |
| **Zoho** | `smtp.zoho.com` | `587` | TLS |
| **SSL (any)** | your host | `465` | SSL |

> **Gmail users:** You must use an **App Password**, not your regular Gmail password. Generate one at: [Google Account → Security → App Passwords](https://myaccount.google.com/apppasswords)

Quick-fill buttons for all major providers are available on the settings page.

---

### 3. Create Templates (`templates/add.php`)

Templates support two modes:

#### Plain Text Mode
- Write your message in the textarea
- Line breaks are preserved and automatically converted to `<br>` in the email

#### HTML Mode
- Write raw HTML in the code editor
- Switch to **Live Preview** tab to see a real-time render inside an `<iframe>`
- Full HTML is sent as-is, wrapped in a clean UTF-8 email envelope

#### Supported Placeholders

Use these in the **Subject** or **Body** of any template:

| Placeholder | Replaced With |
|---|---|
| `{{name}}` | Recipient's name (entered at send time) |
| `{{date}}` | Date value (entered at send time) |
| `{{custom}}` | Custom value (entered at send time) |

Spaces inside braces are also supported: `{{ name }}` works identically to `{{name}}`.

Click the **Quick Insert** chips in the editor to insert placeholders at cursor position.

---

### 4. Send Emails (`send-mail/index.php`)

1. **Select a Template** (optional) — pre-fills Subject and Body. The template type (Plain/HTML) is automatically set.
2. **Enter Recipients** — comma-separated email addresses. Maximum **5 per request**.
3. **Fill Placeholder Values** — Name, Date, Custom fields (used to resolve `{{name}}` etc.)
4. Click **🚀 Launch Campaign**

Results appear in a real-time terminal-style output box showing per-recipient success/failure status.

---

### 5. Dashboard (`index.php`)

The dashboard shows:
- **Saved Templates** count
- **Emails Sent** (successful) count
- Quick navigation to Send Mail and SMTP Settings
- **Recent Email Logs** — last 5 sends with recipient, subject, status, and timestamp

---

## 🔧 SMTP Debugger

If emails are not sending, use the built-in diagnostic tool:

```
http://localhost/all-in-one/mailSender/smtp-debug.php
```

It performs three checks:
1. **TCP Socket Test** — verifies your server can reach the SMTP host on the configured port
2. **PHPMailer Full Auth Test** — attempts a real SMTP handshake with verbose debug output
3. **PHP Configuration Check** — verifies `openssl`, `allow_url_fopen`, and `mail()` availability

> ⚠️ **Delete `smtp-debug.php` after debugging.** It exposes your decrypted SMTP credentials in the browser.

---

## 🔒 Security Overview

| Concern | Implementation |
|---|---|
| SQL Injection | PDO prepared statements throughout — zero raw queries |
| XSS | All output escaped via `hb()` function (`htmlspecialchars`) |
| CSRF | Random 64-char hex token generated per session, validated on every POST |
| Password Storage | bcrypt via PHP `password_hash()` / `password_verify()` |
| SMTP Password | AES-256-CBC encrypted before writing to DB |
| Auth Guard | `require_login()` on every protected page |
| Rate Limiting | Session-based `rate_limit()` (enabled in production) |
| SSL Peer Verify | Disabled for local/dev SMTP compatibility (`verify_peer = false`) — enable in production |

---

## 🧩 Key Components

### `config/functions.php`

| Function | Purpose |
|---|---|
| `hb($data)` | XSS-safe HTML output — wraps `htmlspecialchars` |
| `generate_csrf_token()` | Creates/returns a 64-char hex CSRF token in session |
| `validate_csrf_token($token)` | Validates POST token against session — dies on mismatch |
| `rate_limit($seconds)` | Blocks requests within N seconds of the last one |
| `redirect($url)` | `header()` redirect + `exit` |
| `is_logged_in()` | Checks `$_SESSION['user_id']` |
| `require_login()` | Redirects to login if not authenticated |

### `config/encryption.php` — `MailSenderEncryption`

| Method | Purpose |
|---|---|
| `encrypt($data)` | AES-256-CBC encrypt → base64 encode (IV prepended) |
| `decrypt($data)` | base64 decode → extract IV → AES-256-CBC decrypt |

### `send-mail/process.php`

| Function | Purpose |
|---|---|
| `replace_placeholders()` | Resolves `{{name}}`, `{{date}}`, `{{custom}}` in subject + body |
| `format_body_for_email()` | Detects if body is HTML or plain text; applies `nl2br` to plain |

The engine loops over each recipient individually, creating a fresh `PHPMailer` instance per email. Results and logs are accumulated and returned as a JSON response.

---

## 🌐 Supported SMTP Providers

| Provider | Host | TLS Port | SSL Port |
|---|---|---|---|
| Gmail | `smtp.gmail.com` | 587 | 465 |
| Outlook / Microsoft 365 | `smtp.office365.com` | 587 | — |
| Yahoo | `smtp.mail.yahoo.com` | 587 | 465 |
| Zoho Mail | `smtp.zoho.com` | 587 | 465 |
| Custom / Self-hosted | your domain | varies | varies |

---

## 🐛 Troubleshooting

| Symptom | Likely Cause | Fix |
|---|---|---|
| `Database connection failed` | Wrong DB credentials | Update `config/db.php` |
| `SMTP settings not configured` | No SMTP row in DB | Go to SMTP Settings and save |
| `Connection/Server Error` | Port blocked by firewall | Use SMTP debugger; try port 465 with SSL |
| Gmail auth failure | Using regular password | Generate an App Password |
| Emails not received | Spam/junk filtering | Check recipient spam folder; verify SPF/DKIM |
| Decryption returns garbage | Encryption key changed after saving | Re-save SMTP settings with new key |
| CSRF error on POST | Session expired | Refresh the page and resubmit |

---

## 📦 Dependencies

| Package | Version | Purpose |
|---|---|---|
| `phpmailer/phpmailer` | `^6.9` | SMTP email sending library |

Frontend (CDN, no install needed):

| Library | Version | Purpose |
|---|---|---|
| Bootstrap | 5.3.0 | UI framework |
| Bootstrap Icons | 1.10.5 | Icon set |
| Google Fonts — Inter | latest | Typography |
| Google Fonts — Noto Sans Devanagari | latest | Hindi/Devanagari script support |

---

## 📄 License

MIT — free to use, modify, and distribute.

---

*Built by **Aman** — part of the all-in-one toolset.*
