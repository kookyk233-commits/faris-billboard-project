FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
# نسخ كل محتويات المستودع إلى السيرفر واستهداف ملف index.php مباشرة
COPY . /var/www/html/
RUN a2enmod rewrite
EXPOSE 80
