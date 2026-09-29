# =====================================================================
# FacilMed — imagem para o servidor (Render).
#
# Só é usada no deploy. Na máquina dos alunos continua valendo o XAMPP
# (README §2). O Render lê este arquivo, monta a imagem e sobe o site a
# cada push na branch configurada. Passo a passo: README §2.7.
# =====================================================================

FROM php:8.3-apache

# Extensões do PHP que o Laravel/FacilMed usam + ferramentas do Composer.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# O Apache passa a servir a pasta public/ (o index.php de lá é o do Laravel).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala as dependências primeiro (camada reaproveitada entre deploys).
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# Copia o resto do projeto e finaliza o autoload.
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# O Render avisa a porta pela variável PORT (padrão 10000).
ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
