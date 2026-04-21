<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$viewsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'pages';
$configPath = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'site_pages.php';
$pageControllerPath = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'PageController.php';
$routesPath = $projectRoot . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
$sitemapControllerPath = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'SitemapController.php';

$pages = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile()) {
        continue;
    }

    $filename = $file->getFilename();

    if (! str_ends_with($filename, '.blade.php')) {
        continue;
    }

    $relative = str_replace($viewsRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

    if (str_starts_with($relative, 'partials/')) {
        continue;
    }

    $view = 'pages.' . str_replace(['/', '.blade.php'], ['.', ''], $relative);
    $path = pathForView($view);
    $method = methodForView($view);
    $pages[$path] = [
        'view' => $view,
        'method' => $method,
    ];
}

uksort($pages, static function (string $a, string $b): int {
    if ($a === '/') {
        return -1;
    }

    if ($b === '/') {
        return 1;
    }

    return strcmp($a, $b);
});

writeConfig($configPath, $pages);
writePageController($pageControllerPath, $pages);
writeRoutes($routesPath, $pages);
writeSitemapController($sitemapControllerPath);

echo 'Generated config, controller, routes, and sitemap for ' . count($pages) . ' pages.' . PHP_EOL;

function pathForView(string $view): string
{
    $name = substr($view, strlen('pages.'));

    if ($name === 'home') {
        return '/';
    }

    return '/' . str_replace(['.', '_'], ['/', '-'], $name);
}

function methodForView(string $view): string
{
    return str_replace('.', '_', substr($view, strlen('pages.')));
}

function writeConfig(string $path, array $pages): void
{
    $export = [];

    foreach ($pages as $routePath => $page) {
        $export[$routePath] = $page['view'];
    }

    $content = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($export, true) . ';' . PHP_EOL;
    file_put_contents($path, $content);
}

function writePageController(string $path, array $pages): void
{
    $methods = [];

    foreach ($pages as $page) {
        $methods[] = "    public function {$page['method']}() { return view('{$page['view']}'); }";
    }

    $content = <<<PHP
<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
%s
}
PHP;

    file_put_contents($path, sprintf($content, implode(PHP_EOL, $methods)) . PHP_EOL);
}

function writeRoutes(string $path, array $pages): void
{
    $lines = [
        '<?php',
        '',
        'use App\Http\Controllers\FormSubmissionController;',
        'use App\Http\Controllers\PageController;',
        'use App\Http\Controllers\SitemapController;',
        'use Illuminate\Support\Facades\Route;',
        '',
        "Route::post('/send-form', [FormSubmissionController::class, 'submit'])->name('forms.submit');",
        "Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');",
        '',
    ];

    foreach ($pages as $routePath => $page) {
        $lines[] = "Route::get('{$routePath}', [PageController::class, '{$page['method']}']);";
    }

    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
}

function writeSitemapController(string $path): void
{
    $content = <<<'PHP'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $entries = collect(config('site_pages', []))
            ->map(function (string $view, string $path): array {
                $lastModified = public_path('index.php');
                $viewPath = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');

                if (is_file($viewPath)) {
                    $lastModified = $viewPath;
                }

                return [
                    'loc' => $path === '/' ? url('/') : url($path),
                    'lastmod' => gmdate('c', filemtime($lastModified)),
                ];
            })
            ->values()
            ->all();

        $xml = view('sitemap.xml', ['entries' => $entries])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
PHP;

    file_put_contents($path, $content . PHP_EOL);
}
