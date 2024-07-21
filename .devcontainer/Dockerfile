FROM php:7

RUN apt -y update && apt -y upgrade

WORKDIR /tmp
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt install -y bash vim jq