<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GeneratedSiteStructureTest extends TestCase
{
    public function test_generated_site_pages_config_matches_page_view_inventory(): void
    {
        $pageViews = collect(File::allFiles(resource_path('views/pages')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->reject(fn ($file) => str_contains($file->getRelativePathname(), 'partials' . DIRECTORY_SEPARATOR))
            ->map(function ($file): string {
                return 'pages.' . str_replace(
                    [DIRECTORY_SEPARATOR, '.blade.php'],
                    ['.', ''],
                    $file->getRelativePathname()
                );
            })
            ->sort()
            ->values()
            ->all();

        $configuredViews = collect(config('site_pages'))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($pageViews, $configuredViews);
    }

    public function test_generated_site_pages_only_reference_existing_views(): void
    {
        foreach (config('site_pages', []) as $path => $view) {
            $this->assertTrue(
                view()->exists($view),
                "Configured site page [{$path}] points to missing view [{$view}]."
            );
        }
    }
}
