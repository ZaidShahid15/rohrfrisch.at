<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$missingUrlsPath = $projectRoot . DIRECTORY_SEPARATOR . 'missing-live-urls.txt';
$cloneScript = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'clone_rohrfrisch.php';

$argv = [$cloneScript];

foreach (file($missingUrlsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $url) {
    $path = parse_url(trim($url), PHP_URL_PATH);

    if (is_string($path) && $path !== '') {
        $argv[] = '--path=' . $path;
    }
}

require $cloneScript;
