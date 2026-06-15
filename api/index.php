<?php
/**
 * Entrypoint para despliegues Serverless en Vercel.
 * Funciona como enrutador dinámico para simular el comportamiento de un servidor web tradicional (Apache/Nginx).
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Forzar SCRIPT_NAME y PHP_SELF para que los scripts sigan funcionando igual
$_SERVER['SCRIPT_NAME'] = $uri;
$_SERVER['PHP_SELF'] = $uri;

// Si la ruta raíz o index.php es solicitada
if ($uri === '/' || $uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

// Para las demás rutas, buscar el archivo físico correspondiente
$base = realpath(__DIR__ . '/..');
$file = realpath($base . $uri);

// Seguridad: evitar path traversal y asegurar que es un archivo PHP
if ($file && strpos($file, $base) === 0 && is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        require $file;
        exit;
    } else {
        // Para assets estáticos si Vercel llegara a enrutarlos aquí
        $mime = mime_content_type($file);
        header("Content-Type: $mime");
        readfile($file);
        exit;
    }
}

// Si la ruta solicitada es un directorio (ej. /modules/dashboard/)
// intentar buscar el index.php de ese directorio
if (is_dir($base . $uri)) {
    $indexPath = realpath($base . $uri . '/index.php');
    if ($indexPath && strpos($indexPath, $base) === 0 && is_file($indexPath)) {
        $_SERVER['SCRIPT_NAME'] = rtrim($uri, '/') . '/index.php';
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
        require $indexPath;
        exit;
    }
}

// Si nada funciona, devolver un error 404
http_response_code(404);
echo "404 Not Found - El archivo solicitado no existe: " . htmlspecialchars($uri);
exit;
