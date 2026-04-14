<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
require '../../Files/Database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// التحقق من وجود معرف السيرة
if (!isset($_GET['cv_id']) || empty($_GET['cv_id'])) {
    header("Location: my-resumes.php"); // تم التعديل هنا
    exit;
}

$cvId = (int)$_GET['cv_id'];

// التحقق من ملكية السيرة
$stmt = $conn->prepare("SELECT id FROM cvs WHERE id = ? AND user_id = ?");
$stmt->execute([$cvId, $userId]);
if (!$stmt->fetch()) {
    header("Location: my-resumes.php"); // تم التعديل هنا
    exit;
}

$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userName = $stmt->fetchColumn() ?: 'مستخدم';

$success = false;
$error = '';

/* ===== جلب البيانات الحالية ===== */
// Personal Info
$stmt = $conn->prepare("SELECT * FROM personal_info WHERE cv_id = ?");
$stmt->execute([$cvId]);
$personalInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Education
$stmt = $conn->prepare("SELECT * FROM education WHERE cv_id = ? ORDER BY id");
$stmt->execute([$cvId]);
$educationItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Experience
$stmt = $conn->prepare("SELECT * FROM experience WHERE cv_id = ? ORDER BY id");
$stmt->execute([$cvId]);
$experienceItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Courses
$stmt = $conn->prepare("SELECT * FROM courses WHERE cv_id = ? ORDER BY id");
$stmt->execute([$cvId]);
$courseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Projects
$stmt = $conn->prepare("SELECT * FROM projects WHERE cv_id = ? ORDER BY id");
$stmt->execute([$cvId]);
$projectItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Skills
$stmt = $conn->prepare("SELECT * FROM skills WHERE cv_id = ? ORDER BY id");
$stmt->execute([$cvId]);
$skillItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CV info
$stmt = $conn->prepare("SELECT name, template_name, is_published FROM cvs WHERE id = ?");
$stmt->execute([$cvId]);
$cvInfo = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== UPDATE CV ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // تحديث معلومات السيرة الأساسية
        $stmt = $conn->prepare("UPDATE cvs SET name = ?, template_name = ?, is_published = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['template_name'] ?? 'default',
            $_POST['is_published'] ?? 0,
            $cvId
        ]);

        // تحديث المعلومات الشخصية
        $stmt = $conn->prepare("
            UPDATE personal_info SET 
                job_title = ?, email = ?, phone = ?, address = ?, summary = ?,
                date_of_birth = ?, nationality = ?, linkedin = ?, github = ?, website = ?
            WHERE cv_id = ?
        ");
        $stmt->execute([
            $_POST['job_title'] ?? null,
            $_POST['email'] ?? null,
            $_POST['phone'] ?? null,
            $_POST['address'] ?? null,
            $_POST['summary'] ?? null,
            !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            $_POST['nationality'] ?? null,
            $_POST['linkedin'] ?? null,
            $_POST['github'] ?? null,
            $_POST['website'] ?? null,
            $cvId
        ]);

        // حذف البيانات القديمة
        $conn->prepare("DELETE FROM education WHERE cv_id = ?")->execute([$cvId]);
        $conn->prepare("DELETE FROM experience WHERE cv_id = ?")->execute([$cvId]);
        $conn->prepare("DELETE FROM courses WHERE cv_id = ?")->execute([$cvId]);
        $conn->prepare("DELETE FROM projects WHERE cv_id = ?")->execute([$cvId]);
        $conn->prepare("DELETE FROM skills WHERE cv_id = ?")->execute([$cvId]);

        // Education - إضافة البيانات الجديدة
        foreach ($_POST['degree'] ?? [] as $i => $v) {
            if (empty($v)) continue;
            
            $stmt = $conn->prepare("
                INSERT INTO education (
                    cv_id, degree, institution, field_of_study, 
                    start_date, end_date, description
                ) VALUES (?,?,?,?,?,?,?)
            ");
            
            $stmt->execute([
                $cvId,
                $v,
                $_POST['institution'][$i] ?? null,
                $_POST['field_of_study'][$i] ?? null,
                !empty($_POST['edu_start'][$i]) ? $_POST['edu_start'][$i] : null,
                !empty($_POST['edu_end'][$i]) ? $_POST['edu_end'][$i] : null,
                $_POST['edu_description'][$i] ?? null
            ]);
        }

        // Experience - إضافة البيانات الجديدة
        foreach ($_POST['job_title_exp'] ?? [] as $i => $v) {
            if (empty($v)) continue;
            
            $stmt = $conn->prepare("
                INSERT INTO experience (
                    cv_id, job_title, company, start_date, 
                    end_date, is_current, description
                ) VALUES (?,?,?,?,?,?,?)
            ");
            
            $stmt->execute([
                $cvId,
                $v,
                $_POST['company'][$i] ?? null,
                !empty($_POST['exp_start'][$i]) ? $_POST['exp_start'][$i] : null,
                !empty($_POST['exp_end'][$i]) ? $_POST['exp_end'][$i] : null,
                isset($_POST['is_current'][$i]) ? 1 : 0,
                $_POST['exp_description'][$i] ?? null
            ]);
        }

        // Skills - إضافة البيانات الجديدة
        foreach ($_POST['skill_name'] ?? [] as $i => $v) {
            if (empty($v)) continue;
            
            $stmt = $conn->prepare("
                INSERT INTO skills (cv_id, skill_name, skill_level)
                VALUES (?,?,?)
            ");
            
            $level = $_POST['skill_level'][$i] ?? 'Intermediate';
            $allowedLevels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
            if (!in_array($level, $allowedLevels)) {
                $level = 'Intermediate';
            }
            
            $stmt->execute([$cvId, $v, $level]);
        }

        // Courses - إضافة البيانات الجديدة
        foreach ($_POST['course_name'] ?? [] as $i => $v) {
            if (empty($v)) continue;
            
            $stmt = $conn->prepare("
                INSERT INTO courses (cv_id, course_name, provider, completion_date)
                VALUES (?,?,?,?)
            ");
            
            $stmt->execute([
                $cvId,
                $v,
                $_POST['provider'][$i] ?? null,
                !empty($_POST['course_date'][$i]) ? $_POST['course_date'][$i] : null
            ]);
        }

        // Projects - إضافة البيانات الجديدة
        foreach ($_POST['project_name'] ?? [] as $i => $v) {
            if (empty($v)) continue;
            
            $stmt = $conn->prepare("
                INSERT INTO projects (cv_id, project_name, project_description, project_link)
                VALUES (?,?,?,?)
            ");
            
            $stmt->execute([
                $cvId,
                $v,
                $_POST['project_description'][$i] ?? null,
                $_POST['project_link'][$i] ?? null
            ]);
        }

        $conn->commit();
        $success = true;
        
        // إعادة جلب البيانات المحدثة
        // Personal Info
        $stmt = $conn->prepare("SELECT * FROM personal_info WHERE cv_id = ?");
        $stmt->execute([$cvId]);
        $personalInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Education
        $stmt = $conn->prepare("SELECT * FROM education WHERE cv_id = ? ORDER BY id");
        $stmt->execute([$cvId]);
        $educationItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Experience
        $stmt = $conn->prepare("SELECT * FROM experience WHERE cv_id = ? ORDER BY id");
        $stmt->execute([$cvId]);
        $experienceItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Courses
        $stmt = $conn->prepare("SELECT * FROM courses WHERE cv_id = ? ORDER BY id");
        $stmt->execute([$cvId]);
        $courseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Projects
        $stmt = $conn->prepare("SELECT * FROM projects WHERE cv_id = ? ORDER BY id");
        $stmt->execute([$cvId]);
        $projectItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Skills
        $stmt = $conn->prepare("SELECT * FROM skills WHERE cv_id = ? ORDER BY id");
        $stmt->execute([$cvId]);
        $skillItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $conn->rollBack();
        $error = "فشل تحديث السيرة: " . $e->getMessage();
    }
}

include '../../Files/assets/topbar.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل السيرة الذاتية</title>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700&display=swap" rel="stylesheet">
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
    --radius: 12px;
    --transition: 0.25s ease;
    --shadow: 0 8px 20px rgba(0,0,0,0.3);
    --shadow-hover: 0 12px 30px rgba(0,0,0,0.4);
    --gradient: linear-gradient(135deg, #2c3e50, #1e2a36);
    --success-bg: #1e3a2f;
    --success-text: #b7e4c7;
    --success-border: #2d4d3a;
    --error-bg: #3a2626;
    --error-text: #f8b4b4;
    --error-border: #5a3838;
}

/* ===== RESET ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', 'Cairo', sans-serif;
}

body {
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    direction: rtl;
    font-weight: 400;
}

/* ===== CONTENT ===== */
.content {
    display: flex;
    justify-content: center;
    padding: 20px;
}

/* ===== CV PAPER ===== */
.cv-paper {
    width: 250mm;
    min-height: 297mm;
    background: var(--paper);
    border-radius: 24px;
    padding: 45px;
    box-shadow: var(--shadow);
    animation: fadeIn 0.5s ease;
    border: 1px solid var(--border);
}

/* ===== TABS ===== */
.tabs-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 35px;
    flex-wrap: wrap;
}

.tabs {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    background: var(--surface);
    padding: 4px;
    border-radius: 50px;
    border: 1px solid var(--border);
}

.tab {
    padding: 8px 18px;
    background: transparent;
    border: none;
    border-radius: 40px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: white;
    transition: var(--transition);
}

.tab:hover {
    color: white;
    background: var(--border);
}

.tab.active {
    background: var(--primary);
    color: #1a1e24;
    font-weight: 600;
}

.save-btn {
    height: 44px;
    padding: 0 28px;
    border-radius: 40px;
    background: var(--primary);
    color: #1a1e24;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: var(--transition);
}

.save-btn:hover {
    background: var(--accent);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}

/* ===== SECTIONS ===== */
.section {
    display: none;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 20px;
}

.section.active {
    display: block;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== GRID ===== */
.grid {
    display: grid;
    gap: 18px;
    margin-bottom: 12px;
}

.grid.two {
    grid-template-columns: repeat(2, 1fr);
}

.grid.three {
    grid-template-columns: repeat(3, 1fr);
}

/* ===== FIELD ===== */
.field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 4px;
}

.field label {
    font-size: 13px;
    font-weight: 500;
    color: white;
    text-align: center;
}

/* ===== INPUTS ===== */
input, textarea, select {
    width: 100%;
    padding: 12px 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--text);
    font-size: 14px;
    transition: var(--transition);
    font-family: 'Inter', 'Cairo', sans-serif;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: var(--primary);
    background: var(--surface);
    box-shadow: 0 0 0 3px rgba(139, 155, 181, 0.1);
}

input::placeholder, textarea::placeholder {
    color: var(--muted);
    font-size: 13px;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

/* ===== ITEMS ===== */
.item {
    border: 1px solid var(--border-dark);
    padding: 25px;
    border-radius: 18px;
    margin-bottom: 20px;
    background: var(--bg);
    position: relative;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
}

.item:last-of-type {
    margin-bottom: 0;
}

/* ===== BUTTONS ===== */
.btn {
    margin-top: 20px;
    padding: 12px 26px;
    border-radius: 40px;
    background: var(--primary);
    color: #1a1e24;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn:hover {
    background: var(--accent);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.3);
}

.btn.add {
    background: var(--border);
    color: var(--text);
    border: 1px solid var(--border-dark);
    box-shadow: none;
    font-weight: 500;
}

.btn.add:hover {
    background: var(--primary);
    color: #1a1e24;
}

/* ===== MESSAGES ===== */
.success, .error {
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 25px;
    text-align: center;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 14px;
}

.success {
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid var(--success-border);
}

.success i {
    color: #7ac99a;
    font-size: 18px;
}

.error {
    background: var(--error-bg);
    color: var(--error-text);
    border: 1px solid var(--error-border);
}

.error i {
    color: #f8b4b4;
    font-size: 18px;
}

/* ===== CURRENT JOB CHECKBOX ===== */
.current-job {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    height: 100%;
    padding-top: 24px;
}

.current-job label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border: 1px solid var(--border);
    border-radius: 40px;
    background: var(--bg);
    cursor: pointer;
    font-size: 14px;
    color: var(--text-light);
    transition: var(--transition);
    white-space: nowrap;
    text-align: center;
}

.current-job label:hover {
    border-color: var(--primary);
    color: white;
    background: var(--surface);
}

.current-job input {
    width: auto;
    accent-color: var(--primary);
}

/* تعطيل تاريخ الانتهاء */
.end-date.disabled input {
    opacity: 0.5;
    pointer-events: none;
    background: var(--border);
}

/* ===== REMOVE BUTTON ===== */
.remove-btn {
    position: absolute;
    top: 15px;
    left: 15px;
    background: var(--error-bg);
    color: var(--error-text);
    border: 1px solid var(--error-border);
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.remove-btn:hover {
    background: #b91c1c;
    color: white;
    border-color: #b91c1c;
}

/* ===== SKILLS SECTION ===== */
.skill-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.skill-row input {
    flex: 2;
}

.skill-level {
    width: 140px;
    background: var(--bg);
}

.skill-row .remove-btn {
    position: static;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--error-bg);
    color: var(--error-text);
    border: 1px solid var(--error-border);
}

.skill-row .remove-btn:hover {
    background: #b91c1c;
    color: white;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
    .topbar-center span {
        display: none;
    }
    
    .topbar-center {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .grid.two,
    .grid.three {
        grid-template-columns: 1fr;
    }
    
    .tabs-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .save-btn {
        width: 100%;
        justify-content: center;
    }
    
    .cv-paper {
        padding: 25px;
    }
    
    .section {
        padding: 20px;
    }
    
    .item {
        padding: 20px;
    }
    
    .skill-row {
        flex-wrap: wrap;
    }
    
    .skill-level {
        width: 100%;
    }
}

@media (max-width: 600px) {
    .topbar {
        flex-wrap: wrap;
        height: auto;
        gap: 12px;
        padding: 16px;
    }
    
    .topbar-center {
        order: 3;
        width: 100%;
        justify-content: space-around;
    }
    
    .tabs {
        justify-content: center;
        width: 100%;
    }
    
    .tab {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
    }
}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== CUSTOM SCROLLBAR ===== */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
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

/* ===== ADDITIONAL IMPROVEMENTS ===== */
input[type="date"] {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%238b9bb5' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: left 12px center;
    background-size: 16px;
    padding-left: 40px;
}

select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%238b9bb5' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: left 12px center;
    background-size: 16px;
    padding-left: 40px;
}

/* تحسين الظهور في الوضع الداكن */
.cv-paper {
    background: var(--paper);
}

.section {
    background: var(--surface);
}

.item {
    background: var(--bg);
}

/* تحسين النصوص */
h1, h2, h3, h4 {
    color: var(--text);
    font-weight: 600;
}

/* تحسين الأيقونات */
.fa-solid, .fa-regular {
    color: var(--primary);
}
</style>

</head>

<body>

<!-- ================= CONTENT ================= -->
<div class="content">
  <div class="cv-paper">

    <?php if($success): ?>
      <div class="success">
        <i class="fa-solid fa-circle-check"></i>
        تم تحديث السيرة الذاتية بنجاح
      </div>
    <?php endif; ?>

    <?php if($error): ?>
      <div class="error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">

      <!-- ===== TABS HEADER ===== -->
      <div class="tabs-header">
        <div class="tabs">
          <div class="tab active" onclick="openTab(0)">البيانات الشخصية</div>
          <div class="tab" onclick="openTab(1)">المؤهلات</div>
          <div class="tab" onclick="openTab(2)">الخبرات</div>
          <div class="tab" onclick="openTab(3)">الدورات</div>
          <div class="tab" onclick="openTab(4)">المشاريع</div>
          <div class="tab" onclick="openTab(5)">المهارات</div>
        </div>

        <button type="submit" class="btn save-btn">
          <i class="fa-solid fa-pen-to-square"></i>
          تحديث السيرة
        </button>
      </div>

      <!-- ================= TABS CONTENT ================= -->
      <div class="tabs-content">

      <!-- ===== PERSONAL INFO ===== -->
      <div class="section active">
        <div class="field">
          <label>الاسم الثلاثي</label>
          <input name="name" placeholder="مثال: وليد بن محمد الحربي" 
                 value="<?= htmlspecialchars($cvInfo['name'] ?? '') ?>" required>
        </div>

        <div class="field">
          <label>نبذة مختصرة</label>
          <textarea name="summary" placeholder="نبذة قصيرة تعرّف عنك مهنيًا خلال 3–4 أسطر"><?= htmlspecialchars($personalInfo['summary'] ?? '') ?></textarea>
        </div>

        <div class="grid two">
          <div class="field">
            <label>رقم الجوال</label>
            <input name="phone" placeholder="05xxxxxxxx" value="<?= htmlspecialchars($personalInfo['phone'] ?? '') ?>">
          </div>

          <div class="field">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" placeholder="example@email.com" value="<?= htmlspecialchars($personalInfo['email'] ?? '') ?>">
          </div>
        </div>

        <div class="grid two">
          <div class="field">
            <label>تاريخ الميلاد</label>
            <input type="date" name="date_of_birth" value="<?= htmlspecialchars($personalInfo['date_of_birth'] ?? '') ?>">
          </div>

          <div class="field">
            <label>الجنسية</label>
            <input name="nationality" placeholder="سعودي" value="<?= htmlspecialchars($personalInfo['nationality'] ?? '') ?>">
          </div>
        </div>

        <div class="field">
          <label>العنوان</label>
          <input name="address" placeholder="المدينة – الدولة" value="<?= htmlspecialchars($personalInfo['address'] ?? '') ?>">
        </div>

        <div class="grid three">
          <div class="field">
            <label>LinkedIn</label>
            <input name="linkedin" placeholder="linkedin.com/in/..." value="<?= htmlspecialchars($personalInfo['linkedin'] ?? '') ?>">
          </div>

          <div class="field">
            <label>GitHub</label>
            <input name="github" placeholder="github.com/username" value="<?= htmlspecialchars($personalInfo['github'] ?? '') ?>">
          </div>

          <div class="field">
            <label>الموقع الشخصي</label>
            <input name="website" placeholder="www.example.com" value="<?= htmlspecialchars($personalInfo['website'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- ===== EDUCATION ===== -->
      <div class="section">
        <?php if(empty($educationItems)): ?>
        <div class="item">
          <button type="button" class="remove-btn" onclick="removeItem(this)">
            <i class="fa-solid fa-trash"></i>
          </button>
          <div class="grid three">
            <div class="field">
              <label>الجهة التعليمية</label>
              <input name="institution[]" placeholder="اسم الجامعة / المعهد">
            </div>
            <div class="field">
              <label>التخصص</label>
              <input name="field_of_study[]" placeholder="علوم حاسب، هندسة...">
            </div>
            <div class="field">
              <label>درجة المؤهل</label>
              <input name="degree[]" placeholder="دبلوم، بكالريوس">
            </div>
          </div>
          <div class="grid two">
            <div class="field">
              <label>تاريخ البدء</label>
              <input type="date" name="edu_start[]">
            </div>
            <div class="field">
              <label>تاريخ الانتهاء</label>
              <input type="date" name="edu_end[]">
            </div>
          </div>
          <div class="field">
            <label>الوصف / ملاحظات</label>
            <textarea name="edu_description[]" placeholder="تفاصيل إضافية عن الدراسة أو الإنجازات"></textarea>
          </div>
        </div>
        <?php else: ?>
          <?php foreach($educationItems as $edu): ?>
          <div class="item">
            <button type="button" class="remove-btn" onclick="removeItem(this)">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="grid three">
              <div class="field">
                <label>الجهة التعليمية</label>
                <input name="institution[]" placeholder="اسم الجامعة / المعهد" value="<?= htmlspecialchars($edu['institution'] ?? '') ?>">
              </div>
              <div class="field">
                <label>التخصص</label>
                <input name="field_of_study[]" placeholder="علوم حاسب، هندسة..." value="<?= htmlspecialchars($edu['field_of_study'] ?? '') ?>">
              </div>
              <div class="field">
                <label>درجة المؤهل</label>
                <input name="degree[]" placeholder="دبلوم، بكالريوس" value="<?= htmlspecialchars($edu['degree'] ?? '') ?>">
              </div>
            </div>
            <div class="grid two">
              <div class="field">
                <label>تاريخ البدء</label>
                <input type="date" name="edu_start[]" value="<?= htmlspecialchars($edu['start_date'] ?? '') ?>">
              </div>
              <div class="field">
                <label>تاريخ الانتهاء</label>
                <input type="date" name="edu_end[]" value="<?= htmlspecialchars($edu['end_date'] ?? '') ?>">
              </div>
            </div>
            <div class="field">
              <label>الوصف / ملاحظات</label>
              <textarea name="edu_description[]" placeholder="تفاصيل إضافية عن الدراسة أو الإنجازات"><?= htmlspecialchars($edu['description'] ?? '') ?></textarea>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <button type="button" class="btn add" onclick="addEdu()">+ إضافة مؤهل</button>
      </div>

      <!-- ===== EXPERIENCE ===== -->
      <div class="section">
        <?php if(empty($experienceItems)): ?>
        <div class="item">
          <button type="button" class="remove-btn" onclick="removeItem(this)">
            <i class="fa-solid fa-trash"></i>
          </button>
          <div class="grid two">
            <div class="field">
              <label>المسمى الوظيفي</label>
              <input name="job_title_exp[]" placeholder="مثال: مطور نظم">
            </div>
            <div class="field">
              <label>الشركة</label>
              <input name="company[]" placeholder="اسم الشركة">
            </div>
          </div>
          <div class="grid three align-end">
            <div class="field">
              <label>تاريخ البدء</label>
              <input type="date" name="exp_start[]">
            </div>
            <div class="field end-date">
              <label>تاريخ الانتهاء</label>
              <input type="date" name="exp_end[]">
            </div>
            <div class="current-job">
              <label>
                <input type="checkbox" name="is_current[]" onchange="toggleEndDate(this)">
                <span>على رأس العمل</span>
              </label>
            </div>
          </div>
          <div class="field">
            <label>وصف المهام</label>
            <textarea name="exp_description[]" placeholder="اذكر مهامك ومسؤولياتك وإنجازاتك"></textarea>
          </div>
        </div>
        <?php else: ?>
          <?php foreach($experienceItems as $exp): ?>
          <div class="item">
            <button type="button" class="remove-btn" onclick="removeItem(this)">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="grid two">
              <div class="field">
                <label>المسمى الوظيفي</label>
                <input name="job_title_exp[]" placeholder="مثال: مطور نظم" value="<?= htmlspecialchars($exp['job_title'] ?? '') ?>">
              </div>
              <div class="field">
                <label>الشركة</label>
                <input name="company[]" placeholder="اسم الشركة" value="<?= htmlspecialchars($exp['company'] ?? '') ?>">
              </div>
            </div>
            <div class="grid three align-end">
              <div class="field">
                <label>تاريخ البدء</label>
                <input type="date" name="exp_start[]" value="<?= htmlspecialchars($exp['start_date'] ?? '') ?>">
              </div>
              <div class="field end-date <?= ($exp['is_current'] ?? 0) == 1 ? 'disabled' : '' ?>">
                <label>تاريخ الانتهاء</label>
                <input type="date" name="exp_end[]" value="<?= !($exp['is_current'] ?? 0) ? htmlspecialchars($exp['end_date'] ?? '') : '' ?>">
              </div>
              <div class="current-job">
                <label>
                  <input type="checkbox" name="is_current[]" onchange="toggleEndDate(this)" <?= ($exp['is_current'] ?? 0) == 1 ? 'checked' : '' ?>>
                  <span>على رأس العمل</span>
                </label>
              </div>
            </div>
            <div class="field">
              <label>وصف المهام</label>
              <textarea name="exp_description[]" placeholder="اذكر مهامك ومسؤولياتك وإنجازاتك"><?= htmlspecialchars($exp['description'] ?? '') ?></textarea>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <button type="button" class="btn add" onclick="addExp()">+ إضافة خبرة</button>
      </div>

      <!-- ===== COURSES ===== -->
      <div class="section">
        <?php if(empty($courseItems)): ?>
        <div class="item">
          <button type="button" class="remove-btn" onclick="removeItem(this)">
            <i class="fa-solid fa-trash"></i>
          </button>
          <div class="grid two">
            <div class="field">
              <label>اسم الدورة</label>
              <input name="course_name[]" placeholder="مثال: أساسيات الأمن السيبراني">
            </div>
            <div class="field">
              <label>الجهة المقدمة</label>
              <input name="provider[]" placeholder="منصة، جامعة، جهة تدريبية">
            </div>
          </div>
          <div class="field">
            <label>تاريخ إتمام الدورة</label>
            <input type="date" name="course_date[]">
          </div>
        </div>
        <?php else: ?>
          <?php foreach($courseItems as $course): ?>
          <div class="item">
            <button type="button" class="remove-btn" onclick="removeItem(this)">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="grid two">
              <div class="field">
                <label>اسم الدورة</label>
                <input name="course_name[]" placeholder="مثال: أساسيات الأمن السيبراني" value="<?= htmlspecialchars($course['course_name'] ?? '') ?>">
              </div>
              <div class="field">
                <label>الجهة المقدمة</label>
                <input name="provider[]" placeholder="منصة، جامعة، جهة تدريبية" value="<?= htmlspecialchars($course['provider'] ?? '') ?>">
              </div>
            </div>
            <div class="field">
              <label>تاريخ إتمام الدورة</label>
              <input type="date" name="course_date[]" value="<?= htmlspecialchars($course['completion_date'] ?? '') ?>">
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <button type="button" class="btn add" onclick="addCourse()">+ إضافة دورة</button>
      </div>

      <!-- ===== PROJECTS ===== -->
      <div class="section">
        <?php if(empty($projectItems)): ?>
        <div class="item">
          <button type="button" class="remove-btn" onclick="removeItem(this)">
            <i class="fa-solid fa-trash"></i>
          </button>
          <div class="grid two">
            <div class="field">
              <label>اسم المشروع</label>
              <input name="project_name[]" placeholder="مثال: نظام إدارة مهام">
            </div>
            <div class="field">
              <label>رابط المشروع</label>
              <input name="project_link[]" placeholder="https://github.com/username/project">
            </div>
          </div>
          <div class="field">
            <label>وصف المشروع</label>
            <textarea name="project_description[]" placeholder="اشرح فكرة المشروع، التقنيات المستخدمة، ودورك فيه"></textarea>
          </div>
        </div>
        <?php else: ?>
          <?php foreach($projectItems as $project): ?>
          <div class="item">
            <button type="button" class="remove-btn" onclick="removeItem(this)">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="grid two">
              <div class="field">
                <label>اسم المشروع</label>
                <input name="project_name[]" placeholder="مثال: نظام إدارة مهام" value="<?= htmlspecialchars($project['project_name'] ?? '') ?>">
              </div>
              <div class="field">
                <label>رابط المشروع</label>
                <input name="project_link[]" placeholder="https://github.com/username/project" value="<?= htmlspecialchars($project['project_link'] ?? '') ?>">
              </div>
            </div>
            <div class="field">
              <label>وصف المشروع</label>
              <textarea name="project_description[]" placeholder="اشرح فكرة المشروع، التقنيات المستخدمة، ودورك فيه"><?= htmlspecialchars($project['project_description'] ?? '') ?></textarea>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <button type="button" class="btn add" onclick="addProject()">+ إضافة مشروع</button>
      </div>

      <!-- ===== SKILLS ===== -->
      <div class="section">
        <?php if(empty($skillItems)): ?>
        <div class="item skill-item">
          <div class="skill-row">
            <input name="skill_name[]" placeholder="المهارة">
            <select name="skill_level[]" class="skill-level">
              <option value="Beginner">مبتدئ</option>
              <option value="Intermediate" selected>متوسط</option>
              <option value="Advanced">متقدم</option>
              <option value="Expert">خبير</option>
            </select>
            <button type="button" class="remove-btn" onclick="removeItem(this)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
        <?php else: ?>
          <?php foreach($skillItems as $skill): ?>
          <div class="item skill-item">
            <div class="skill-row">
              <input name="skill_name[]" placeholder="المهارة" value="<?= htmlspecialchars($skill['skill_name'] ?? '') ?>">
              <select name="skill_level[]" class="skill-level">
                <option value="Beginner" <?= ($skill['skill_level'] ?? '') == 'Beginner' ? 'selected' : '' ?>>مبتدئ</option>
                <option value="Intermediate" <?= ($skill['skill_level'] ?? '') == 'Intermediate' ? 'selected' : '' ?>>متوسط</option>
                <option value="Advanced" <?= ($skill['skill_level'] ?? '') == 'Advanced' ? 'selected' : '' ?>>متقدم</option>
                <option value="Expert" <?= ($skill['skill_level'] ?? '') == 'Expert' ? 'selected' : '' ?>>خبير</option>
              </select>
              <button type="button" class="remove-btn" onclick="removeItem(this)">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <button type="button" class="btn add" onclick="addSkill()">+ إضافة مهارة</button>
      </div>

      </div>
    </form>
  </div>
</div>

<!-- ================= JS ================= -->
<script>
function openTab(i){
  document.querySelectorAll('.tab').forEach((t,n)=>t.classList.toggle('active', n===i));
  document.querySelectorAll('.section').forEach((s,n)=>s.classList.toggle('active', n===i));
}

function toggleEndDate(checkbox){
  const item = checkbox.closest('.item');
  const endDateField = item.querySelector('.end-date');

  if(checkbox.checked){
    endDateField.classList.add('disabled');
    endDateField.querySelector('input').value = '';
  }else{
    endDateField.classList.remove('disabled');
  }
}

/* ===================== */
/*   HELPER FUNCTION     */
/* ===================== */

function cloneItem(sectionIndex){
  const section = document.querySelectorAll('.section')[sectionIndex];
  const firstItem = section.querySelector('.item');
  const newItem = firstItem.cloneNode(true);

  /* تفريغ القيم */
  newItem.querySelectorAll('input, textarea, select').forEach(el => {
    if(el.type === 'checkbox'){
      el.checked = false;
    }else if(el.tagName === 'SELECT'){
      // إعادة تحديد أول خيار
      if(el.options.length > 0){
        el.selectedIndex = 0;
      }
    }else{
      el.value = '';
    }
  });

  /* إزالة تعطيل تاريخ الانتهاء إن وجد */
  const endDate = newItem.querySelector('.end-date');
  if(endDate){
    endDate.classList.remove('disabled');
  }

  section.insertBefore(newItem, section.querySelector('.btn.add'));
}

/* ===================== */
/*   ADD FUNCTIONS       */
/* ===================== */

/* إضافة مؤهل */
function addEdu(){
  cloneItem(1);
}

/* إضافة خبرة */
function addExp(){
  cloneItem(2);
}

/* إضافة دورة */
function addCourse(){
  cloneItem(3);
}

/* إضافة مشروع */
function addProject(){
  cloneItem(4);
}

/* إضافة مهارة */
function addSkill(){
  cloneItem(5);
}

/* ===================== */
/*  REMOVE ITEM          */
/* ===================== */

function removeItem(button){
  const section = button.closest('.section');
  const item = button.closest('.item');

  const items = section.querySelectorAll('.item');

  // منع حذف آخر عنصر
  if(items.length <= 1){
    return;
  }

  item.remove();
}

/* تهيئة حقول تاريخ الانتهاء عند تحميل الصفحة */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.current-job input[type="checkbox"]').forEach(checkbox => {
    const item = checkbox.closest('.item');
    const endDateField = item.querySelector('.end-date');
    
    if(checkbox.checked && endDateField){
      endDateField.classList.add('disabled');
    }
  });
});
</script>

</body>
</html>