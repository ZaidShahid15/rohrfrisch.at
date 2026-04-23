<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$viewsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views';
$pagesConfig = require $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'site_pages.php';
$baseUrl = 'https://klickpin.site';
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36';
$runId = date('Ymd_His');
$outputDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'seo-migration' . DIRECTORY_SEPARATOR . $runId;

ensureDir($outputDir);

$crawl = crawlSite($baseUrl, $userAgent);
$metadataByPath = [];
$crawlErrors = [];

foreach ($crawl['pages'] as $page) {
    $path = normalizePath($page['url'], $baseUrl);
    $metadataByPath[$path] = $page;
}

foreach ($crawl['errors'] as $error) {
    $crawlErrors[] = $error;
}

$localBackup = [];
$updatedPages = [];
$unmappedPages = [];
$applyErrors = [];

foreach ($pagesConfig as $routePath => $viewName) {
    $viewPath = $viewsRoot . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $viewName) . '.blade.php';

    if (! is_file($viewPath)) {
        $applyErrors[] = [
            'path' => $routePath,
            'view' => $viewName,
            'error' => 'View file not found',
        ];
        continue;
    }

    $original = file_get_contents($viewPath);

    if (! is_string($original)) {
        $applyErrors[] = [
            'path' => $routePath,
            'view' => $viewName,
            'error' => 'Unable to read view file',
        ];
        continue;
    }

    $currentMetadata = extractLocalMetadata($original);
    $localBackup[] = [
        'path' => $routePath,
        'view' => $viewName,
        'file' => relativePath($projectRoot, $viewPath),
        'metadata' => $currentMetadata,
    ];

    if (! isset($metadataByPath[$routePath])) {
        continue;
    }

    $scraped = $metadataByPath[$routePath];
    $result = applyMetadataToBlade($original, $scraped, $routePath);

    if ($result['updated_content'] === $original) {
        continue;
    }

    file_put_contents($viewPath, $result['updated_content']);

    $updatedPages[] = [
        'path' => $routePath,
        'view' => $viewName,
        'file' => relativePath($projectRoot, $viewPath),
        'changes' => $result['changes'],
        'source_url' => $scraped['url'],
    ];
}

foreach ($metadataByPath as $path => $page) {
    if (! array_key_exists($path, $pagesConfig)) {
        $unmappedPages[] = [
            'path' => $path,
            'url' => $page['url'],
            'title' => $page['title'],
        ];
    }
}

$validation = validateLocalMetadata($pagesConfig, $viewsRoot);

writeJson($outputDir . DIRECTORY_SEPARATOR . 'extracted_metadata.json', array_values($metadataByPath));
writeJson($outputDir . DIRECTORY_SEPARATOR . 'local_metadata_backup.json', $localBackup);
writeJson($outputDir . DIRECTORY_SEPARATOR . 'updated_pages.json', $updatedPages);
writeJson($outputDir . DIRECTORY_SEPARATOR . 'unmapped_pages.json', $unmappedPages);
writeJson($outputDir . DIRECTORY_SEPARATOR . 'errors.json', [
    'crawl_errors' => $crawlErrors,
    'apply_errors' => $applyErrors,
]);
writeJson($outputDir . DIRECTORY_SEPARATOR . 'validation.json', $validation);
file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'crawl_url_list.txt', implode(PHP_EOL, array_map(
    static fn (array $page): string => $page['url'],
    array_values($metadataByPath)
)) . PHP_EOL);

echo 'Output directory: ' . $outputDir . PHP_EOL;
echo 'Crawled pages: ' . count($metadataByPath) . PHP_EOL;
echo 'Updated pages: ' . count($updatedPages) . PHP_EOL;
echo 'Unmapped pages: ' . count($unmappedPages) . PHP_EOL;
echo 'Crawl errors: ' . count($crawlErrors) . PHP_EOL;
echo 'Apply errors: ' . count($applyErrors) . PHP_EOL;

function crawlSite(string $baseUrl, string $userAgent): array
{
    $host = parse_url($baseUrl, PHP_URL_HOST);
    $queue = [$baseUrl];
    $queued = [$baseUrl => true];
    $visited = [];
    $pages = [];
    $errors = [];

    foreach (discoverSitemapUrls($baseUrl, $userAgent) as $sitemapUrl) {
        if (! isset($queued[$sitemapUrl])) {
            $queue[] = $sitemapUrl;
            $queued[$sitemapUrl] = true;
        }
    }

    while ($queue !== []) {
        $url = array_shift($queue);

        if (isset($visited[$url])) {
            continue;
        }

        $visited[$url] = true;
        $response = fetchUrl($url, $userAgent);

        if ($response['status'] >= 400 || $response['body'] === '') {
            $errors[] = [
                'url' => $url,
                'status' => $response['status'],
                'error' => 'HTTP error or empty body',
            ];
            continue;
        }

        $contentType = strtolower($response['content_type']);

        if (str_contains($contentType, 'xml')) {
            foreach (extractUrlsFromSitemap($response['body'], $baseUrl) as $sitemapDiscoveredUrl) {
                if (! isset($queued[$sitemapDiscoveredUrl]) && ! isset($visited[$sitemapDiscoveredUrl])) {
                    $queue[] = $sitemapDiscoveredUrl;
                    $queued[$sitemapDiscoveredUrl] = true;
                }
            }

            continue;
        }

        $metadata = extractHeadMetadata($url, $response['body']);
        $pages[] = $metadata;

        foreach (extractInternalLinks($response['body'], $baseUrl, $host) as $internalUrl) {
            if (! isset($queued[$internalUrl]) && ! isset($visited[$internalUrl])) {
                $queue[] = $internalUrl;
                $queued[$internalUrl] = true;
            }
        }
    }

    usort($pages, static fn (array $a, array $b): int => strcmp($a['url'], $b['url']));

    return [
        'pages' => $pages,
        'errors' => $errors,
    ];
}

function discoverSitemapUrls(string $baseUrl, string $userAgent): array
{
    $candidates = [
        rtrim($baseUrl, '/') . '/sitemap_index.xml',
        rtrim($baseUrl, '/') . '/sitemap.xml',
    ];
    $urls = [];

    foreach ($candidates as $candidate) {
        $response = fetchUrl($candidate, $userAgent);

        if ($response['status'] >= 400 || $response['body'] === '') {
            continue;
        }

        foreach (extractUrlsFromSitemap($response['body'], $baseUrl) as $url) {
            $urls[$url] = true;
        }
    }

    return array_keys($urls);
}

function extractUrlsFromSitemap(string $xml, string $baseUrl): array
{
    $urls = [];

    if (! preg_match_all('#<loc>\s*([^<]+)\s*</loc>#i', $xml, $matches)) {
        return [];
    }

    foreach ($matches[1] as $match) {
        $url = normalizeDiscoveredUrl(trim(html_entity_decode($match, ENT_QUOTES | ENT_HTML5, 'UTF-8')), $baseUrl);

        if ($url !== null) {
            $urls[$url] = true;
        }
    }

    return array_keys($urls);
}

function extractInternalLinks(string $html, string $baseUrl, ?string $host): array
{
    $urls = [];

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);

    if (! $loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//a[@href]') as $node) {
        if (! $node instanceof DOMElement) {
            continue;
        }

        $href = trim($node->getAttribute('href'));

        if ($href === '' || str_starts_with($href, '#')) {
            continue;
        }

        $normalized = normalizeDiscoveredUrl($href, $baseUrl, $host);

        if ($normalized !== null) {
            $urls[$normalized] = true;
        }
    }

    return array_keys($urls);
}

function normalizeDiscoveredUrl(string $url, string $baseUrl, ?string $allowedHost = null): ?string
{
    $allowedHost ??= parse_url($baseUrl, PHP_URL_HOST);
    $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if ($url === '' || preg_match('#^(mailto:|tel:|javascript:|data:)#i', $url)) {
        return null;
    }

    if (str_starts_with($url, '//')) {
        $url = 'https:' . $url;
    }

    $absolute = preg_match('#^https?://#i', $url) ? $url : resolveRelativeUrl($baseUrl, $url);
    $parts = parse_url($absolute);

    if ($parts === false) {
        return null;
    }

    $host = $parts['host'] ?? null;

    if ($host === null || $allowedHost === null || strcasecmp($host, $allowedHost) !== 0) {
        return null;
    }

    $path = $parts['path'] ?? '/';

    if (preg_match('#\.(?:jpg|jpeg|png|gif|svg|webp|avif|css|js|json|xml|pdf|zip|woff2?|ttf|eot|mp4|webm)$#i', $path)) {
        return null;
    }

    $normalizedPath = '/' . ltrim(rawurldecode($path), '/');
    $normalizedPath = preg_replace('#/+#', '/', $normalizedPath) ?? $normalizedPath;
    $normalizedPath = $normalizedPath === '' ? '/' : $normalizedPath;

    if ($normalizedPath !== '/' && str_ends_with($normalizedPath, '/')) {
        $normalizedPath = rtrim($normalizedPath, '/');
    }

    return rtrim($baseUrl, '/') . ($normalizedPath === '/' ? '/' : $normalizedPath);
}

function resolveRelativeUrl(string $baseUrl, string $relativeUrl): string
{
    if (str_starts_with($relativeUrl, '/')) {
        return rtrim($baseUrl, '/') . $relativeUrl;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($relativeUrl, '/');
}

function extractHeadMetadata(string $url, string $html): array
{
    $metadata = [
        'url' => $url,
        'title' => '',
        'description' => '',
        'keywords' => '',
        'canonical' => '',
        'robots' => '',
        'charset' => '',
        'viewport' => '',
        'og' => [],
        'twitter' => [],
    ];

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);

    if (! $loaded) {
        return $metadata;
    }

    $xpath = new DOMXPath($dom);
    $titleNode = $xpath->query('//head/title')->item(0);
    $metadata['title'] = $titleNode instanceof DOMNode ? normalizeWhitespace($titleNode->textContent) : '';

    foreach ($xpath->query('//head/meta') as $metaNode) {
        if (! $metaNode instanceof DOMElement) {
            continue;
        }

        $name = strtolower(trim($metaNode->getAttribute('name')));
        $property = strtolower(trim($metaNode->getAttribute('property')));
        $content = normalizeWhitespace($metaNode->getAttribute('content'));
        $charset = trim($metaNode->getAttribute('charset'));

        if ($charset !== '' && $metadata['charset'] === '') {
            $metadata['charset'] = $charset;
        }

        if ($name === 'description' && $metadata['description'] === '') {
            $metadata['description'] = $content;
        } elseif ($name === 'keywords' && $metadata['keywords'] === '') {
            $metadata['keywords'] = $content;
        } elseif ($name === 'robots' && $metadata['robots'] === '') {
            $metadata['robots'] = $content;
        } elseif ($name === 'viewport' && $metadata['viewport'] === '') {
            $metadata['viewport'] = $content;
        } elseif (str_starts_with($name, 'twitter:')) {
            $metadata['twitter'][$name] = $content;
        } elseif (str_starts_with($property, 'og:')) {
            $metadata['og'][$property] = $content;
        }
    }

    foreach ($xpath->query('//head/link') as $linkNode) {
        if (! $linkNode instanceof DOMElement) {
            continue;
        }

        $rel = strtolower(trim($linkNode->getAttribute('rel')));

        if ($rel === 'canonical' && $metadata['canonical'] === '') {
            $metadata['canonical'] = trim($linkNode->getAttribute('href'));
        }
    }

    ksort($metadata['og']);
    ksort($metadata['twitter']);

    return $metadata;
}

function applyMetadataToBlade(string $content, array $scraped, string $routePath): array
{
    $newline = str_contains($content, "\r\n") ? "\r\n" : "\n";
    $changes = [];
    $scrapedTitle = trim((string) ($scraped['title'] ?? ''));
    $scrapedDescription = trim((string) ($scraped['description'] ?? ''));
    $scrapedCanonical = trim((string) ($scraped['canonical'] ?? ''));
    $scrapedRobots = trim((string) ($scraped['robots'] ?? ''));
    $scrapedOg = normalizeMetaMap($scraped['og'] ?? []);

    $updated = $content;

    if ($scrapedTitle !== '') {
        $updatedTitle = "@section('title', " . bladeStringLiteral($scrapedTitle) . ')';
        $replacementCount = 0;
        $updated = preg_replace(
            "/@section\\('title',\\s*'((?:\\\\'|[^'])*)'\\)/",
            $updatedTitle,
            $updated,
            1,
            $replacementCount
        ) ?? $updated;

        if ($replacementCount > 0) {
            $changes[] = 'title';
        }
    }

    $headPattern = "/@section\\('head'\\)\\R(.*?)\\R@endsection/s";
    $updated = preg_replace_callback($headPattern, static function (array $matches) use (
        $scrapedDescription,
        $scrapedCanonical,
        $scrapedRobots,
        $scrapedOg,
        $newline,
        &$changes,
        $routePath
    ): string {
        $head = $matches[1];
        $before = $head;

        $head = removeTagsByPatterns($head, [
            '#^\s*<title\b[^>]*>.*?</title>\s*$#smi',
            '#^\s*<meta\s+name="description"[^>]*>\s*$#mi',
            '#^\s*<meta\s+property="og:[^"]+"[^>]*>\s*$#mi',
        ]);

        $head = dedupeSingleTag($head, '#<link\s+rel="canonical"\s+href="[^"]*"\s*/?>#i');
        $head = dedupeSingleTag($head, '#<meta\s+name="robots"\s+content="[^"]*"\s*/?>#i');

        $hasCanonical = preg_match('#<link\s+rel="canonical"\s+href="([^"]*)"\s*/?>#i', $head, $canonicalMatch) === 1
            && trim($canonicalMatch[1]) !== '';
        $hasRobots = preg_match('#<meta\s+name="robots"\s+content="([^"]*)"\s*/?>#i', $head, $robotsMatch) === 1
            && trim($robotsMatch[1]) !== '';

        $injections = [];

        if ($scrapedDescription !== '') {
            $injections[] = '<meta name="description" content="' . eAttr($scrapedDescription) . '">';
            $changes[] = 'description';
        }

        if (! $hasCanonical && $scrapedCanonical !== '') {
            $injections[] = '<link rel="canonical" href="' . eAttr($scrapedCanonical) . '">';
            $changes[] = 'canonical_filled';
        }

        if (! $hasRobots && $scrapedRobots !== '') {
            $injections[] = '<meta name="robots" content="' . eAttr($scrapedRobots) . '">';
            $changes[] = 'robots_filled';
        }

        foreach ($scrapedOg as $property => $value) {
            if ($value === '') {
                continue;
            }

            $injections[] = '<meta property="' . eAttr($property) . '" content="' . eAttr($value) . '">';
        }

        if ($scrapedOg !== []) {
            $changes[] = 'og';
        }

        $head = injectMarkupNearAnchor($head, $injections, $newline);
        $head = normalizeHeadSpacing($head, $newline);

        return "@section('head'){$newline}{$head}{$newline}@endsection";
    }, $updated, 1) ?? $updated;

    $changes = array_values(array_unique($changes));

    return [
        'updated_content' => $updated,
        'changes' => $changes,
        'path' => $routePath,
    ];
}

function extractLocalMetadata(string $content): array
{
    $title = '';
    $head = '';

    if (preg_match("/@section\\('title',\\s*'((?:\\\\'|[^'])*)'\\)/", $content, $titleMatch)) {
        $title = stripcslashes($titleMatch[1]);
    }

    if (preg_match("/@section\\('head'\\)\\R(.*?)\\R@endsection/s", $content, $headMatch)) {
        $head = $headMatch[1];
    }

    return [
        'title' => $title,
        'description' => firstMatch('#<meta\s+name="description"\s+content="([^"]*)"\s*/?>#i', $head),
        'canonical' => firstMatch('#<link\s+rel="canonical"\s+href="([^"]*)"\s*/?>#i', $head),
        'robots' => firstMatch('#<meta\s+name="robots"\s+content="([^"]*)"\s*/?>#i', $head),
        'og' => extractMetaMap('#<meta\s+property="(og:[^"]+)"\s+content="([^"]*)"\s*/?>#i', $head),
        'twitter' => extractMetaMap('#<meta\s+name="(twitter:[^"]+)"\s+content="([^"]*)"\s*/?>#i', $head),
        'charset' => firstMatch('#<meta\s+charset="([^"]*)"\s*/?>#i', $head),
        'viewport' => firstMatch('#<meta\s+name="viewport"\s+content="([^"]*)"\s*/?>#i', $head),
    ];
}

function validateLocalMetadata(array $pagesConfig, string $viewsRoot): array
{
    $duplicateCanonicalUrls = [];
    $canonicalOwners = [];
    $emptyFields = [];
    $duplicateTags = [];
    $missingHeadSections = [];

    foreach ($pagesConfig as $routePath => $viewName) {
        $viewPath = $viewsRoot . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $viewName) . '.blade.php';

        if (! is_file($viewPath)) {
            continue;
        }

        $content = file_get_contents($viewPath);

        if (! is_string($content) || ! preg_match("/@section\\('head'\\)\\R(.*?)\\R@endsection/s", $content, $headMatch)) {
            $missingHeadSections[] = [
                'path' => $routePath,
                'view' => $viewName,
            ];
            continue;
        }

        $metadata = extractLocalMetadata($content);
        $head = $headMatch[1];

        foreach (['description', 'canonical', 'robots'] as $key) {
            $value = trim((string) $metadata[$key]);

            if ($key !== 'robots' && $value === '') {
                $emptyFields[] = [
                    'path' => $routePath,
                    'view' => $viewName,
                    'field' => $key,
                ];
            }
        }

        if (trim($metadata['title']) === '') {
            $emptyFields[] = [
                'path' => $routePath,
                'view' => $viewName,
                'field' => 'title',
            ];
        }

        $canonical = trim((string) $metadata['canonical']);

        if ($canonical !== '') {
            if (isset($canonicalOwners[$canonical])) {
                $duplicateCanonicalUrls[] = [
                    'canonical' => $canonical,
                    'paths' => [$canonicalOwners[$canonical], $routePath],
                ];
            } else {
                $canonicalOwners[$canonical] = $routePath;
            }
        }

        foreach ([
            'description' => '#<meta\s+name="description"\s+content="[^"]*"\s*/?>#i',
            'canonical' => '#<link\s+rel="canonical"\s+href="[^"]*"\s*/?>#i',
            'robots' => '#<meta\s+name="robots"\s+content="[^"]*"\s*/?>#i',
        ] as $tagName => $pattern) {
            preg_match_all($pattern, $head, $matches);

            if (count($matches[0]) > 1) {
                $duplicateTags[] = [
                    'path' => $routePath,
                    'view' => $viewName,
                    'tag' => $tagName,
                    'count' => count($matches[0]),
                ];
            }
        }
    }

    return [
        'duplicate_canonical_urls' => $duplicateCanonicalUrls,
        'empty_fields' => $emptyFields,
        'duplicate_tags' => $duplicateTags,
        'missing_head_sections' => $missingHeadSections,
    ];
}

function fetchUrl(string $url, string $userAgent): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => ['Accept-Language: en-US,en;q=0.9'],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
        'content_type' => $contentType,
    ];
}

function normalizePath(string $url, string $baseUrl): string
{
    $path = parse_url($url, PHP_URL_PATH) ?: '/';
    $path = '/' . ltrim(rawurldecode($path), '/');
    $path = preg_replace('#/+#', '/', $path) ?? $path;

    if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
    }

    return $path === '' ? '/' : $path;
}

function normalizeWhitespace(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

    return trim($value);
}

function normalizeMetaMap(array $map): array
{
    $normalized = [];

    foreach ($map as $key => $value) {
        $key = strtolower(trim((string) $key));
        $value = trim((string) $value);

        if ($key === '') {
            continue;
        }

        $normalized[$key] = $value;
    }

    ksort($normalized);

    return $normalized;
}

function injectMarkupNearAnchor(string $head, array $markupLines, string $newline): string
{
    $markupLines = array_values(array_filter($markupLines, static fn (string $line): bool => trim($line) !== ''));

    if ($markupLines === []) {
        return $head;
    }

    $injection = implode($newline, $markupLines);
    $anchors = [
        '<meta http-equiv="X-UA-Compatible" content="IE=edge">',
        '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">',
        '<meta charset="UTF-8">',
    ];

    foreach ($anchors as $anchor) {
        if (str_contains($head, $anchor)) {
            return preg_replace(
                '/' . preg_quote($anchor, '/') . '\R*/',
                $anchor . $newline . $newline . $injection . $newline . $newline,
                $head,
                1
            ) ?? $head;
        }
    }

    return $injection . $newline . $newline . ltrim($head);
}

function normalizeHeadSpacing(string $head, string $newline): string
{
    $head = preg_replace("/(?:\r?\n){3,}/", $newline . $newline, $head) ?? $head;

    return trim($head);
}

function removeTagsByPatterns(string $head, array $patterns): string
{
    foreach ($patterns as $pattern) {
        $head = preg_replace($pattern, '', $head) ?? $head;
    }

    return $head;
}

function dedupeSingleTag(string $head, string $pattern): string
{
    $seen = false;

    return preg_replace_callback($pattern, static function (array $matches) use (&$seen): string {
        if ($seen) {
            return '';
        }

        $seen = true;

        return $matches[0];
    }, $head) ?? $head;
}

function firstMatch(string $pattern, string $subject): string
{
    return preg_match($pattern, $subject, $matches) === 1
        ? html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : '';
}

function extractMetaMap(string $pattern, string $subject): array
{
    $map = [];

    if (! preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER)) {
        return [];
    }

    foreach ($matches as $match) {
        $map[$match[1]] = html_entity_decode(trim($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    ksort($map);

    return $map;
}

function bladeStringLiteral(string $value): string
{
    return "'" . str_replace(['\\', '\''], ['\\\\', '\\\''], $value) . "'";
}

function eAttr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
}

function ensureDir(string $path): void
{
    if (! is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function writeJson(string $path, array $data): void
{
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
    );
}

function relativePath(string $projectRoot, string $path): string
{
    return ltrim(str_replace($projectRoot, '', $path), '\\/');
}
