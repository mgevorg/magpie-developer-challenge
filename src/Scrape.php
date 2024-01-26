<?php

namespace App;

require 'vendor/autoload.php';
require 'helpers.php';

class Scrape
{
    private array $products = [];

    public function run(): void
    {
        $crawlerDocument = ScrapeHelper::fetchDocument($_ENV['SCRAPE_URL']);
        file_put_contents('output.json', json_encode($this->products));
    }
}

$scrape = new Scrape();
$scrape->run();
