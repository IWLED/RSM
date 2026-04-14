<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
require '../../Files/Database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userName = $stmt->fetchColumn() ?: 'مستخدم';

/* ===== Fetch CVs ===== */
$stmt = $conn->prepare("
    SELECT id, name, created_at 
    FROM cvs 
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$userId]);
$cvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../Files/assets/topbar.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>اختيار السيرة الذاتية</title>

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
}

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


/* ===== Content ===== */
.content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* ===== Page Header ===== */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-title {
    font-size: 28px;
    font-weight: 600;
    color: var(--text);
    position: relative;
    padding-right: 15px;
}

.page-title::before {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 70%;
    background: var(--primary);
    border-radius: 4px;
}

.page-title i {
    color: var(--primary);
    margin-left: 10px;
    font-size: 24px;
}

.create-new-btn {
    background: var(--surface);
    color: var(--text);
    padding: 12px 24px;
    border-radius: 40px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid var(--border);
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}

.create-new-btn:hover {
    background: var(--primary);
    color: #1a1e24;
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

.create-new-btn i {
    font-size: 16px;
}

/* ===== Stats Summary ===== */
.stats-summary {
    background: var(--surface);
    border-radius: 20px;
    padding: 20px 25px;
    margin-bottom: 30px;
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 30px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: var(--paper);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 20px;
    border: 1px solid var(--border);
}

.stat-info h4 {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 4px;
}

.stat-info .stat-number {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
}

/* ===== Cards Grid ===== */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.card {
    background: var(--surface);
    border-radius: 20px;
    padding: 25px;
    border: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient);
    opacity: 0;
    transition: var(--transition);
}

.card:hover {
    transform: translateY(-5px);
    border-color: var(--primary);
    box-shadow: var(--shadow-hover);
}

.card:hover::before {
    opacity: 1;
}

.card-header {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 15px;
}

.card-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: var(--paper);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 22px;
    border: 1px solid var(--border);
}

.card-title {
    flex: 1;
}

.card-title h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 5px;
    line-height: 1.4;
}

.card-title .date {
    color: var(--text-light);
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.card-title .date i {
    color: var(--muted);
    font-size: 12px;
}

.card-footer {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 40px;
    background: var(--gradient-light);
    color: #1a1e24;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    filter: brightness(1.1);
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

.btn i {
    font-size: 14px;
}

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: var(--surface);
    border-radius: 30px;
    border: 1px solid var(--border);
    margin-top: 20px;
}

.empty-state i {
    font-size: 80px;
    color: var(--primary);
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 24px;
    color: var(--text);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--text-light);
    margin-bottom: 30px;
    font-size: 16px;
}

.empty-state .btn {
    display: inline-flex;
    width: auto;
    padding: 14px 40px;
    background: var(--gradient-light);
    color: #1a1e24;
    text-decoration: none;
}

/* ===== Search/Filter Bar (اختياري للتحسين المستقبلي) ===== */
.search-bar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 50px;
    padding: 4px;
    display: flex;
    align-items: center;
    margin-bottom: 25px;
}

.search-bar input {
    flex: 1;
    background: transparent;
    border: none;
    padding: 12px 20px;
    color: var(--text);
    font-size: 14px;
}

.search-bar input::placeholder {
    color: var(--text-light);
}

.search-bar button {
    background: var(--primary);
    border: none;
    border-radius: 40px;
    padding: 10px 25px;
    color: #1a1e24;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-bar button:hover {
    filter: brightness(1.1);
    transform: translateX(-2px);
}

/* ===== Responsive ===== */
@media (max-width: 900px) {
    .topbar-center span {
        display: none;
    }
    
    .topbar-center {
        margin-left: 0;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .create-new-btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-summary {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .stat-item {
        width: 100%;
    }
    
    .grid {
        grid-template-columns: 1fr;
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
    
    .content {
        padding: 20px 15px;
    }
    
    .page-title {
        font-size: 24px;
    }
    
    .card {
        padding: 20px;
    }
    
    .empty-state {
        padding: 60px 15px;
    }
    
    .empty-state i {
        font-size: 60px;
    }
    
    .empty-state h3 {
        font-size: 20px;
    }
}

/* ===== Scrollbar ===== */
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

/* ===== Loading Animation (اختياري) ===== */
@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

.loading {
    animation: pulse 1.5s ease-in-out infinite;
}

/* ===== Tooltips ===== */
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
}

[data-tooltip]:hover:before {
    opacity: 1;
}
</style>
</head>

<body>

<!-- ===== CONTENT ===== -->
<div class="content">
    
    <!-- رأس الصفحة مع إحصائيات سريعة -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa-solid fa-folder-open"></i>
            السير الذاتية
        </h1>
        
        <a href="new_resume.php" class="create-new-btn">
            <i class="fa-solid fa-plus"></i>
            إنشاء سيرة جديدة
        </a>
    </div>
    
    <!-- ملخص سريع -->
    <div class="stats-summary">
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div class="stat-info">
                <h4>إجمالي السير</h4>
                <span class="stat-number"><?= count($cvs) ?></span>
            </div>
        </div>
        
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-info">
                <h4>آخر تحديث</h4>
                <span class="stat-number">
                    <?= !empty($cvs) ? date('Y-m-d', strtotime($cvs[0]['created_at'])) : 'لا يوجد' ?>
                </span>
            </div>
        </div>
    </div>

    <?php if(empty($cvs)): ?>
        <!-- حالة عدم وجود سير ذاتية -->
        <div class="empty-state">
            <i class="fa-regular fa-file-lines"></i>
            <h3>لا توجد سير ذاتية</h3>
            <p>لم تقم بإنشاء أي سيرة ذاتية بعد. ابدأ الآن في إنشاء سيرتك الأولى</p>
            <a href="new_resume.php" class="btn">
                <i class="fa-solid fa-plus"></i>
                إنشاء سيرة جديدة
            </a>
        </div>
    <?php else: ?>
        <!-- عرض السير الذاتية -->
        <div class="grid">
            <?php foreach($cvs as $index => $cv): ?>
                <div class="card" <?= $index === 0 ? 'data-tooltip="آخر تحديث"' : '' ?>>
                    <div class="card-header">
                        <div class="card-icon">
                            <i class="fa-regular fa-file-pdf"></i>
                        </div>
                        <div class="card-title">
                            <h3><?= htmlspecialchars($cv['name']) ?></h3>
                            <div class="date">
                                <i class="fa-regular fa-calendar"></i>
                                <?= date('Y/m/d', strtotime($cv['created_at'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="select_template.php?cv_id=<?= $cv['id'] ?>" class="btn">
                            <i class="fa-regular fa-eye"></i>
                            عرض
                        </a>
                        <!-- تم تغيير الرابط هنا ليتوافق مع صفحة التعديل الجديدة -->
                        <a href="edit_resume.php?cv_id=<?= $cv['id'] ?>" class="btn btn-outline">
                            <i class="fa-regular fa-pen-to-square"></i>
                            تعديل
                        </a>
                    </div>
                    
                    <!-- أيقونة اخرى للتمييز -->
                    <?php if($index === 0): ?>
                        <span style="position: absolute; top: 10px; left: 10px; background: var(--primary); color: #1a1e24; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                            <i class="fa-regular fa-clock"></i> أحدث
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>