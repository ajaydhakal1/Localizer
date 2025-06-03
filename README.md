# Localizer Package for Laravel

A Laravel package to automatically extract translatable texts from your Blade views or live pages, inject Laravel localization strings (`{{ __('text') }}`), and translate them into multiple languages using LibreTranslate.

---

## Features

- Crawl specified pages (local Blade views or live URLs) to extract visible texts.
- Skip scripts, styles, and already localized texts.
- Automatically wrap extracted texts in Blade templates with `{{ __('text') }}`.
- Backup and restore Blade files safely during localization injection.
- Translate extracted texts into configured languages using LibreTranslate.
- Artisan commands to automate crawling and translation.

---

## Requirements

- Laravel 8.x or higher  
- PHP 8.0 or higher  
- [LibreTranslate](https://libretranslate.com/) running locally or accessible via URL  
- Composer

---

## Installation

### Require the package via Composer

```bash
composer require mr-ajay/localizer
```

### Publish the config file

```bash
php artisan vendor:publish --provider="MrAjay\Localizer\LocalizerServiceProvider" --tag="config"
```

### Configure `.env` file

Add your LibreTranslate API URL:

```env
LIBRETRANSLATE_URL=http://localhost:5000
```

---

## Configuration

Edit the published config file `config/localizer.php`:

```php
return [
    // Pages to crawl and extract texts from
    'pages' => [
        '/',                 // homepage
        '/about',            // about page
        // add other URL paths or Blade relative paths here
    ],

    // Target languages for translation (ISO codes)
    'languages' => ['ja', 'fr', 'ko'],

    // LibreTranslate API URL
    'api_url' => env('LIBRETRANSLATE_URL', 'http://localhost:5000'),
];
```

---

## Usage

### Crawl and Translate All Pages

Run the main Artisan command to crawl all pages and generate translation files:

```bash
php artisan localize:all
```

This command will:

- Crawl all configured pages asynchronously.
- Extract visible texts, excluding scripts and styles.
- Backup and update your Blade files to wrap texts in `{{ __('text') }}` unless already localized.
- Save English base texts in `resources/lang/en.json`.
- Translate texts to configured languages and save JSON files in `resources/lang/{lang}.json`.

---
### Crawl and Translate Specific Pages

Run this Artisan command to crawl specific pages specified in config and generate translation files:

```bash
php artisan localize
```

This command will:

- Crawl specific pages asynchronously.
- Extract visible texts, excluding scripts and styles.
- Backup and update your Blade files to wrap texts in `{{ __('text') }}` unless already localized.
- Save English base texts in `resources/lang/en.json`.
- Translate texts to configured languages and save JSON files in `resources/lang/{lang}.json`.

---

## How It Works

- **CrawlPages Job:** Uses Guzzle HTTP client and Symfony DomCrawler to fetch page content and extract texts.
- **Backup Blade files:** Before modifying Blade templates, backups are made. On error, backups are restored.
- **Inject localization strings:** Texts are replaced with `{{ __('text') }}` only if not already localized.
- **TranslateTexts Job:** Sends texts to LibreTranslate API to generate translated JSON files.
- **Progress and status** are output in the console.

---

## Customizing Page to Blade File Mapping

The package attempts to map URL paths to Blade files automatically. You can customize this logic inside the `CrawlPages` job:

```php
$mapPageToBlade = function (string $page) {
    if ($page === '/') {
        return resource_path('views/welcome.blade.php');
    }
    return resource_path('views/' . ltrim($page, '/') . '.blade.php');
};
```

Adjust this mapping if your Blade files are structured differently.

---

## Handling Blade Backups

- Original Blade files are backed up with `.backup` extension before localization injection.
- If an error occurs during modification, the original file is restored from backup.
- After successful update, the backup is deleted.
- You can manually restore files from backup if needed.

---

## Translation Progress Display

Translations show progress as a percentage in the console, e.g.:

```
Translated 80% to ja
```

---

## Troubleshooting

- **No pages specified error:** Ensure `pages` array is defined in `config/localizer.php`.
- **Translation API errors:** Verify your LibreTranslate server is running and reachable via the configured URL.
- **Blade file not found warnings:** Make sure your page paths map correctly to Blade files.

---

## Extending the Package

- Add support for more languages in `config/localizer.php`.
- Customize the DOM selectors or extraction logic in `CrawlPages`.
- Add support for other translation APIs by modifying `TranslateTexts`.

---

## License

MIT License — feel free to use and modify.

---

## Contributing

Contributions, issues, and feature requests are welcome!  
Feel free to check the issues page.

---

## Author

Created by **Ajay Dhakal** — [GitHub Profile](https://github.com/ajaydhakal1)

---

**Happy localizing! 🌐🚀**
