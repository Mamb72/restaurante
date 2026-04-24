<?php
/**
 * config/config.php
 * Constantes globales del proyecto.
 *
 * Este archivo SÍ se versiona en Git porque no contiene datos sensibles.
 */

declare(strict_types=1);

// --- Rutas base del proyecto -------------------------------------------
// Ruta absoluta a la raíz del proyecto (útil para includes).
define('BASE_PATH', dirname(__DIR__));

// URL base pública del proyecto (sin barra final).
define('BASE_URL', 'http://restaurante.local');

// --- Configuración general ----------------------------------------------
// Modo debug: en desarrollo TRUE, en producción FALSE.
define('DEBUG_MODE', true);

// Zona horaria del servidor.
date_default_timezone_set('Europe/Madrid');

// --- Cabeceras de seguridad básicas -------------------------------------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

// --- Configuración de errores según el modo -----------------------------
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
