<?php
session_start();
require '../../Files/Database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['cv_id'])) {
    header("Location: select_resume.php");
    exit;
}

$cvId   = (int)$_GET['cv_id'];
$userId = (int)$_SESSION['user_id'];

/* ===== Verify CV Ownership ===== */
$stmt = $conn->prepare("
    SELECT name 
    FROM cvs 
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$cvId, $userId]);
$cvTitle = $stmt->fetchColumn();

if (!$cvTitle) {
    die('سيرة غير صالحة');
}

/* ===== User ===== */
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userName = $stmt->fetchColumn() ?: 'مستخدم';

/* ===== Templates (static for now) ===== */
$templates = [
    [
        'id' => 'classic',
        'name' => 'الكلاسيكي',
        'desc' => 'تصميم رسمي مناسب للوظائف الحكومية والقطاع التقليدي',
        'icon' => 'fa-file-lines',
        'color' => '#8b9bb5',
        'popular' => true
    ],
    [
        'id' => 'modern',
        'name' => 'العصري',
        'desc' => 'تصميم حديث وأنيق للشركات التقنية والشركات الناشئة',
 'icon' => 'fa-file-lines',
        'color' => '#6d7f99',
        'popular' => false
    ],
    [
        'id' => 'minimal',
        'name' => 'البسيط',
        'desc' => 'تصميم بسيط ومركز بدون أي تشتيت، يركز على المحتوى',
    'icon' => 'fa-file-lines',
        'color' => '#7f8c8d',
        'popular' => false
    ]

];

include '../../Files/assets/topbar.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>اختيار القالب - <?= htmlspecialchars($cvTitle) ?></title>

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

/* ===== Header ===== */
.page-header {
    margin-bottom: 40px;
    text-align: center;
}

.page-title {
    font-size: 32px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 10px;
}

.page-title i {
    color: var(--primary);
    margin-left: 10px;
    font-size: 28px;
}

.cv-name-badge {
    display: inline-block;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 40px;
    padding: 8px 20px;
    margin-top: 10px;
}

.cv-name-badge i {
    color: var(--primary);
    margin-left: 8px;
}

.cv-name-badge span {
    color: var(--text-light);
    font-size: 16px;
}

.cv-name-badge strong {
    color: var(--text);
    font-weight: 600;
    margin-right: 5px;
}

.subtitle {
    color: var(--text-light);
    font-size: 16px;
    max-width: 600px;
    margin: 15px auto 0;
}

/* ===== Templates Grid ===== */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 30px 25px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
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

.card:hover {
    transform: translateY(-6px);
    border-color: var(--primary);
    box-shadow: var(--shadow-hover);
}

.card:hover::before {
    opacity: 1;
}

.popular-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: var(--primary);
    color: #1a1e24;
    padding: 5px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.popular-badge i {
    font-size: 12px;
}

.card-icon {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    background: var(--paper);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    transition: var(--transition);
}

.card:hover .card-icon {
    border-color: var(--primary);
    background: var(--surface);
}

.card-icon i {
    font-size: 32px;
    color: var(--primary);
    transition: var(--transition);
}

.card:hover .card-icon i {
    transform: scale(1.1);
}

.card h3 {
    font-size: 22px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 10px;
}

.card p {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 25px;
    flex-grow: 1;
}

.card-features {
    margin-bottom: 25px;
    padding-right: 0;
    list-style: none;
}

.card-features li {
    font-size: 13px;
    color: var(--muted);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-features li i {
    color: var(--primary);
    font-size: 12px;
}

.btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 40px;
    background: var(--gradient-light);
    color: #1a1e24;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    margin-top: auto;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
    filter: brightness(1.1);
}

.btn i {
    font-size: 16px;
}

/* ===== Back Link ===== */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 20px;
    transition: var(--transition);
}

.back-link:hover {
    color: var(--accent);
    transform: translateX(-3px);
}

.back-link i {
    font-size: 14px;
}

/* ===== Responsive ===== */
@media (max-width: 900px) {
    .topbar-center {
        margin-left: 0;
    }
    
    .topbar-center span {
        display: none;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 28px;
    }
    
    .grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .card {
        padding: 25px 20px;
    }
    
    .card-icon {
        width: 60px;
        height: 60px;
    }
    
    .card-icon i {
        font-size: 28px;
    }
    
    .card h3 {
        font-size: 20px;
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
    
    .page-title i {
        font-size: 22px;
    }
    
    .cv-name-badge {
        width: 100%;
        text-align: center;
    }
    
    .grid {
        grid-template-columns: 1fr;
    }
    
    .back-link {
        margin-bottom: 15px;
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

/* ===== Loading Animation ===== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease forwards;
    animation-fill-mode: both;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }

/* ===== Template Preview (يمكن إضافتها لاحقاً) ===== */
.template-preview {
    position: relative;
    margin-bottom: 20px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: var(--paper);
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.template-preview i {
    font-size: 48px;
    color: var(--muted);
    opacity: 0.5;
}

/* ===== Hover Effects ===== */
.card-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
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
</style>
</head>

<body>


<!-- ===== CONTENT ===== -->
<div class="content">
    
    <!-- رابط العودة -->
    <a href="select_resume.php" class="back-link">
        <i class="fa-solid fa-arrow-right"></i>
        العودة إلى قائمة السير الذاتية
    </a>
    
    <!-- رأس الصفحة -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa-regular fa-palette"></i>
            اختر قالباً لسيرتك الذاتية
        </h1>
        
        <div class="cv-name-badge">
            <i class="fa-regular fa-file-lines"></i>
            <span>السيرة:</span>
            <strong><?= htmlspecialchars($cvTitle) ?></strong>
        </div>
        
        <p class="subtitle">
            اختر التصميم المناسب لسيرتك الذاتية. يمكنك تغيير القالب لاحقاً في أي وقت.
        </p>
    </div>

    <!-- قائمة القوالب -->
    <div class="grid">
        <?php foreach($templates as $t): 
            // حدد الملف حسب القالب
            $file = "";
            if($t['id'] === 'classic') $file = "templates/classic.php";
            elseif($t['id'] === 'modern') $file = "templates/modern.php";
            elseif($t['id'] === 'minimal') $file = "templates/minimal.php";
       
        ?>
        <div class="card">
            <!-- شارة الأكثر شهرة -->
            <?php if(!empty($t['popular'])): ?>
                <div class="popular-badge">
                    <i class="fa-regular fa-star"></i>
                    الأكثر شهرة
                </div>
            <?php endif; ?>
            
            <!-- أيقونة القالب -->
            <div class="card-icon">
                <i class="fa-regular <?= $t['icon'] ?>"></i>
            </div>
            
            <!-- محتوى القالب -->
            <h3><?= $t['name'] ?></h3>
            <p><?= $t['desc'] ?></p>
            
            <!-- مميزات القالب (ثابتة للعرض) -->
            <ul class="card-features">
                <li><i class="fa-regular fa-circle-check"></i> تصميم متجاوب مع جميع الأجهزة</li>
                <li><i class="fa-regular fa-circle-check"></i> جاهز للطباعة بصيغة PDF</li>
                <li><i class="fa-regular fa-circle-check"></i> يدعم اللغة العربية بشكل كامل</li>
            </ul>
            
            <!-- زر الاختيار -->
            <a href="<?= $file ?>?cv_id=<?= $cvId ?>" class="btn">
                <i class="fa-regular fa-eye"></i>
                معاينة القالب
            </a>
            
            <!-- زر إضافي للمعاينة السريعة (اختياري) -->
            <!-- <button class="btn btn-outline" onclick="previewTemplate('<?= $t['id'] ?>')">
                <i class="fa-regular fa-eye"></i>
                معاينة سريعة
            </button> -->
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== Modal للمعاينة السريعة (اختياري - يمكن إضافته لاحقاً) ===== -->
<div id="previewModal" style="display: none;">
    <!-- محتوى المعاينة -->
</div>

</body>
</html>