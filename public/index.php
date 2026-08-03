<?php
/**
 * Front Controller — satu-satunya entry point aplikasi.
 * Semua request (lewat .htaccess rewrite) masuk ke sini.
 */

// Hanya berlaku saat menjalankan `php -S` (dev server bawaan PHP untuk testing cepat tanpa Apache).
// Server bawaan ini, tidak seperti Apache+.htaccess, akan memanggil index.php untuk SEMUA request
// termasuk file statis (CSS/JS/gambar) jika dijalankan dengan router script. Baris ini membiarkan
// dev server men-serve file statis apa adanya. Sama sekali tidak memengaruhi Apache/Nginx produksi.
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($staticFile !== __DIR__ . '/' && is_file($staticFile)) {
        return false;
    }
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

session_start();

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth_helper.php';

/**
 * Autoload sederhana: cari class di folder controllers, models, middleware.
 * Tidak pakai namespace supaya tetap "native & simple" sesuai kebutuhan project.
 */
spl_autoload_register(function (string $class) {
    $paths = [
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../app/models/' . $class . '.php',
        __DIR__ . '/../app/middleware/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// ---------- CSRF check untuk semua request POST/PUT/DELETE ----------
CsrfMiddleware::handle();

// ---------- Routing ----------
$routes = require __DIR__ . '/../routes/web.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Hilangkan prefix BASE_URL agar matching route konsisten meskipun project ada di sub-folder
$basePath = rtrim(BASE_URL, '/');
if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . ltrim($uri, '/');
$uri = rtrim($uri, '/') ?: '/';

$routeKey = $method . ' ' . $uri;
$params = [];
$matchedRoute = null;

if (isset($routes[$routeKey])) {
    // Match langsung (route tanpa parameter dinamis)
    $matchedRoute = $routes[$routeKey];
} else {
    // Coba cocokkan route dengan parameter dinamis, contoh: 'GET /categories/{id}/edit'
    foreach ($routes as $pattern => $routeDef) {
        [$patternMethod, $patternPath] = explode(' ', $pattern, 2);
        if ($patternMethod !== $method) {
            continue;
        }

        $regex = '#^' . preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $patternPath) . '$#';
        if (preg_match($regex, $uri, $matches)) {
            $matchedRoute = $routeDef;
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            break;
        }
    }
}

if ($matchedRoute === null) {
    http_response_code(404);
    require __DIR__ . '/../app/views/errors/404.php';
    exit;
}

[$controllerName, $action, $options] = array_pad($matchedRoute, 3, []);

// ---------- Middleware ----------
if (!empty($options['auth'])) {
    AuthMiddleware::handle();
}

if (!empty($options['permission'])) {
    [$menuRouteKey, $ability] = $options['permission'];
    RoleMiddleware::handle($menuRouteKey, $ability);
}

// ---------- Dispatch ke Controller ----------
$controller = new $controllerName();
call_user_func_array([$controller, $action], array_values($params));
