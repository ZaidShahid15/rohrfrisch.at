<?php

declare(strict_types=1);

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
