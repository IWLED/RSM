-- إنشاء قاعدة البيانات إذا لم تكن موجودة
CREATE DATABASE IF NOT EXISTS rsm
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE rsm;

-- ======================================
-- جدول المستخدمين
-- ======================================
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول السير الذاتية
-- ======================================
CREATE TABLE IF NOT EXISTS cvs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    name VARCHAR(150) NOT NULL,
    template_name VARCHAR(100) DEFAULT 'default',
    is_published TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
    KEY user_id (user_id),
    CONSTRAINT cvs_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول المعلومات الشخصية
-- ======================================
CREATE TABLE IF NOT EXISTS personal_info (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cv_id INT(11) NOT NULL,
    job_title VARCHAR(150) DEFAULT NULL,
    email VARCHAR(30) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    summary TEXT DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    nationality VARCHAR(100) DEFAULT NULL,
    linkedin VARCHAR(255) DEFAULT NULL,
    github VARCHAR(255) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id),
    KEY cv_id (cv_id),
    CONSTRAINT personal_info_ibfk_1 FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول التعليم
-- ======================================
CREATE TABLE IF NOT EXISTS education (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cv_id INT(11) NOT NULL,
    degree VARCHAR(150) DEFAULT NULL,
    institution VARCHAR(150) DEFAULT NULL,
    field_of_study VARCHAR(150) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    description TEXT DEFAULT NULL,
    PRIMARY KEY(id),
    KEY cv_id (cv_id),
    CONSTRAINT education_ibfk_1 FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول الخبرات العملية
-- ======================================
CREATE TABLE IF NOT EXISTS experience (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cv_id INT(11) NOT NULL,
    job_title VARCHAR(150) DEFAULT NULL,
    company VARCHAR(150) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_current TINYINT(1) DEFAULT 0,
    description TEXT DEFAULT NULL,
    PRIMARY KEY(id),
    KEY cv_id (cv_id),
    CONSTRAINT experience_ibfk_1 FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول المشاريع
-- ======================================
CREATE TABLE IF NOT EXISTS projects (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cv_id INT(11) NOT NULL,
    project_name VARCHAR(150) DEFAULT NULL,
    project_description TEXT DEFAULT NULL,
    project_link VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(id),
    KEY cv_id (cv_id),
    CONSTRAINT projects_ibfk_1 FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول الدورات
-- ======================================
CREATE TABLE IF NOT EXISTS courses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cv_id INT(11) NOT NULL,
    course_name VARCHAR(150) DEFAULT NULL,
    provider VARCHAR(150) DEFAULT NULL,
    completion_date DATE DEFAULT NULL,
    PRIMARY KEY(id),
    KEY cv_id (cv_id),
    CONSTRAINT courses_ibfk_1 FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ======================================
-- جدول المهارات
-- ======================================
CREATE TABLE IF NOT EXISTS skills (
    id INT(11) NOT NULL AUTO_INCREMENT,
    cv_id INT(11) NOT NULL,
    skill_name VARCHAR(100) DEFAULT NULL,
    skill_level ENUM('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Intermediate',
    PRIMARY KEY(id),
    KEY cv_id (cv_id),
    CONSTRAINT skills_ibfk_1 FOREIGN KEY (cv_id) REFERENCES cvs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
