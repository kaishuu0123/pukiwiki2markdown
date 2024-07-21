FROM php:7

RUN apt -y update && apt -y upgrade

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir /app
COPY . /app

WORKDIR /app
RUN composer install

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "-c", "php.ini"]
