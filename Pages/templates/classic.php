<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
require '../../../../RSM/Files/Database/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userName = $stmt->fetchColumn() ?: 'مستخدم';

$cv_id = isset($_GET['cv_id']) ? (int)$_GET['cv_id'] : 0;

// التحقق من صلاحية الوصول للسيرة الذاتية
$stmt = $conn->prepare("SELECT * FROM cvs WHERE id = ? AND user_id = ?");
$stmt->execute([$cv_id, $userId]);
$cv = $stmt->fetch();
if (!$cv) die("غير مصرح بالوصول");

// جلب جميع البيانات
$stmt = $conn->prepare("SELECT * FROM personal_info WHERE cv_id = ?");
$stmt->execute([$cv_id]);
$personal = $stmt->fetch() ?: [];

$stmt = $conn->prepare("SELECT * FROM education WHERE cv_id = ? ORDER BY end_date DESC");
$stmt->execute([$cv_id]);
$education = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM experience WHERE cv_id = ? ORDER BY start_date DESC");
$stmt->execute([$cv_id]);
$experiences = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM projects WHERE cv_id = ?");
$stmt->execute([$cv_id]);
$projects = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM courses WHERE cv_id = ?");
$stmt->execute([$cv_id]);
$courses = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT * FROM skills WHERE cv_id = ?");
$stmt->execute([$cv_id]);
$skills = $stmt->fetchAll();

// دوال مساعدة
function formatDateRange($start, $end, $current = false) {
    if (!$start && !$end) return '';
    if ($start && $end) {
        $startDate = date('Y/m', strtotime($start));
        $endDate = date('Y/m', strtotime($end));
        return "$startDate – $endDate";
    }
    if ($start && !$end) {
        $startDate = date('Y/m', strtotime($start));
        return $current ? "$startDate – الحاضر" : $startDate;
    }
    return $end;
}

function formatDate($date) {
    if (!$date) return '';
    return date('Y/m', strtotime($date));
}

include '../../../Files/assets/topbar.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cv['name']) ?> - السيرة الذاتية</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
         background: #2f343a;
            font-family: 'Inter', 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 20px 30px 20px;
        }

      

        /* ===== شريط التحكم الرئيسي ===== */
        .action-bar {
            max-width: 210mm;
            width: 100%;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            background: white;
            padding: 12px 20px;
            border-radius: 50px;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }

        .cv-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cv-info-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2c3e50, #4a5a6e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .cv-info-text {
            display: flex;
            flex-direction: column;
        }

        .cv-info-label {
            font-size: 12px;
            color: #8a9cb0;
        }

        .cv-info-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2c3e50, #4a5a6e);
            color: white;
            box-shadow: 0 4px 10px rgba(44, 62, 80, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(44, 62, 80, 0.3);
        }

        .btn-outline {
            background: white;
            color: #2c3e50;
            border: 1px solid #edf2f7;
        }

        .btn-outline:hover {
            background: #f8fafd;
            border-color: #2c3e50;
            transform: translateY(-2px);
        }

        .btn-print {
            background: white;
            color: #2c3e50;
            border: 1px solid #edf2f7;
            padding: 10px 25px;
        }

        .btn-print:hover {
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
            transform: translateY(-2px);
        }

        .btn-print i {
            font-size: 16px;
        }

        /* ===== حاوية السيرة الذاتية ===== */
        .resume-container {
            max-width: 210mm;
            width: 100%;
            margin: 0 auto;
            position: relative;
        }

        .resume {
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
        }

        /* محتوى السيرة */
        .resume-content {
            padding: 12mm 15mm;
        }

        /* الهيدر */
        .header {
            margin-bottom: 15px;
        }

        .name {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .job-title {
            font-size: 16px;
            color: #5d6f82;
            font-weight: 500;
            margin-bottom: 10px;
        }

        /* معلومات شخصية إضافية */
        .personal-details {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #5d6f82;
        }

        .detail-badge {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-badge i {
            color: #2c3e50;
            width: 16px;
        }

        /* شبكة معلومات الاتصال */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin: 15px 0;
            padding: 12px 0;
            border-top: 1px solid #eef2f6;
            border-bottom: 1px solid #eef2f6;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2c3e50;
            font-size: 12px;
        }

        .info-item i {
            width: 16px;
            color: #4a5a6e;
        }

        .info-item a {
            color: #2c3e50;
            text-decoration: none;
            transition: color 0.2s;
        }

        .info-item a:hover {
            color: #4a5a6e;
        }

        /* نبذة مختصرة */
        .summary {
            background: #f8fafd;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-right: 3px solid #2c3e50;
            border-radius: 0 12px 12px 0;
            font-size: 13px;
            line-height: 1.6;
            color: #2c3e50;
        }

        /* عناوين الأقسام */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin: 20px 0 15px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #eef2f6;
            padding-bottom: 5px;
        }

        .section-title i {
            color: #4a5a6e;
        }

        /* بطاقات التعليم والخبرة */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 10px;
        }

        .card {
            border: 1px solid #eef2f6;
            border-radius: 12px;
            padding: 15px;
            background: white;
            transition: all 0.2s;
        }

        .card:hover {
            border-color: #b8c5d0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .card-subtitle {
            font-size: 14px;
            color: #5d6f82;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .card-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 12px;
            color: #7f8fa0;
        }

        .card-meta i {
            margin-left: 4px;
            color: #4a5a6e;
        }

        .card-description {
            font-size: 12px;
            line-height: 1.5;
            color: #5d6f82;
            border-top: 1px dashed #eef2f6;
            padding-top: 10px;
            margin-top: 10px;
        }

        /* شبكة المشاريع */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .project-card {
            border: 1px solid #eef2f6;
            border-radius: 12px;
            padding: 15px;
            background: white;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .project-name {
            font-size: 15px;
            font-weight: 700;
            color: #2c3e50;
        }

        .project-qr {
            width: 40px;
            height: 40px;
            background: #f8fafd;
            border-radius: 8px;
            overflow: hidden;
        }

        .project-description {
            font-size: 12px;
            line-height: 1.5;
            color: #5d6f82;
            margin-bottom: 10px;
        }

        .project-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #2c3e50;
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            padding: 4px 10px;
            background: #f8fafd;
            border-radius: 20px;
            transition: all 0.2s;
        }

        .project-link:hover {
            background: #2c3e50;
            color: white;
        }

        /* قائمة الدورات */
        .courses-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 15px;
        }

        .course-item {
            border: 1px solid #eef2f6;
            border-radius: 10px;
            padding: 12px;
            background: white;
        }

        .course-name {
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .course-provider {
            font-size: 11px;
            color: #5d6f82;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .course-date {
            font-size: 10px;
            color: #7f8fa0;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* المهارات */
        .skills-container {
            border: 1px solid #eef2f6;
            border-radius: 12px;
            padding: 15px;
            background: #f8fafd;
            margin-top: 10px;
        }

        .skills-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .skill-item {
            background: white;
            border: 1px solid #eef2f6;
            border-radius: 30px;
            padding: 6px 15px;
            font-size: 12px;
            color: #2c3e50;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .skill-item:hover {
            border-color: #2c3e50;
            background: #f8fafd;
        }

        .skill-level {
            background: #eef2f6;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            color: #5d6f82;
        }

        /* تحسينات للطباعة */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .topbar, .action-bar {
                display: none !important;
            }
            
            .resume {
                box-shadow: none;
                border-radius: 0;
            }
            
            .resume-content {
                padding: 10mm 12mm;
            }
            
            .card, .project-card, .course-item {
                break-inside: avoid;
                border: 1px solid #ddd;
            }
            
            .no-break {
                break-inside: avoid;
            }
        }

        /* للشاشات الصغيرة */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .cards-grid, .projects-grid, .courses-list {
                grid-template-columns: 1fr;
            }
            
            .action-bar {
                flex-direction: column;
                align-items: stretch;
                border-radius: 20px;
            }
            
            .action-buttons {
                justify-content: stretch;
            }
            
            .action-btn {
                flex: 1;
                justify-content: center;
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
        }
    </style>
</head>
<body>
   
    <!-- شريط التحكم الرئيسي مع زر الطباعة -->
    <div class="action-bar">
        <div class="cv-info">
            <div class="cv-info-icon">
                <i class="fa-regular fa-file-lines"></i>
            </div>
            <div class="cv-info-text">
                <span class="cv-info-label">السيرة الذاتية</span>
                <span class="cv-info-name"><?= htmlspecialchars($cv['name']) ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="edit_resume.php?cv_id=<?= $cv_id ?>" class="action-btn btn-outline">
                <i class="fa-regular fa-pen-to-square"></i>
                تعديل
            </a>
            <a href="../select_template.php?cv_id=<?= $cv_id ?>" class="action-btn btn-outline">
                <i class="fa-regular fa-palette"></i>
                تغيير القالب
            </a>
            <button onclick="window.print()" class="action-btn btn-print">
                <i class="fas fa-print"></i>
                طباعة
            </button>
        </div>
    </div>

    <!-- حاوية السيرة الذاتية -->
    <div class="resume-container">
        <div class="resume">
            <div class="resume-content">
                <!-- رأس الصفحة -->
                <div class="header">
                    <h1 class="name"><?= htmlspecialchars($personal['full_name'] ?? $cv['name']) ?></h1>
                    
                    <?php if (!empty($personal['job_title'])): ?>
                        <div class="job-title"><?= htmlspecialchars($personal['job_title']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- معلومات شخصية إضافية مدمجة -->
                <?php if (!empty($personal['date_of_birth']) || !empty($personal['nationality'])): ?>
                    <div class="personal-details">
                        <?php if (!empty($personal['date_of_birth'])): ?>
                            <div class="detail-badge">
                                <i class="fas fa-birthday-cake"></i>
                                <span><?= htmlspecialchars($personal['date_of_birth']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($personal['nationality'])): ?>
                            <div class="detail-badge">
                                <i class="fas fa-flag"></i>
                                <span><?= htmlspecialchars($personal['nationality']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- معلومات الاتصال المدمجة -->
                <div class="info-grid">
                    <?php if (!empty($personal['email'])): ?>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($personal['email']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($personal['phone'])): ?>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <span><?= htmlspecialchars($personal['phone']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($personal['address'])): ?>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($personal['address']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($personal['linkedin'])): ?>
                        <div class="info-item">
                            <i class="fab fa-linkedin"></i>
                            <a href="<?= htmlspecialchars($personal['linkedin']) ?>" target="_blank">LinkedIn</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($personal['github'])): ?>
                        <div class="info-item">
                            <i class="fab fa-github"></i>
                            <a href="<?= htmlspecialchars($personal['github']) ?>" target="_blank">GitHub</a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($personal['website'])): ?>
                        <div class="info-item">
                            <i class="fas fa-globe"></i>
                            <a href="<?= htmlspecialchars($personal['website']) ?>" target="_blank">الموقع</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- نبذة مختصرة -->
                <?php if (!empty($personal['summary'])): ?>
                    <div class="summary">
                        <?= nl2br(htmlspecialchars($personal['summary'])) ?>
                    </div>
                <?php endif; ?>

                <!-- التعليم -->
                <?php if (!empty($education)): ?>
                    <div class="section-title">
                        <i class="fas fa-graduation-cap"></i>
                        <span>التعليم</span>
                    </div>
                    <div class="cards-grid">
                        <?php foreach ($education as $edu): ?>
                            <div class="card">
                                <div class="card-title"><?= htmlspecialchars($edu['degree'] ?? '') ?></div>
                                <div class="card-subtitle"><?= htmlspecialchars($edu['institution'] ?? '') ?></div>
                                
                                <?php if (!empty($edu['field_of_study'])): ?>
                                    <div class="card-meta">
                                        <i class="fas fa-book"></i>
                                        <?= htmlspecialchars($edu['field_of_study']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($edu['start_date'] || $edu['end_date']): ?>
                                    <div class="card-meta">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= formatDateRange($edu['start_date'], $edu['end_date']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($edu['description'])): ?>
                                    <div class="card-description">
                                        <?= nl2br(htmlspecialchars($edu['description'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- الخبرات -->
                <?php if (!empty($experiences)): ?>
                    <div class="section-title">
                        <i class="fas fa-briefcase"></i>
                        <span>الخبرات</span>
                    </div>
                    <div class="cards-grid">
                        <?php foreach ($experiences as $exp): ?>
                            <div class="card">
                                <div class="card-title"><?= htmlspecialchars($exp['job_title'] ?? '') ?></div>
                                <div class="card-subtitle"><?= htmlspecialchars($exp['company'] ?? '') ?></div>
                                
                                <div class="card-meta">
                                    <i class="far fa-calendar-alt"></i>
                                    <?= formatDateRange($exp['start_date'], $exp['end_date'], $exp['is_current'] ?? false) ?>
                                </div>

                                <?php if (!empty($exp['description'])): ?>
                                    <div class="card-description">
                                        <?= nl2br(htmlspecialchars($exp['description'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- المشاريع -->
                <?php if (!empty($projects)): ?>
                    <div class="section-title">
                        <i class="fas fa-diagram-project"></i>
                        <span>المشاريع</span>
                    </div>
                    <div class="projects-grid">
                        <?php foreach ($projects as $index => $project): ?>
                            <div class="project-card">
                                <div class="project-header">
                                    <span class="project-name"><?= htmlspecialchars($project['project_name'] ?? '') ?></span>
                                    
                                    <?php if (!empty($project['project_link'])): ?>
                                        <div id="qr-<?= $index ?>" class="project-qr"></div>
                                        <script>
                                            new QRCode(document.getElementById("qr-<?= $index ?>"), {
                                                text: "<?= htmlspecialchars($project['project_link']) ?>",
                                                width: 40,
                                                height: 40,
                                                colorDark: "#2c3e50",
                                                colorLight: "#ffffff",
                                                correctLevel: QRCode.CorrectLevel.H
                                            });
                                        </script>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($project['project_description'])): ?>
                                    <div class="project-description">
                                        <?= nl2br(htmlspecialchars($project['project_description'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($project['project_link'])): ?>
                                    <a href="<?= htmlspecialchars($project['project_link']) ?>" target="_blank" class="project-link">
                                        <i class="fas fa-external-link-alt"></i>
                                        زيارة المشروع
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- الدورات -->
                <?php if (!empty($courses)): ?>
                    <div class="section-title">
                        <i class="fas fa-certificate"></i>
                        <span>الدورات</span>
                    </div>
                    <div class="courses-list">
                        <?php foreach ($courses as $course): ?>
                            <div class="course-item">
                                <div class="course-name"><?= htmlspecialchars($course['course_name'] ?? '') ?></div>
                                <?php if (!empty($course['provider'])): ?>
                                    <div class="course-provider">
                                        <i class="fas fa-building"></i>
                                        <?= htmlspecialchars($course['provider']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($course['completion_date'])): ?>
                                    <div class="course-date">
                                        <i class="far fa-calendar-check"></i>
                                        <?= formatDate($course['completion_date']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- المهارات -->
                <?php if (!empty($skills)): ?>
                    <div class="section-title">
                        <i class="fas fa-code"></i>
                        <span>المهارات</span>
                    </div>
                    <div class="skills-container">
                        <div class="skills-grid">
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill-item">
                                    <?= htmlspecialchars($skill['skill_name'] ?? '') ?>
                                    <?php if (!empty($skill['skill_level'])): ?>
                                        <span class="skill-level"><?= htmlspecialchars($skill['skill_level']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>