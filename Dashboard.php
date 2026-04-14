<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
require '../../RSM/Files/Database/Connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// جلب معلومات المستخدم
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userName = $stmt->fetchColumn() ?: 'مستخدم';

// جلب إحصائيات المستخدم
$stmt = $conn->prepare("SELECT COUNT(*) FROM cvs WHERE user_id = ?");
$stmt->execute([$userId]);
$totalCvs = $stmt->fetchColumn();

// جلب آخر 3 سير ذاتية
$stmt = $conn->prepare("SELECT id, name, created_at FROM cvs WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$userId]);
$recentCvs = $stmt->fetchAll();

include '../Files/assets/topbar.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - ResumeManager</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
    --gradient-light: linear-gradient(135deg, #8b9bb5, #6d7f99);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: var(--bg);
    font-family: 'Inter', 'Cairo', sans-serif;
    min-height: 100vh;
    padding: 0 20px 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-weight: 400;
    color: var(--text);
}

/* ===== محتوى الصفحة الرئيسية ===== */
.dashboard {
    max-width: 1400px;
    width: 100%;
}

/* ترحيب */
.welcome-section {
    background: var(--surface);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    color: var(--text);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
}

.welcome-section h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
    color: var(--text);
}

.welcome-section p {
    font-size: 16px;
    opacity: 0.8;
    color: var(--text-light);
}

/* البطاقات الإحصائية */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: var(--surface);
    border-radius: 20px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: var(--shadow);
    transition: transform 0.3s;
    border: 1px solid var(--border);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: var(--gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    opacity: 0.9;
}

.stat-info h3 {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 5px;
}

.stat-info .number {
    font-size: 32px;
    font-weight: 700;
    color: var(--text);
}

/* أزرار سريعة */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 40px;
}

.action-btn {
    background: var(--surface);
    border-radius: 15px;
    padding: 20px;
    text-decoration: none;
    color: var(--text);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.action-btn:hover {
    transform: translateY(-5px);
    background: var(--gradient);
    color: white;
    border-color: transparent;
}

.action-btn i {
    font-size: 30px;
    color: var(--primary);
}

.action-btn:hover i {
    color: white;
}

/* السير الذاتية الأخيرة */
.recent-section {
    background: var(--surface);
    border-radius: 20px;
    padding: 25px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 20px;
    color: var(--text);
}

.view-all {
    color: var(--primary);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.view-all:hover {
    color: var(--accent);
}

.view-all i {
    margin-right: 5px;
}

.cvs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.cv-card {
    border: 1px solid var(--border);
    border-radius: 15px;
    padding: 20px;
    transition: all 0.3s;
    background: var(--paper);
}

.cv-card:hover {
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
    transform: translateY(-2px);
}

.cv-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 10px;
}

.cv-name i {
    color: var(--primary);
    margin-left: 8px;
}

.cv-date {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 15px;
}

.cv-date i {
    margin-left: 5px;
    color: var(--muted);
}

.cv-actions {
    display: flex;
    gap: 10px;
}

.cv-action {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    font-size: 13px;
    transition: all 0.3s;
    font-weight: 500;
}

.cv-action.view {
    background: var(--primary);
    color: #1a1e24;
}

.cv-action.edit {
    background: var(--border);
    color: var(--text);
}

.cv-action:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    filter: brightness(1.1);
}

/* رسالة عند عدم وجود سير ذاتية */
.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--text-light);
}

.empty-state i {
    font-size: 50px;
    margin-bottom: 15px;
    color: var(--primary);
}

.empty-state p {
    margin-bottom: 15px;
}

.empty-state .btn {
    background: var(--gradient);
    color: white;
    padding: 12px 24px;
    border-radius: 40px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: var(--transition);
    border: none;
}

.empty-state .btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

/* تحسينات إضافية */
.stat-card, .action-btn, .recent-section, .cv-card {
    backdrop-filter: blur(5px);
}

/* تحسين الأيقونات */
.fa-solid, .fa-regular {
    font-size: 14px;
}

/* تحسين التدرجات */
.welcome-section {
    background: linear-gradient(135deg, var(--surface), var(--paper));
}

/* تحسين شريط التمرير */
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

@media(max-width:600px){
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
        position: static;
        transform: none;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr 1fr;
    }
    
    .welcome-section h1 {
        font-size: 24px;
    }
    
    .welcome-section {
        padding: 20px;
    }
}

/* تحسين ظهور النصوص */
h1, h2, h3, h4, h5, h6 {
    color: var(--text);
    font-weight: 600;
}

/* تحسين الروابط */
a {
    color: var(--primary);
    text-decoration: none;
    transition: var(--transition);
}

a:hover {
    color: var(--accent);
}

/* تحسين البطاقات في الوضع الداكن */
.stat-card, .action-btn, .recent-section, .cv-card {
    background: var(--surface);
}

/* تحسين الحدود */
hr {
    border: none;
    height: 1px;
    background: var(--border);
    margin: 20px 0;
}

/* تحسين النصوص الخفيفة */
.text-muted {
    color: var(--text-light);
}

/* تحسين الأزرار */
button, .btn {
    cursor: pointer;
    font-family: 'Inter', 'Cairo', sans-serif;
}

/* تحسين حقول الإدخال إن وجدت */
input, textarea, select {
    background: var(--paper);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 10px 15px;
    border-radius: 10px;
    font-family: 'Inter', 'Cairo', sans-serif;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(139, 155, 181, 0.1);
}

/* تحسين الجداول إن وجدت */
table {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
}

th {
    color: var(--text);
    background: var(--paper);
}

td {
    color: var(--text-light);
    border-bottom: 1px solid var(--border);
}

/* تحسين القوائم المنسدلة */
.dropdown {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
}

.dropdown-item {
    color: var(--text);
}

.dropdown-item:hover {
    background: var(--border);
}

/* تحسين الإشعارات */
.alert {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 15px;
    color: var(--text);
}

.alert-success {
    background: #1e3a2f;
    border-color: #2d4d3a;
    color: #b7e4c7;
}

.alert-error {
    background: #3a2626;
    border-color: #5a3838;
    color: #f8b4b4;
}

/* تحسين التحميل */
.loading {
    color: var(--primary);
}

/* تحسين البادج */
.badge {
    background: var(--border);
    color: var(--text);
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 12px;
}

.badge-primary {
    background: var(--primary);
    color: #1a1e24;
}
</style>
</head>
<body>

<!-- محتوى الصفحة الرئيسية -->
<div class="dashboard">
    <!-- قسم الترحيب -->
    <div class="welcome-section">
        <h1>مرحباً <?= htmlspecialchars($userName) ?> 👋</h1>
        <p>نحن سعداء بعودتك. يمكنك إدارة سيرتك الذاتية بكل سهولة من هنا</p>
    </div>

    <!-- البطاقات الإحصائية -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-info">
                <h3>إجمالي السير الذاتية</h3>
                <div class="number"><?= $totalCvs ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>آخر نشاط</h3>
                <div class="number">اليوم</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="stat-info">
                <h3>الإنجازات</h3>
                <div class="number">0</div>
            </div>
        </div>
    </div>

  

    <!-- السير الذاتية الأخيرة -->
    <div class="recent-section">
        <div class="section-header">
            <h2>آخر السير الذاتية</h2>
            <a href="Pages/select_resume.php" class="view-all">عرض الكل <i class="fas fa-arrow-left"></i></a>
        </div>

        <?php if (empty($recentCvs)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>لا توجد سير ذاتية بعد</p>
                <a href="Pages/new_resume.php" class="btn">
                    ابدأ بإنشاء سيرتك الأولى
                </a>
            </div>
        <?php else: ?>
            <div class="cvs-grid">
                <?php foreach ($recentCvs as $cv): ?>
                    <div class="cv-card">
                        <div class="cv-name">
                            <i class="fas fa-file-alt" style="color: #667eea; margin-left: 8px;"></i>
                            <?= htmlspecialchars($cv['name']) ?>
                        </div>
                        <div class="cv-date">
                            <i class="far fa-calendar-alt"></i> 
                            تم الإنشاء: <?= date('Y/m/d', strtotime($cv['created_at'])) ?>
                        </div>
                        <div class="cv-actions">
                            <a href="Pages/view_resume.php?cv_id=<?= $cv['id'] ?>" class="cv-action view">
                                <i class="fas fa-eye"></i> عرض
                            </a>
                            <a href="Pages/edit_resume.php?cv_id=<?= $cv['id'] ?>" class="cv-action edit">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>