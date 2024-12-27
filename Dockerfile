FROM php:8.3.3-alpine


RUN curl -sS https://getcomposer.org/installer | php -- \
     --install-dir=/usr/local/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --working-dir=/var/www/html

RUN echo "generating application key..."
RUN php artisan key:generate --show

RUN echo "Caching config..."
RUN php artisan config:cache

RUN echo "Caching routes..."
RUN php artisan route:cache


