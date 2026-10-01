#!/bin/sh
# FacilMed — roda toda vez que o servidor liga (README §2.7).
set -e

cd /var/www/html

# 1. Apache escuta na porta que o Render mandar.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Endereço do site: se ninguém definiu APP_URL, usa o que o Render criou
# (ex.: https://facilmed.onrender.com). Vai nos links dos e-mails.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# 2. Certificado do banco (Aiven exige conexão segura). O texto do
#    certificado vem da variável MYSQL_CA_CERT e vira um arquivo.
if [ -n "$MYSQL_CA_CERT" ]; then
    printf '%s\n' "$MYSQL_CA_CERT" > /tmp/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem
fi

# 3. Cache de configuração, rotas e telas (site mais rápido).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Cria/atualiza as tabelas. Só aplica migrations novas; não apaga nada.
php artisan migrate --force

# 5. Banco vazio (primeiro deploy)? Coloca os dados de demonstração.
USUARIOS=$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n 1)
if [ "$USUARIOS" = "0" ]; then
    echo "FacilMed: banco vazio, rodando os seeders de demonstração."
    php artisan db:seed --force
else
    echo "FacilMed: banco já tem dados ($USUARIOS usuários), seeder não roda."
fi

# 6. (01/10/2026) Fotos das unidades de demonstração que ainda não têm foto
#    (não mexe nas que uma clínica enviou). Pode rodar a cada boot.
php artisan db:seed --class=FotosDemonstracaoSeeder --force || true

# 7. (01/10/2026) Administrador com e-mail de verdade, se ADMIN_EMAIL e
#    ADMIN_PASSWORD estiverem no Render. Já existe? Não mexe. Nunca derruba o boot.
php artisan facilmed:garantir-admin || true

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
