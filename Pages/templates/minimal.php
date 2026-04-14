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
    $startDate = $start ? date('Y/m', strtotime($start)) : '';
    $endDate = $end ? date('Y/m', strtotime($end)) : '';
    
    if ($start && $end) return "$startDate – $endDate";
    if ($start && !$end) return $current ? "$startDate – الآن" : $startDate;
    return $endDate;
}

function formatDate($date) {
    return $date ? date('Y/m', strtotime($date)) : '';
}

// تجميع البيانات في صفوف مكونة من 4 أعمدة للدورات
$coursesChunks = array_chunk($courses, 4);
$groupedCourses = [];
foreach ($coursesChunks as $chunk) {
    $groupedCourses[] = $chunk;
}

// تجميع البيانات في صفوف مكونة من 4 أعمدة للمهارات
$skillsChunks = array_chunk($skills, 4);
$groupedSkills = [];
foreach ($skillsChunks as $chunk) {
    $groupedSkills[] = $chunk;
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
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }


        /* زر الطباعة */
        .print-wrapper {
            max-width: 210mm;
            width: 100%;
            margin-bottom: 15px;
            text-align: left;
        }

        .print-btn {
            background: white;
            border: 1px solid #d0d9e3;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            color: #2c3e50;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .print-btn:hover {
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }

        /* السيرة الذاتية */
        .resume {
            max-width: 210mm;
            width: 100%;
            background: white;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        /* المحتوى */
        .resume-inner {
            padding: 20px 25px;
        }

        /* الهيدر */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f4f9;
        }

        .name-title h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a2634;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .name-title .job {
            font-size: 18px;
            font-weight: 400;
            color: #5b6f82;
        }

        .quick-info {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #5b6f82;
        }

        .quick-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .quick-info i {
            color: #8a9bb0;
            font-size: 15px;
        }

        /* معلومات الاتصال - سطر واحد */
        .contact-bar {
            background: #f8fafd;
            padding: 15px 25px;
            border-radius: 50px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            font-size: 14px;
            border: 1px solid #edf2f7;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2c3e50;
        }

        .contact-item i {
            color: #5b6f82;
            width: 18px;
            font-size: 15px;
        }

        .contact-item a {
            color: #2c3e50;
            text-decoration: none;
        }

        .contact-item a:hover {
            color: #000;
        }

        /* نبذة */
        .summary {
            background: #f8fafd;
            padding: 18px 22px;
            border-radius: 16px;
            margin-bottom: 25px;
            font-size: 14px;
            line-height: 1.7;
            color: #2c3e50;
            border: 1px solid #edf2f7;
        }

        /* عناوين الأقسام */
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0 15px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1a2634;
            letter-spacing: 0.5px;
        }

        .section-title i {
            color: #5b6f82;
            font-size: 20px;
        }

        .section-title span {
            border-bottom: 2px solid #e1e9f2;
            padding-bottom: 5px;
        }

        /* قسم المؤهلات - تصميم محسن */
        .qualifications-section {
            background: #f8fafd;
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid #edf2f7;
        }

        .qualification-item {
            padding: 15px;
            border-bottom: 1px dashed #d0d9e3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .qualification-item:last-child {
            border-bottom: none;
        }

        .qualification-content {
            display: flex;
            align-items: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .qualification-degree {
            font-weight: 700;
            font-size: 16px;
            color: #1a2634;
            background: white;
            padding: 5px 15px;
            border-radius: 30px;
            border: 1px solid #e1e9f2;
        }

        .qualification-major {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
            color: #2c3e50;
        }

        .qualification-major i {
            color: #8a9bb0;
            font-size: 14px;
        }

        .qualification-institution {
            font-size: 14px;
            color: #5b6f82;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .qualification-institution i {
            color: #8a9bb0;
            font-size: 13px;
        }

        .qualification-date {
            font-size: 13px;
            color: #8a9bb0;
            display: flex;
            align-items: center;
            gap: 5px;
            background: white;
            padding: 4px 12px;
            border-radius: 25px;
            border: 1px solid #edf2f7;
        }

        /* الخبرات */
        .experiences-section {
            background: #f8fafd;
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 25px;
            border: 1px solid #edf2f7;
        }

        .experience-item {
            padding: 15px;
            border-bottom: 1px dashed #d0d9e3;
        }

        .experience-item:last-child {
            border-bottom: none;
        }

        .experience-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 8px;
        }

        .experience-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .experience-job {
            font-weight: 700;
            font-size: 16px;
            color: #1a2634;
            background: white;
            padding: 4px 15px;
            border-radius: 30px;
            border: 1px solid #e1e9f2;
        }

        .experience-company {
            font-size: 15px;
            color: #5b6f82;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .experience-company i {
            color: #8a9bb0;
            font-size: 13px;
        }

        .experience-date {
            font-size: 13px;
            color: #8a9bb0;
            display: flex;
            align-items: center;
            gap: 5px;
            background: white;
            padding: 4px 12px;
            border-radius: 25px;
            border: 1px solid #edf2f7;
        }

        .experience-description {
            font-size: 13px;
            color: #4a5a6e;
            line-height: 1.6;
            margin-top: 10px;
            padding-right: 20px;
            border-right: 2px solid #e1e9f2;
        }

        /* المشاريع */
        .projects-section {
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .project-item {
            padding: 18px;
            background: #f8fafd;
            border-radius: 16px;
            border: 1px solid #edf2f7;
            transition: all 0.2s;
        }

        .project-item:hover {
            border-color: #cbd5e1;
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .project-name {
            font-weight: 700;
            font-size: 16px;
            color: #1a2634;
            background: white;
            padding: 4px 12px;
            border-radius: 30px;
            border: 1px solid #e1e9f2;
        }

        .project-qr {
            width: 55px;
            height: 55px;
            background: white;
            padding: 5px;
            border-radius: 10px;
            border: 1px solid #edf2f7;
        }

        .project-desc {
            font-size: 13px;
            line-height: 1.6;
            color: #2c3e50;
        }

        /* الدورات - 4 أعمدة × صفين */
        .courses-section {
            margin-bottom: 25px;
        }

        .courses-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .courses-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .course-item {
            background: #f8fafd;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid #edf2f7;
            transition: all 0.2s;
        }

        .course-item:hover {
            border-color: #cbd5e1;
        }

        .course-name {
            font-weight: 700;
            font-size: 14px;
            color: #1a2634;
            margin-bottom: 6px;
        }

        .course-provider {
            font-size: 12px;
            color: #5b6f82;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }

        .course-provider i {
            color: #8a9bb0;
            font-size: 11px;
        }

        .course-date {
            font-size: 11px;
            color: #8a9bb0;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .course-date i {
            font-size: 10px;
        }

        /* المهارات - 4 أعمدة × صفين */
        .skills-section {
            margin-bottom: 25px;
        }

        .skills-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .skills-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .skill-item {
            background: #f8fafd;
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }

        .skill-item:hover {
            border-color: #cbd5e1;
        }

        .skill-name {
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
        }

        .skill-level {
            font-size: 12px;
            color: #8a9bb0;
            background: white;
            padding: 3px 10px;
            border-radius: 30px;
            border: 1px solid #e1e9f2;
        }

        /* فواصل */
        hr {
            border: none;
            border-top: 2px solid #f0f4f9;
            margin: 25px 0;
        }

        /* للطباعة */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .topbar, .print-wrapper {
                display: none;
            }
            
            .resume {
                box-shadow: none;
                border-radius: 0;
            }
            
            .contact-bar, .summary, .qualifications-section, 
            .experiences-section, .project-item, .course-item, .skill-item {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .projects-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>


<div class="print-wrapper">
    <button onclick="window.print()" class="print-btn">
        <i class="fas fa-print"></i> طباعة
    </button>
</div>

<div class="resume">
    <div class="resume-inner">
        <!-- الهيدر مع التاريخ والجنسية -->
        <div class="header">
            <div class="name-title">
                <h1><?= htmlspecialchars($personal['full_name'] ?? $cv['name']) ?></h1>
                <?php if (!empty($personal['job_title'])): ?>
                    <div class="job"><?= htmlspecialchars($personal['job_title']) ?></div>
                <?php endif; ?>
            </div>
            <div class="quick-info">
                <?php if (!empty($personal['date_of_birth'])): ?>
                    <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($personal['date_of_birth']) ?></span>
                <?php endif; ?>
                <?php if (!empty($personal['nationality'])): ?>
                    <span><i class="fas fa-globe"></i> <?= htmlspecialchars($personal['nationality']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- شريط التواصل - سطر واحد -->
        <div class="contact-bar">
            <?php if (!empty($personal['email'])): ?>
                <div class="contact-item"><i class="fas fa-envelope"></i> <?= htmlspecialchars($personal['email']) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($personal['phone'])): ?>
                <div class="contact-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($personal['phone']) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($personal['address'])): ?>
                <div class="contact-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($personal['address']) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($personal['linkedin'])): ?>
                <div class="contact-item"><i class="fab fa-linkedin"></i> <a href="<?= htmlspecialchars($personal['linkedin']) ?>" target="_blank">LinkedIn</a></div>
            <?php endif; ?>
            
            <?php if (!empty($personal['github'])): ?>
                <div class="contact-item"><i class="fab fa-github"></i> <a href="<?= htmlspecialchars($personal['github']) ?>" target="_blank">GitHub</a></div>
            <?php endif; ?>
            
            <?php if (!empty($personal['website'])): ?>
                <div class="contact-item"><i class="fas fa-globe"></i> <a href="<?= htmlspecialchars($personal['website']) ?>" target="_blank">Website</a></div>
            <?php endif; ?>
        </div>

        <!-- نبذة مختصرة -->
        <?php if (!empty($personal['summary'])): ?>
            <div class="summary">
                <?= nl2br(htmlspecialchars($personal['summary'])) ?>
            </div>
        <?php endif; ?>

        <!-- المؤهلات - عرض محسن مع الاسم والدرجة والتخصص -->
        <?php if (!empty($education)): ?>
            <div class="section-title">
                <i class="fas fa-graduation-cap"></i>
                <span>المؤهلات العلمية</span>
            </div>
            <div class="qualifications-section">
                <?php foreach ($education as $edu): ?>
                    <div class="qualification-item">
                        <div class="qualification-content">
                            <!-- اسم المؤهل/الدرجة -->
                            <span class="qualification-degree"><?= htmlspecialchars($edu['degree'] ?? '') ?></span>
                            
                            <!-- التخصص -->
                            <?php if (!empty($edu['field_of_study'])): ?>
                                <span class="qualification-major">
                                    <i class="fas fa-book-open"></i> <?= htmlspecialchars($edu['field_of_study']) ?>
                                </span>
                            <?php endif; ?>
                            
                            <!-- الجهة المانحة -->
                            <?php if (!empty($edu['institution'])): ?>
                                <span class="qualification-institution">
                                    <i class="fas fa-university"></i> <?= htmlspecialchars($edu['institution']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- التاريخ -->
                        <?php if ($edu['start_date'] || $edu['end_date']): ?>
                            <span class="qualification-date">
                                <i class="far fa-calendar-alt"></i> <?= formatDateRange($edu['start_date'], $edu['end_date']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- الخبرات -->
        <?php if (!empty($experiences)): ?>
            <div class="section-title">
                <i class="fas fa-briefcase"></i>
                <span>الخبرات المهنية</span>
            </div>
            <div class="experiences-section">
                <?php foreach ($experiences as $exp): ?>
                    <div class="experience-item">
                        <div class="experience-main">
                            <div class="experience-left">
                                <span class="experience-job"><?= htmlspecialchars($exp['job_title'] ?? '') ?></span>
                                <span class="experience-company">
                                    <i class="fas fa-building"></i> <?= htmlspecialchars($exp['company'] ?? '') ?>
                                </span>
                            </div>
                            <span class="experience-date">
                                <i class="far fa-calendar-alt"></i> <?= formatDateRange($exp['start_date'], $exp['end_date'], $exp['is_current'] ?? false) ?>
                            </span>
                        </div>
                        <?php if (!empty($exp['description'])): ?>
                            <div class="experience-description">
                                <?= nl2br(htmlspecialchars($exp['description'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <hr>

        <!-- الدورات - 4 أعمدة × صفين -->
        <?php if (!empty($courses)): ?>
            <div class="section-title">
                <i class="fas fa-certificate"></i>
                <span>الدورات التدريبية</span>
            </div>
            <div class="courses-section">
                <div class="courses-grid">
                    <?php foreach ($groupedCourses as $row): ?>
                        <div class="courses-row">
                            <?php foreach ($row as $course): ?>
                                <div class="course-item">
                                    <div class="course-name"><?= htmlspecialchars($course['course_name'] ?? '') ?></div>
                                    <?php if (!empty($course['provider'])): ?>
                                        <div class="course-provider">
                                            <i class="fas fa-building"></i> <?= htmlspecialchars($course['provider']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($course['completion_date'])): ?>
                                        <div class="course-date">
                                            <i class="far fa-calendar-check"></i> <?= formatDate($course['completion_date']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <!-- ملء الخانات الفارغة إذا كان العدد أقل من 4 -->
                            <?php for ($i = count($row); $i < 4; $i++): ?>
                                <div class="course-item" style="opacity: 0.3; background: transparent; border: 1px dashed #edf2f7;">
                                    <div class="course-name">-</div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- المشاريع مع باركود -->
        <?php if (!empty($projects)): ?>
            <div class="section-title">
                <i class="fas fa-diagram-project"></i>
                <span>المشاريع</span>
            </div>
            <div class="projects-section">
                <?php foreach ($projects as $index => $project): ?>
                    <div class="project-item">
                        <div class="project-header">
                            <span class="project-name"><?= htmlspecialchars($project['project_name'] ?? '') ?></span>
                            <?php if (!empty($project['project_link'])): ?>
                                <div id="qr-<?= $index ?>" class="project-qr"></div>
                                <script>
                                    new QRCode(document.getElementById("qr-<?= $index ?>"), {
                                        text: "<?= htmlspecialchars($project['project_link']) ?>",
                                        width: 55,
                                        height: 55,
                                        colorDark: "#1a2634",
                                        colorLight: "#ffffff",
                                        correctLevel: QRCode.CorrectLevel.H
                                    });
                                </script>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($project['project_description'])): ?>
                            <div class="project-desc">
                                <?= nl2br(htmlspecialchars($project['project_description'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- المهارات - 4 أعمدة × صفين -->
        <?php if (!empty($skills)): ?>
            <div class="section-title">
                <i class="fas fa-code"></i>
                <span>المهارات</span>
            </div>
            <div class="skills-section">
                <div class="skills-grid">
                    <?php foreach ($groupedSkills as $row): ?>
                        <div class="skills-row">
                            <?php foreach ($row as $skill): ?>
                                <div class="skill-item">
                                    <span class="skill-name"><?= htmlspecialchars($skill['skill_name'] ?? '') ?></span>
                                    <?php if (!empty($skill['skill_level'])): ?>
                                        <span class="skill-level"><?= htmlspecialchars($skill['skill_level']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <!-- ملء الخانات الفارغة إذا كان العدد أقل من 4 -->
                            <?php for ($i = count($row); $i < 4; $i++): ?>
                                <div class="skill-item" style="opacity: 0.3; background: transparent; border: 1px dashed #edf2f7;">
                                    <span class="skill-name">-</span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>