-- ============================================================
-- قاعدة بيانات "اللوحة الإعلانية" - كلية فارس التقانية
-- محدّثة عن جداول البحث الأصلية (AD_NO/AD_NAME...) بأسماء عصرية
-- متوافقة مع MySQL 8+
-- ============================================================

CREATE DATABASE IF NOT EXISTS faris_billboard
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE faris_billboard;

-- جدول الإدارات (Administrations) — يقابل "جدول الإدارة" في البحث
CREATE TABLE administrations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)     NOT NULL,
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول المستخدمين (Users) — يقابل "جدول المستخدم"، VALIDITY -> role
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)      NOT NULL UNIQUE,
    password_hash   VARCHAR(255)     NOT NULL,
    full_name       VARCHAR(100)     NOT NULL,
    role            ENUM('admin','employee') NOT NULL DEFAULT 'employee',
    administration_id INT UNSIGNED  NULL,
    is_active       TINYINT(1)       NOT NULL DEFAULT 1,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_administration
        FOREIGN KEY (administration_id) REFERENCES administrations(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- جدول الإعلانات (Ads) — يقابل "جدول الاعلان"
CREATE TABLE ads (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(150)     NOT NULL,   -- AD_NAME
    content             TEXT             NOT NULL,   -- AD_CONTENT
    source              VARCHAR(100)     NULL,       -- AD_DIR (جهة الإعلان)
    image_path          VARCHAR(255)     NULL,       -- AD_IMEG
    administration_id   INT UNSIGNED     NOT NULL,   -- FK -> administrations
    user_id             INT UNSIGNED     NOT NULL,   -- FK -> users (من نشر الإعلان)
    published_at        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP, -- AD_DATE
    created_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ads_administration
        FOREIGN KEY (administration_id) REFERENCES administrations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ads_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_ads_published_at (published_at)
) ENGINE=InnoDB;

-- جدول رموز تحديث الدخول (لدعم تسجيل الخروج/إبطال الجلسة مع JWT عديم الحالة)
CREATE TABLE refresh_tokens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED     NOT NULL,
    token_hash  VARCHAR(255)     NOT NULL,
    expires_at  DATETIME         NOT NULL,
    revoked     TINYINT(1)       NOT NULL DEFAULT 0,
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- جدول رموز أجهزة الإشعارات (Firebase Cloud Messaging)
CREATE TABLE device_tokens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED     NULL,       -- يمكن أن يكون NULL لطالب لم يسجل دخول (جهاز عام)
    fcm_token   VARCHAR(255)     NOT NULL UNIQUE,
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- بيانات أولية: إدارة عامة + مستخدم أدمن افتراضي
-- المستخدم: admin  |  كلمة المرور: Admin@123  (غيّرها فوراً بعد أول رفع للاستضافة)
INSERT INTO administrations (id, name) VALUES (1, 'الإدارة العامة');

INSERT INTO users (username, password_hash, full_name, role, administration_id, is_active)
VALUES (
    'admin',
    '$2y$10$GFyceRf0dl/ssOvhajeupuD5.vlF7mboZCBB.8RtxccUrz88zyHg2',
    'مدير النظام',
    'admin',
    1,
    1
);
