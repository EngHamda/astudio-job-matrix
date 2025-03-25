FROM alpine:latest

# Update the package list and install PHP and required extensions
RUN apk update && apk add --no-cache php php-fpm php-session php-json php-mysqli \
    php-iconv php-pdo php-pdo_mysql php-xml php-simplexml php-tokenizer curl


RUN mkdir -p /var/www/html

# Download Adminer
RUN curl -L https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php -o /var/www/html/index.php

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
