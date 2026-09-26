FROM php:8.2-apache

# تثبيت وتفعيل مكتبات PDO الخاصة بقواعد البيانات لتشغيل مشروعك
RUN docker-php-ext-install pdo pdo_mysql

# نسخ ملفات مشروع الـ backend بالكامل إلى السيرفر
COPY . /var/www/html/

# تفعيل مود الروابط المتطورة والمخفية (Router)
RUN a2enmod rewrite

# ضبط السيرفر ليعمل على مجلد الـ htdocs الافتراضي
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/0000-default.conf

EXPOSE 80
