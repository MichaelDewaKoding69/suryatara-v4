# Image untuk hosting di Railway: PHP 8.3 + Apache + ekstensi MySQL
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
 && sed -ri "s/Options Indexes FollowSymLinks/Options FollowSymLinks/" /etc/apache2/apache2.conf \
 && { echo 'upload_max_filesize = 10M'; echo 'post_max_size = 30M'; echo 'date.timezone = Asia/Jakarta'; } > /usr/local/etc/php/conf.d/suryatara.ini

COPY . /var/www/html/
COPY docker/start.sh /usr/local/bin/start.sh
RUN sed -i "s/\r$//" /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh && chown -R www-data:www-data /var/www/html

CMD ["start.sh"]
