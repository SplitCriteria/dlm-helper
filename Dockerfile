FROM php:7.2-apache
WORKDIR /var/www/html
COPY . .
RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/conf.d/php.ini