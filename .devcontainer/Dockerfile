FROM php:8.5.12-cli

RUN apt -y update && apt -y upgrade

WORKDIR /tmp
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt install -y bash vim jq unzip git