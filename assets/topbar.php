<?php
/**
 * ملف الشريط العلوي المستقل (topbar.php)
 * يستخدم مسارات مطلعة بناءً على جذر المشروع
 */

// تحديد الصفحة الحالية
$topbarCurrentPage = basename($_SERVER['PHP_SELF']);

// جلب اسم المستخدم من الجلسة
$topbarUserName = isset($_SESSION['user_id']) && isset($userName) ? $userName : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'مستخدم');
$topbarInitial = !empty($topbarUserName) && $topbarUserName !== 'مستخدم' ? mb_substr($topbarUserName, 0, 1) : 'U';

// ===== الحل السحري: تحديد المسار الأساسي الثابت =====
// نحن نعرف أن المشروع كله تحت مجلد Files
// لذا سنستخدم مسار مطلق يبدأ من /Files/

// أولاً: نحدد إذا كنا في سيرفر محلي أو على استضافة
$topbarBaseUrl = '';

// التحقق من البروتوكول (http أو https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

// الحصول على اسم المضيف (localhost أو domain.com)
$host = $_SERVER['HTTP_HOST'];

// بناء المسار الأساسي الكامل
// مثال: http://localhost/Files/
$topbarBaseUrl = $protocol . $host . '/Files/';

// للاستخدام داخل السيرفر (بدون الرابط الكامل)
$topbarServerPath = '/Files/';
?>

<!-- ===== بداية الشريط العلوي المستقل ===== -->
<style>
/* متغيرات الشريط العلوي */
.topbar-navigation * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.topbar-navigation {
    --topbar-bg: #242830;
    --topbar-surface: #2a2e36;
    --topbar-primary: #8b9bb5;
    --topbar-accent: #6d7f99;
    --topbar-text: #e8ecf2;
    --topbar-text-light: #b8c2d0;
    --topbar-border: #363c47;
    --topbar-radius: 16px;
    --topbar-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --topbar-shadow: 0 8px 20px rgba(0,0,0,0.3);
    
    max-width: 1400px;
    width: 100%;
    height: 80px;
    background: var(--topbar-bg);
    border-radius: var(--topbar-radius);
    box-shadow: var(--topbar-shadow);
    margin: 20px auto 30px auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 25px;
    border: 1px solid var(--topbar-border);
    backdrop-filter: blur(10px);
    font-family: 'Inter', 'Cairo', sans-serif;
    position: relative;
    z-index: 1000;
}

/* ===== القسم الأيمن ===== */
.topbar-right-section {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* بطاقة المستخدم */
.topbar-user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 16px 6px 8px;
    border-radius: 50px;
    background: var(--topbar-surface);
    border: 1px solid var(--topbar-border);
    transition: var(--topbar-transition);
    cursor: default;
}

.topbar-user-profile:hover {
    border-color: var(--topbar-primary);
    background: var(--topbar-bg);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.topbar-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2c3e50, #1e2a36);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    border: 2px solid var(--topbar-primary);
    transition: var(--topbar-transition);
}

.topbar-user-profile:hover .topbar-avatar {
    border-color: var(--topbar-accent);
    transform: scale(1.05);
}

.topbar-display-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--topbar-text);
}

/* أزرار الأيقونات */
.topbar-action-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--topbar-surface);
    color: var(--topbar-text-light);
    text-decoration: none;
    transition: var(--topbar-transition);
    border: 1px solid var(--topbar-border);
    font-size: 18px;
    position: relative;
    overflow: hidden;
}

.topbar-action-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(139, 155, 181, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.3s, height 0.3s;
}

.topbar-action-btn:hover::before {
    width: 100%;
    height: 100%;
}

.topbar-action-btn:hover {
    background: var(--topbar-primary);
    color: #1a1e24;
    border-color: var(--topbar-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.topbar-action-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

/* ===== القسم الأوسط (أزرار التنقل) ===== */
.topbar-nav-center {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--topbar-surface);
    padding: 6px;
    border-radius: 60px;
    border: 1px solid var(--topbar-border);
}

.topbar-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    border-radius: 40px;
    color: var(--topbar-text-light);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: var(--topbar-transition);
    background: transparent;
    position: relative;
    overflow: hidden;
}

.topbar-nav-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(139, 155, 181, 0.1);
    transform: scaleX(0);
    transform-origin: right;
    transition: transform 0.3s ease;
    border-radius: 40px;
    z-index: -1;
}

.topbar-nav-item:hover::before {
    transform: scaleX(1);
    transform-origin: left;
}

.topbar-nav-item:hover {
    color: var(--topbar-text);
    border: 1px solid var(--topbar-border);
    transform: translateY(-1px);
}

.topbar-nav-item.active {
    background: linear-gradient(135deg, #2c3e50, #1e2a36);
    color: white;
    border: 1px solid var(--topbar-border);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.topbar-nav-item.active i {
    color: white;
}

.topbar-nav-item i {
    font-size: 16px;
    color: var(--topbar-primary);
    transition: var(--topbar-transition);
}

.topbar-nav-item.active i {
    color: white;
    transform: scale(1.1);
}

/* ===== القسم الأيسر (الشعار) ===== */
.topbar-brand {
    font-weight: 700;
    font-size: 24px;
    background: linear-gradient(135deg, #8b9bb5, #6d7f99);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 1px;
    position: relative;
    padding: 5px 10px;
}

.topbar-brand::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 10px;
    right: 10px;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--topbar-primary), transparent);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.topbar-brand:hover::after {
    transform: scaleX(1);
}

/* ===== تأثيرات إضافية ===== */
.topbar-nav-item,
.topbar-action-btn,
.topbar-user-profile {
    cursor: pointer;
}

/* تلميحات الأدوات */
.topbar-action-btn[title] {
    position: relative;
}

.topbar-action-btn[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--topbar-surface);
    color: var(--topbar-text);
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    border: 1px solid var(--topbar-border);
    box-shadow: var(--topbar-shadow);
    z-index: 1001;
}

/* ===== التجاوب مع الشاشات ===== */
@media(max-width:900px){
    .topbar-nav-item span {
        display: none;
    }
    
    .topbar-nav-item {
        padding: 10px 15px;
    }
    
    .topbar-nav-item i {
        font-size: 18px;
    }
}

@media(max-width:700px){
    .topbar-navigation {
        flex-wrap: wrap;
        height: auto;
        gap: 15px;
        padding: 15px;
    }
    
    .topbar-nav-center {
        order: 3;
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
        background: transparent;
        border: none;
        padding: 0;
    }
    
    .topbar-nav-item {
        background: var(--topbar-surface);
        border: 1px solid var(--topbar-border);
    }
    
    .topbar-display-name {
        display: none;
    }
    
    .topbar-user-profile {
        padding: 6px;
    }
}

@media(max-width:480px){
    .topbar-brand {
        font-size: 20px;
    }
    
    .topbar-action-btn {
        width: 38px;
        height: 38px;
        font-size: 16px;
    }
    
    .topbar-avatar {
        width: 34px;
        height: 34px;
        font-size: 14px;
    }
}
</style>

<!-- هيكل الشريط العلوي -->
<div class="topbar-navigation">
    <!-- القسم الأيمن -->
    <div class="topbar-right-section">
        <div class="topbar-user-profile" title="ملف المستخدم">
            <div class="topbar-avatar"><?= $topbarInitial ?></div>
            <span class="topbar-display-name"><?= htmlspecialchars($topbarUserName) ?></span>
        </div>

        <a href="<?= $topbarServerPath ?>logout.php" class="topbar-action-btn" title="تسجيل الخروج">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>

        <a href="<?= $topbarServerPath ?>Pages/profile.php" class="topbar-action-btn" title="الإعدادات">
            <i class="fa-solid fa-gear"></i>
        </a>
    </div>

    <!-- القسم الأوسط -->
    <div class="topbar-nav-center">
        <a href="<?= $topbarServerPath ?>Dashboard.php" 
           class="topbar-nav-item <?= $topbarCurrentPage == 'Dashboard.php' ? 'active' : '' ?>" 
           title="الصفحة الرئيسية">
            <i class="fa-solid fa-house"></i>
            <span>الرئيسية</span>
        </a>

        <a href="<?= $topbarServerPath ?>Pages/new_resume.php" 
           class="topbar-nav-item <?= $topbarCurrentPage == 'new_resume.php' ? 'active' : '' ?>"
           title="إنشاء سيرة ذاتية جديدة">
            <i class="fa-solid fa-plus"></i>
            <span>سيرة جديدة</span>
        </a>
        
        <a href="<?= $topbarServerPath ?>Pages/select_resume.php" 
           class="topbar-nav-item <?= $topbarCurrentPage == 'select_resume.php' ? 'active' : '' ?>"
           title="عرض وتعديل السير الذاتية">
            <i class="fa-solid fa-folder-open"></i>
            <span>عرض وتعديل</span>
        </a>

    </div>

    <!-- القسم الأيسر -->
    <div class="topbar-brand" title="Resume Manager">
        RSM
    </div>
</div>