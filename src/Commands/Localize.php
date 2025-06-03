<?php

namespace MrAjay\Localizer\Commands;

use Illuminate\Console\Command;
use MrAjay\Localizer\Jobs\CrawlPages;
use MrAjay\Localizer\Jobs\TranslateTexts;

class Localize extends Command
{
    protected $signature = 'localize';
    protected $description = 'Translate pages configured in config/localizer.php';

    public function handle()
    {
        $paths = config('localizer.pages', []);

        if (empty($paths)) {
            $this->error('No pages specified. Define pages in config/localizer.php.');
            return 1;
        }

        config(['localizer.pages' => $paths]);

        $this->info('Crawling configured pages...');
        $texts = (new CrawlPages())->handle();

        $this->info('Translating texts...');
        (new TranslateTexts())->handle($texts);

        $this->info('Translation completed.');
        return 0;
    }
}
