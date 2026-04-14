<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);

require '../../Files/Database/Connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = "";
$error   = "";

// جلب معلومات المستخدم
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("المستخدم غير موجود");
}

// جلب اسم المستخدم للـ topbar
$userName = $user['full_name'];

/* ===== Update profile ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Update name (بدون كلمة مرور) */
    if (isset($_POST['update_name'])) {
        $fullName = trim($_POST['full_name']);

        if ($fullName !== "") {
            $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $userId]);
            $message = "✅ تم تحديث الاسم بنجاح";
            $user['full_name'] = $fullName;
            $userName = $fullName;
        } else {
            $error = "❌ الاسم لا يمكن أن يكون فارغًا";
        }
    }

    /* Update email (يتطلب كلمة المرور) */
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email']);
        $password = $_POST['password_email'];

        // التحقق من كلمة المرور أولاً
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($password, $hash)) {
            $error = "❌ كلمة المرور غير صحيحة";
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ البريد الإلكتروني غير صالح";
        } else {
            // التحقق من عدم وجود البريد الإلكتروني لمستخدم آخر
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $userId]);
            
            if ($stmt->rowCount() > 0) {
                $error = "❌ البريد الإلكتروني مستخدم بالفعل";
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$newEmail, $userId]);
                $message = "✅ تم تحديث البريد الإلكتروني بنجاح";
                $user['email'] = $newEmail;
            }
        }
    }

    /* Update password */
    if (isset($_POST['update_password'])) {
        $current = $_POST['current_password'];
        $new     = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $error = "❌ كلمة المرور الحالية غير صحيحة";
        } elseif (strlen($new) < 6) {
            $error = "❌ كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل";
        } elseif ($new !== $confirm) {
            $error = "❌ كلمة المرور الجديدة وتأكيدها غير متطابقين";
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $userId]);
            $message = "✅ تم تغيير كلمة المرور بنجاح";
        }
    }
}

include '../../Files/assets/topbar.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الملف الشخصي | ResumeManager</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
    --bg: #1a1e24;
    --surface: #242830;
    --paper: #2a2e36;
    --primary: #8b9bb5;
    --accent: #6d7f99;
    --text: #e8ecf2;
    --text-light: #b8c2d0;
    --muted: #8f9bb3;
    --border: #363c47;
    --border-dark: #2f3540;
    --radius: 16px;
    --transition: 0.25s ease;
    --shadow: 0 8px 20px rgba(0,0,0,0.3);
    --shadow-hover: 0 12px 30px rgba(0,0,0,0.4);
    --gradient: linear-gradient(135deg, #2c3e50, #1e2a36);
    --gradient-light: linear-gradient(135deg, #8b9bb5, #6d7f99);
    --success-bg: #1e3a2f;
    --success-text: #b7e4c7;
    --success-border: #2d4d3a;
    --error-bg: #3a2626;
    --error-text: #f8b4b4;
    --error-border: #5a3838;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', 'Cairo', sans-serif;
}

body {
    background: var(--bg);
    min-height: 100vh;
    padding: 0 20px 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* ===== Container ===== */
.container {
    max-width: 1000px;
    width: 100%;
    margin: 20px auto;
}

/* ===== Card ===== */
.card {
    background: var(--surface);
    border-radius: 24px;
    padding: 30px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
    border: 1px solid var(--border);
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.card:hover {
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-light);
    opacity: 0;
    transition: var(--transition);
}

.card:hover::before {
    opacity: 1;
}

/* Profile Header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-bottom: 0;
    background: linear-gradient(135deg, var(--surface), var(--paper));
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--gradient-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: #1a1e24;
    font-weight: 700;
    border: 3px solid var(--primary);
    box-shadow: var(--shadow);
    position: relative;
    z-index: 1;
}

.profile-info {
    flex: 1;
}

.profile-info h2 {
    margin: 0 0 10px 0;
    color: var(--text);
    font-size: 28px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-info h2 i {
    color: var(--primary);
    font-size: 24px;
}

.profile-info .email-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--bg);
    padding: 8px 16px;
    border-radius: 40px;
    border: 1px solid var(--border);
    color: var(--text-light);
    font-size: 14px;
}

.profile-info .email-badge i {
    color: var(--primary);
}

/* Forms Grid */
.forms-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .forms-grid {
        grid-template-columns: 1fr;
    }
}

.form-card {
    background: var(--bg);
    border-radius: 20px;
    padding: 25px;
    border: 1px solid var(--border-dark);
    height: fit-content;
    transition: var(--transition);
}

.form-card.security-card {
    border-right: 3px solid var(--primary);
}

.form-card.security-card .section-title i {
    color: var(--primary);
}

/* Form Elements */
form {
    display: grid;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-size: 13px;
    color: var(--text-light);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-group label i {
    color: var(--primary);
    font-size: 14px;
    width: 18px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper i {
    position: absolute;
    right: 14px;
    color: var(--muted);
    font-size: 16px;
    pointer-events: none;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 45px 14px 14px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text);
    font-size: 14px;
    transition: var(--transition);
}

.input-wrapper input:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--paper);
    box-shadow: 0 0 0 3px rgba(139, 155, 181, 0.1);
}

.input-wrapper input::placeholder {
    color: var(--muted);
}

.input-wrapper input[type="password"] {
    letter-spacing: 2px;
}

/* Security note */
.security-note {
    background: var(--surface);
    border-radius: 10px;
    padding: 10px 12px;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-light);
    border: 1px dashed var(--border);
}

.security-note i {
    color: var(--primary);
    font-size: 14px;
}

/* Buttons */
.btn {
    padding: 14px;
    border: none;
    border-radius: 40px;
    background: var(--gradient-light);
    color: #1a1e24;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 8px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    filter: brightness(1.1);
}

.btn i {
    font-size: 16px;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text);
}

.btn-outline:hover {
    background: var(--primary);
    color: #1a1e24;
    border-color: var(--primary);
}

/* Messages */
.success, .error {
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    font-size: 14px;
    border: 1px solid transparent;
    animation: slideDown 0.3s ease;
}

.success {
    background: var(--success-bg);
    color: var(--success-text);
    border-color: var(--success-border);
}

.success i {
    color: #7ac99a;
    font-size: 18px;
}

.error {
    background: var(--error-bg);
    color: var(--error-text);
    border-color: var(--error-border);
}

.error i {
    color: #f8b4b4;
    font-size: 18px;
}

/* Section title */
.section-title {
    font-size: 18px;
    margin-bottom: 20px;
    color: var(--text);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
}

.section-title i {
    color: var(--primary);
    font-size: 20px;
}

/* Password hint */
.password-hint {
    font-size: 12px;
    color: var(--muted);
    margin-top: -10px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.password-hint i {
    color: var(--primary);
    font-size: 12px;
}

/* Stats Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--bg);
    border-radius: 20px;
    padding: 20px;
    border: 1px solid var(--border-dark);
    display: flex;
    align-items: center;
    gap: 15px;
    transition: var(--transition);
}

.stat-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: var(--surface);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 22px;
    border: 1px solid var(--border);
}

.stat-info h4 {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 5px;
}

.stat-number {
    font-size: 22px;
    font-weight: 600;
    color: var(--text);
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card, .stat-card, .form-card {
    animation: fadeIn 0.5s ease;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--bg);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary);
}

/* Responsive */
@media(max-width:900px){
    .topbar-center span {
        display: none;
    }
}

@media(max-width:768px){
    .container {
        padding: 0 10px;
    }
    
    .card {
        padding: 25px;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .profile-info {
        text-align: center;
    }
    
    .profile-info h2 {
        justify-content: center;
    }
    
    .profile-info .email-badge {
        justify-content: center;
    }
}

@media(max-width:600px){
    body {
        padding: 0 15px 20px 15px;
    }
    
    .card {
        padding: 20px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 34px;
    }
    
    .profile-info h2 {
        font-size: 24px;
    }
    
    .form-card {
        padding: 20px;
    }
    
    .btn {
        padding: 12px;
    }
}

/* Tooltip */
[data-tooltip] {
    position: relative;
    cursor: help;
}

[data-tooltip]:before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: var(--surface);
    color: var(--text);
    font-size: 12px;
    border-radius: 6px;
    white-space: nowrap;
    border: 1px solid var(--border);
    opacity: 0;
    pointer-events: none;
    transition: var(--transition);
    margin-bottom: 5px;
    z-index: 10;
}

[data-tooltip]:hover:before {
    opacity: 1;
}
</style>
</head>

<body>

<div class="container">
    <!-- Profile Header Card -->
    <div class="card profile-header">
        <div class="profile-avatar">
            <?= mb_substr($user['full_name'], 0, 1) ?>
        </div>
        <div class="profile-info">
            <h2>
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($user['full_name']) ?>
            </h2>
            <div class="email-badge">
                <i class="fas fa-envelope"></i>
                <?= htmlspecialchars($user['email']) ?>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-info">
                <h4>السير الذاتية</h4>
                <span class="stat-number">3</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h4>عضو منذ</h4>
                <span class="stat-number">2024</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="stat-info">
                <h4>أمان الحساب</h4>
                <span class="stat-number">عالٍ</span>
            </div>
        </div>
    </div>

    <?php if($message): ?>
        <div class="success">
            <i class="fas fa-check-circle"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Forms Grid -->
    <div class="forms-grid">
        <!-- Update Name (بدون كلمة مرور) -->
        <div class="form-card">
            <div class="section-title">
                <i class="fas fa-user-edit"></i>
                تعديل الاسم
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        الاسم الكامل
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-pencil-alt"></i>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required placeholder="أدخل اسمك الكامل">
                    </div>
                </div>
                <button type="submit" name="update_name" class="btn">
                    <i class="fas fa-save"></i>
                    حفظ التغييرات
                </button>
                <div class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    <span>تحديث الاسم لا يتطلب كلمة مرور</span>
                </div>
            </form>
        </div>

        <!-- Update Email (يتطلب كلمة مرور) -->
        <div class="form-card security-card">
            <div class="section-title">
                <i class="fas fa-envelope"></i>
                تعديل البريد الإلكتروني
                <span style="margin-right: auto; font-size: 12px; color: var(--primary);" data-tooltip="يتطلب كلمة المرور لتأكيد الهوية">
                    <i class="fas fa-lock"></i>
                </span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>
                        <i class="fas fa-at"></i>
                        البريد الإلكتروني الجديد
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required placeholder="example@domain.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-lock"></i>
                        كلمة المرور للتأكيد
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password_email" required placeholder="أدخل كلمة المرور الحالية">
                    </div>
                </div>

                <button type="submit" name="update_email" class="btn">
                    <i class="fas fa-sync-alt"></i>
                    تحديث البريد
                </button>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt"></i>
                    <span>لأمان حسابك، يجب إدخال كلمة المرور الحالية</span>
                </div>
            </form>
        </div>

        <!-- Update Password -->
        <div class="form-card" style="grid-column: span 2;">
            <div class="section-title">
                <i class="fas fa-lock"></i>
                تغيير كلمة المرور
            </div>
            <form method="POST" style="grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label>
                        <i class="fas fa-key"></i>
                        كلمة المرور الحالية
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="current_password" required placeholder="••••••••">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-lock-open"></i>
                        كلمة المرور الجديدة
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" name="new_password" required placeholder="6 أحرف على الأقل">
                    </div>
                    <div class="password-hint">
                        <i class="fas fa-info-circle"></i>
                        يجب أن تكون 6 أحرف على الأقل
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-check-circle"></i>
                        تأكيد كلمة المرور
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-check"></i>
                        <input type="password" name="confirm_password" required placeholder="أعد إدخال كلمة المرور">
                    </div>
                </div>

                <button type="submit" name="update_password" class="btn" style="grid-column: span 2;">
                    <i class="fas fa-exchange-alt"></i>
                    تغيير كلمة المرور
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>