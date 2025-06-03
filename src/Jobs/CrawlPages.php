<?php

namespace MrAjay\Localizer\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;

class CrawlPages
{
    public function handle(): array
    {
        $baseUrl = config('app.url');
        $pages = config('localizer.pages', ['/']);
        $client = new Client(['timeout' => 10]);
        $concurrency = 5;
        $allTexts = [];

        // Helper: Map page URL path to Blade file path (customize as needed)
        $mapPageToBlade = function (string $page) {
            if ($page === '/') {
                return resource_path('views/welcome.blade.php');
            }
            // Replace slashes with directory separators, append .blade.php
            $bladeFile = resource_path('views/' . ltrim($page, '/') . '.blade.php');
            return $bladeFile;
        };

        $requests = function ($pages) use ($client, $baseUrl) {
            foreach ($pages as $page) {
                $url = $baseUrl . $page;
                yield fn() => $client->getAsync($url);
            }
        };

        $pool = new Pool($client, $requests($pages), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use (&$allTexts, $pages, $mapPageToBlade) {
                $page = $pages[$index];
                $crawler = new Crawler($response->getBody()->getContents());

                // Remove script and style tags
                $crawler->filter('script, style')->each(
                    fn($node) =>
                    $node->getNode(0)?->parentNode->removeChild($node->getNode(0))
                );

                // Extract visible texts for this page
                $pageTexts = [];
                $crawler->filter('body')->each(function (Crawler $node) use (&$pageTexts) {
                    foreach ($node->filterXPath('//text()[normalize-space()]') as $domNode) {
                        $text = trim($domNode->textContent);
                        if ($text && strlen($text) > 1) {
                            $pageTexts[$text] = $text;
                        }
                    }
                });

                // Merge with global allTexts
                $allTexts += $pageTexts;

                // Update blade file for this page
                $bladeFile = $mapPageToBlade($page);

                if (File::exists($bladeFile)) {
                    $backupFile = $bladeFile . '.backup';

                    // Backup original blade file if backup doesn't exist
                    if (!File::exists($backupFile)) {
                        File::copy($bladeFile, $backupFile);
                    }

                    try {
                        $bladeContent = File::get($bladeFile);

                        foreach ($pageTexts as $text) {
                            $escapedText = preg_quote($text, '/');

                            // Skip if text already inside {{ __('...') }}
                            $patternAlreadyWrapped = "/\{\{\s*__\(\s*['\"]" . preg_quote($text, '/') . "['\"]\s*\)\s*\}\}/u";

                            if (preg_match($patternAlreadyWrapped, $bladeContent)) {
                                // Already localized, skip replacing this text
                                continue;
                            }

                            // Replace exact text occurrences with {{ __('text') }}
                            // But only if not already inside Blade directive like @ or {{ }}
                            // We do a negative lookbehind for @ or { before the text (naive)
                            $bladeContent = preg_replace_callback(
                                "/(?<![@{])($escapedText)/u",
                                fn($m) => "{{ __('" . addslashes($m[1]) . "') }}",
                                $bladeContent
                            );
                        }

                        File::put($bladeFile, $bladeContent);

                        // Delete backup after successful update
                        if (File::exists($backupFile)) {
                            File::delete($backupFile);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Error updating blade file {$bladeFile}: " . $e->getMessage());

                        // Restore backup on error
                        if (File::exists($backupFile)) {
                            File::copy($backupFile, $bladeFile);
                        }
                    }
                } else {
                    Log::warning("Blade file not found for page {$page}: {$bladeFile}");
                }
            },
            'rejected' => function ($reason, $index) use ($pages, $baseUrl) {
                Log::warning("Failed to crawl: " . $baseUrl . $pages[$index]);
            },
        ]);

        $pool->promise()->wait();

        // Save English texts in en.json
        $langPath = resource_path('lang');
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        $enJsonPath = $langPath . '/en.json';
        file_put_contents($enJsonPath, json_encode($allTexts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $allTexts;
    }
}
