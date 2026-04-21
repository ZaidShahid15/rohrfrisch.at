<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$publicAssetRoot = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'site-clone';
$viewRoot = $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
$pageViewRoot = $viewRoot . DIRECTORY_SEPARATOR . 'pages';
$layoutRoot = $viewRoot . DIRECTORY_SEPARATOR . 'layouts';
$baseUrl = 'https://rohrfrisch.at';
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';
$cliOptions = parseCliOptions($argv ?? []);

$skipPrefixes = [
    '/ru/',
    '/wp-admin',
    '/wp-json',
    '/feed',
    '/comments',
    '/xmlrpc.php',
];

$seedPaths = [
    '/',
    '/dienstleistungen/',
    '/preise/',
    '/uber-uns/',
    '/kontakt/',
    '/impressum/',
    '/privacy-policy/',
    '/cookie-richtlinie/',
    '/servicedetails/',
];

foreach (range(1010, 1230, 10) as $district) {
    $seedPaths[] = "/abflussreinigung-wien-{$district}/";
    $seedPaths[] = "/rohrverstopfung-wien-{$district}/";
    $seedPaths[] = "/rohrverstopfung-notdienst-wien-{$district}/";
}

$singlePagePaths = $cliOptions['paths'];
$crawlInternalLinks = $singlePagePaths === [];
$writeSharedFiles = $singlePagePaths === [];

if ($singlePagePaths !== []) {
    $seedPaths = $singlePagePaths;
}

ensureDir($publicAssetRoot);
ensureDir($pageViewRoot);
ensureDir($layoutRoot);

$queue = array_values(array_unique($seedPaths));
$visited = [];
$pages = [];
$assets = [];

while ($queue !== []) {
    $path = array_shift($queue);
    $normalizedPath = normalizePath($path);

    if (isset($visited[$normalizedPath]) || shouldSkipPath($normalizedPath, $skipPrefixes)) {
        continue;
    }

    $visited[$normalizedPath] = true;
    $pageUrl = absoluteUrl($normalizedPath, $baseUrl);
    $response = fetchUrl($pageUrl, $userAgent);

    if ($response['status'] >= 400 || $response['body'] === '') {
        fwrite(STDERR, "Skipping {$pageUrl} (HTTP {$response['status']})" . PHP_EOL);
        continue;
    }

    $pageData = transformPageHtml($response['body'], $baseUrl, $publicAssetRoot, $assets, $queue, $skipPrefixes, $crawlInternalLinks);
    $viewName = viewNameForPath($normalizedPath);

    $pages[] = [
        'path' => $normalizedPath,
        'view' => $viewName,
        'title' => $pageData['title'],
        'body_attrs' => $pageData['body_attrs'],
        'head' => $pageData['head'],
        'content' => $pageData['content'],
        'scripts' => $pageData['scripts'],
    ];

    echo "Fetched page: {$normalizedPath}" . PHP_EOL;
}

downloadAssets($assets, $publicAssetRoot, $userAgent);
if ($writeSharedFiles) {
    writeLayout($layoutRoot . DIRECTORY_SEPARATOR . 'site.blade.php');
}
writePageViews($pages, $pageViewRoot);
if ($writeSharedFiles) {
    writeConfig($projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'site_clone.php', $pages);
}

echo 'Generated ' . count($pages) . ' Blade pages and ' . count($assets) . ' asset references.' . PHP_EOL;

function ensureDir(string $path): void
{
    if (! is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function parseCliOptions(array $argv): array
{
    $paths = [];

    foreach (array_slice($argv, 1) as $argument) {
        if (str_starts_with($argument, '--path=')) {
            $value = substr($argument, 7);

            if ($value !== '') {
                $paths[] = $value;
            }
        }
    }

    return [
        'paths' => array_values(array_unique($paths)),
    ];
}

function shouldSkipPath(string $path, array $skipPrefixes): bool
{
    foreach ($skipPrefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return true;
        }
    }

    return false;
}

function normalizePath(string $path): string
{
    if ($path === '') {
        return '/';
    }

    $path = parse_url($path, PHP_URL_PATH) ?: '/';
    $path = '/' . ltrim($path, '/');

    return $path === '' ? '/' : $path;
}

function absoluteUrl(string $path, string $baseUrl): string
{
    return rtrim($baseUrl, '/') . ($path === '/' ? '/' : $path);
}

function fetchUrl(string $url, string $userAgent): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => ['Accept-Language: en-US,en;q=0.9'],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
    ];
}

function transformPageHtml(
    string $html,
    string $baseUrl,
    string $publicAssetRoot,
    array &$assets,
    array &$queue,
    array $skipPrefixes,
    bool $crawlInternalLinks = true
): array {
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new DOMXPath($dom);

    $titleNode = $xpath->query('//title')->item(0);
    $title = $titleNode?->textContent ? trim($titleNode->textContent) : 'RohrFrisch';

    foreach ($xpath->query('//*[@href]') as $node) {
        rewriteUrlAttribute($node, 'href', $baseUrl, $publicAssetRoot, $assets, $queue, $skipPrefixes, $crawlInternalLinks);
    }

    foreach ($xpath->query('//*[@src]') as $node) {
        rewriteUrlAttribute($node, 'src', $baseUrl, $publicAssetRoot, $assets, $queue, $skipPrefixes, $crawlInternalLinks);
    }

    foreach ($xpath->query('//*[@srcset]') as $node) {
        rewriteSrcset($node, $baseUrl, $publicAssetRoot, $assets);
    }

    foreach ($xpath->query('//*[@style]') as $node) {
        $node->setAttribute('style', rewriteInlineCssUrls($node->getAttribute('style'), $baseUrl, $publicAssetRoot, $assets));
    }

    foreach ($xpath->query('//style') as $styleNode) {
        $styleNode->nodeValue = rewriteInlineCssUrls($styleNode->nodeValue, $baseUrl, $publicAssetRoot, $assets);
    }

    $headNode = $xpath->query('//head')->item(0);
    $bodyNode = $xpath->query('//body')->item(0);

    $headHtml = '';
    $scriptsHtml = '';

    if ($headNode instanceof DOMNode) {
        foreach (iterator_to_array($headNode->childNodes) as $child) {
            if ($child->nodeName === 'title') {
                continue;
            }

            $headHtml .= trim($headNode->ownerDocument->saveHTML($child)) . PHP_EOL;
        }
    }

    $bodyAttrs = '';
    $contentHtml = '';

    if ($bodyNode instanceof DOMElement) {
        foreach ($bodyNode->attributes as $attribute) {
            $bodyAttrs .= sprintf('%s="%s" ', $attribute->nodeName, eAttr($attribute->nodeValue));
        }

        foreach (iterator_to_array($bodyNode->childNodes) as $child) {
            $htmlChunk = trim($bodyNode->ownerDocument->saveHTML($child));

            if ($child instanceof DOMElement && strtolower($child->tagName) === 'script') {
                $scriptsHtml .= $htmlChunk . PHP_EOL;
                continue;
            }

            $contentHtml .= $htmlChunk . PHP_EOL;
        }
    }

    return [
        'title' => $title,
        'body_attrs' => trim($bodyAttrs),
        'head' => trim($headHtml),
        'content' => trim($contentHtml),
        'scripts' => trim($scriptsHtml),
    ];
}

function rewriteUrlAttribute(
    DOMElement $node,
    string $attribute,
    string $baseUrl,
    string $publicAssetRoot,
    array &$assets,
    array &$queue,
    array $skipPrefixes,
    bool $crawlInternalLinks = true
): void {
    $value = trim($node->getAttribute($attribute));

    if ($value === '') {
        return;
    }

    if (str_starts_with($value, 'mailto:') || str_starts_with($value, 'tel:') || str_contains($value, 'api.whatsapp.com')) {
        return;
    }

    $absolute = makeAbsoluteUrl($value, $baseUrl);

    if ($absolute === null) {
        return;
    }

    $host = parse_url($absolute, PHP_URL_HOST);
    $path = normalizePath(parse_url($absolute, PHP_URL_PATH) ?: '/');

    if ($host === 'rohrfrisch.at') {
        if (isAssetUrl($absolute)) {
            $localPath = localAssetPath($absolute, $publicAssetRoot);
            $assets[$absolute] = $localPath;
            $node->setAttribute($attribute, publicAssetUrl($localPath, $publicAssetRoot));
            return;
        }

        if ($crawlInternalLinks && ! shouldSkipPath($path, $skipPrefixes)) {
            $queue[] = $path;
        }

        $node->setAttribute($attribute, $path === '/' ? '/' : $path);

        return;
    }

    if ($host === 'fonts.googleapis.com' || $host === 'fonts.gstatic.com') {
        $localPath = localAssetPath($absolute, $publicAssetRoot);
        $assets[$absolute] = $localPath;
        $node->setAttribute($attribute, publicAssetUrl($localPath, $publicAssetRoot));
    }
}

function rewriteSrcset(DOMElement $node, string $baseUrl, string $publicAssetRoot, array &$assets): void
{
    $entries = array_filter(array_map('trim', explode(',', $node->getAttribute('srcset'))));
    $rewritten = [];

    foreach ($entries as $entry) {
        [$url, $descriptor] = array_pad(preg_split('/\s+/', $entry, 2), 2, '');
        $absolute = makeAbsoluteUrl($url, $baseUrl);

        if ($absolute === null) {
            $rewritten[] = trim($entry);
            continue;
        }

        if (isAssetUrl($absolute)) {
            $localPath = localAssetPath($absolute, $publicAssetRoot);
            $assets[$absolute] = $localPath;
            $rewritten[] = trim(publicAssetUrl($localPath, $publicAssetRoot) . ' ' . $descriptor);
        } else {
            $rewritten[] = trim($entry);
        }
    }

    $node->setAttribute('srcset', implode(', ', $rewritten));
}

function rewriteInlineCssUrls(string $css, string $baseUrl, string $publicAssetRoot, array &$assets): string
{
    return preg_replace_callback('/url\((["\']?)([^)\'"]+)\1\)/i', function (array $matches) use ($baseUrl, $publicAssetRoot, &$assets) {
        $absolute = makeAbsoluteUrl(trim($matches[2]), $baseUrl);

        if ($absolute === null || ! isAssetUrl($absolute)) {
            return $matches[0];
        }

        $localPath = localAssetPath($absolute, $publicAssetRoot);
        $assets[$absolute] = $localPath;

        return 'url(' . publicAssetUrl($localPath, $publicAssetRoot) . ')';
    }, $css) ?? $css;
}

function makeAbsoluteUrl(string $value, string $baseUrl): ?string
{
    if ($value === '' || str_starts_with($value, '#') || str_starts_with($value, 'data:')) {
        return null;
    }

    if (str_starts_with($value, '//')) {
        return 'https:' . $value;
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    if (str_starts_with($value, '/')) {
        return rtrim($baseUrl, '/') . $value;
    }

    return null;
}

function isAssetUrl(string $url): bool
{
    $path = strtolower(parse_url($url, PHP_URL_PATH) ?: '');

    return str_contains($path, '/wp-content/')
        || str_contains($path, '/wp-includes/')
        || preg_match('/\.(css|js|png|jpe?g|gif|svg|webp|woff2?|ttf|eot|ico)$/', $path) === 1;
}

function localAssetPath(string $url, string $publicAssetRoot): string
{
    $host = parse_url($url, PHP_URL_HOST) ?: 'asset';
    $path = parse_url($url, PHP_URL_PATH) ?: '/asset';
    $query = parse_url($url, PHP_URL_QUERY);
    $safePath = ltrim($path, '/');

    if ($query) {
        $extension = pathinfo($safePath, PATHINFO_EXTENSION);

        if ($extension !== '') {
            $extensionWithDot = '.' . $extension;
            $basePath = substr($safePath, 0, -strlen($extensionWithDot));
            $safePath = $basePath . '--' . md5($query) . $extensionWithDot;
        } elseif ($host === 'fonts.googleapis.com') {
            $safePath .= '--' . md5($query) . '.css';
        } else {
            $safePath .= '--' . md5($query);
        }
    }

    return $publicAssetRoot . DIRECTORY_SEPARATOR . $host . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safePath);
}

function publicAssetUrl(string $localPath, string $publicAssetRoot): string
{
    $relative = str_replace($publicAssetRoot, '', $localPath);
    $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

    return '/site-clone' . $relative;
}

function downloadAssets(array $assets, string $publicAssetRoot, string $userAgent): void
{
    foreach ($assets as $url => $localPath) {
        ensureDir(dirname($localPath));

        if (is_file($localPath)) {
            continue;
        }

        $response = fetchUrl($url, $userAgent);

        if ($response['status'] >= 400 || $response['body'] === '') {
            fwrite(STDERR, "Skipping asset {$url} (HTTP {$response['status']})" . PHP_EOL);
            continue;
        }

        file_put_contents($localPath, $response['body']);
        echo "Downloaded asset: {$url}" . PHP_EOL;
    }
}

function viewNameForPath(string $path): string
{
    if ($path === '/') {
        return 'home';
    }

    $name = trim($path, '/');
    $name = str_replace(['/', '-'], ['.', '_'], $name);

    return $name;
}

function writeLayout(string $layoutPath): void
{
    $layout = <<<'BLADE'
<!doctype html>
<html lang="@yield('html_lang', 'en-US')">
<head>
    <title>@yield('title', 'RohrFrisch')</title>
    @yield('head')
</head>
<body @yield('body_attributes')>
    @yield('content')
    @yield('scripts')
</body>
</html>
BLADE;

    file_put_contents($layoutPath, $layout . PHP_EOL);
}

function writePageViews(array $pages, string $pageViewRoot): void
{
    foreach ($pages as $page) {
        $target = $pageViewRoot . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $page['view']) . '.blade.php';
        ensureDir(dirname($target));

        $blade = <<<'BLADE'
@extends('layouts.site')

@section('title', __TITLE__)

@section('body_attributes')
__BODY_ATTRS__
@endsection

@section('head')
@verbatim
__HEAD__
@endverbatim
@endsection

@section('content')
@verbatim
__CONTENT__
@endverbatim
@endsection

@section('scripts')
@verbatim
__SCRIPTS__
@endverbatim
@endsection
BLADE;

        $blade = str_replace('__TITLE__', var_export($page['title'], true), $blade);
        $blade = str_replace('__BODY_ATTRS__', $page['body_attrs'], $blade);
        $blade = str_replace('__HEAD__', $page['head'], $blade);
        $blade = str_replace('__CONTENT__', $page['content'], $blade);
        $blade = str_replace('__SCRIPTS__', $page['scripts'], $blade);

        file_put_contents($target, $blade . PHP_EOL);
    }
}

function writeConfig(string $configPath, array $pages): void
{
    $export = [];

    foreach ($pages as $page) {
        $export[$page['path']] = 'pages.' . $page['view'];
    }

    $content = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($export, true) . ';' . PHP_EOL;
    file_put_contents($configPath, $content);
}

function eAttr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
