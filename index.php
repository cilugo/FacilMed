<?php

/*
 * FacilMed — index da raiz (24/09/2026).
 *
 * Faz o sistema abrir direto pelo Apache do XAMPP em
 *     http://localhost/FacilMed/
 * sem precisar do "composer run dev". É o mesmo que public/index.php,
 * só que a partir da raiz do projeto. O .htaccess ao lado manda todas
 * as URLs do sistema para cá e serve CSS/JS/imagens de public/.
 *
 * Os dois jeitos continuam valendo:
 *   - XAMPP (Apache):   http://localhost/FacilMed/
 *   - composer run dev: http://127.0.0.1:8000
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (! file_exists(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>FacilMed — falta instalar</title>'
        . '<body style="font-family:sans-serif;max-width:620px;margin:60px auto;line-height:1.6;color:#183e9f">'
        . '<h1>FacilMed ainda não está instalado nesta máquina</h1>'
        . '<p>Abra o terminal na pasta do projeto e rode:</p>'
        . '<pre style="background:#eef3ff;padding:14px;border-radius:8px">composer run setup</pre>'
        . '<p>Passo a passo completo no arquivo <strong>README.md (seção 2)</strong>.</p></body>';
    exit;
}

if (file_exists($manutencao = __DIR__ . '/storage/framework/maintenance.php')) {
    require $manutencao;
}

require __DIR__ . '/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
