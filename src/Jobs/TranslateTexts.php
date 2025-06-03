<?php

namespace MrAjay\Localizer\Jobs;

use Illuminate\Support\Facades\Http;

class TranslateTexts
{
    public function handle(array $texts)
    {
        $languages = config('localizer.languages');
        $apiUrl = config('localizer.api_url');

        foreach ($languages as $lang) {
            $translations = [];
            $existingPath = resource_path("lang/{$lang}.json");

            // Load existing translations if available
            $existingTranslations = [];
            if (file_exists($existingPath)) {
                $json = file_get_contents($existingPath);
                $existingTranslations = json_decode($json, true) ?: [];
            }

            $total = count($texts);
            $count = 0;

            foreach ($texts as $key => $text) {
                // Skip translation if already translated and non-empty
                if (isset($existingTranslations[$key]) && trim($existingTranslations[$key]) !== '') {
                    $translations[$key] = $existingTranslations[$key];
                } else {
                    $response = Http::post("$apiUrl/translate", [
                        'q' => $text,
                        'source' => 'en',
                        'target' => $lang,
                        'format' => 'text',
                    ]);

                    $translations[$key] = $response->json('translatedText') ?? $text;
                }

                $count++;
                $percent = round(($count / $total) * 100);
                echo "Translated {$percent}% to {$lang}\r";
            }

            // Print newline after finishing language translation
            echo "\n";

            file_put_contents($existingPath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
