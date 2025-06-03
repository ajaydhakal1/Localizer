<?php

namespace MrAjay\Localizer\Commands;

use Illuminate\Console\Command;
use MrAjay\Localizer\Jobs\CrawlPages;
use MrAjay\Localizer\Jobs\TranslateTexts;

class LocalizeAll extends Command
{
    protected $signature = 'localize:all';
    protected $description = 'Translate all configured pages into all specified languages';

    public function handle()
    {
        $this->info('Crawling pages...');
        $crawler = new CrawlPages();
        $texts = $crawler->handle();

        $this->info('Translating texts...');
        $translator = new TranslateTexts();
        $translator->handle($texts);

        $this->info('Translation completed.');
    }
}
