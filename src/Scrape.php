<?php

namespace App;

require 'vendor/autoload.php';
require 'helpers.php';

use Symfony\Component\DomCrawler\Crawler;

class Scrape
{
    private array $products = [];
    private string $scrapeUrl;
    private array $pages = [];

    public function __construct()
    {
        $this->scrapeUrl = $_ENV['SCRAPE_URL'];
    }

    private function setPages()
    {
        $crawlerDocument = ScrapeHelper::fetchDocument($this->scrapeUrl);
        $nodes = $crawlerDocument->filter('#pages .flex a')->extract(['href']);
        foreach ($nodes as $node) {
            if (preg_match('/(\d+)$/', $node, $matches)) {
                $this->pages[] = $matches[1];
            }
        }
    }

    private function fetchPage($page = '')
    {
        $this->scrapeUrl = preg_replace('/\?page=\d+$/', '', $this->scrapeUrl);
        $url = ($page == '') ? $this->scrapeUrl : $this->scrapeUrl . "?page={$page}";
        $crawlerDocument = ScrapeHelper::fetchDocument($url);
        $products = $crawlerDocument->filter('.product');

        $result = [];
        
        $products->each(function (Crawler $product) use (&$result) {
            $capacity = $product->filter('.product-capacity')->text();
            $title = $product->filter('.product-name')->text() . " " . $capacity;
            $price = trim($product->filter('.my-8.text-center.text-lg')->text(), '£');
            $image = $product->filter('img')->attr('src');
            $availability = $product->filter('.my-4.text-sm.block.text-center')->first()->text();
            $shippingText = $product->filter('.my-4.text-sm.block.text-center')->last()->text();
            $colours = $product->filter('[data-colour]')->extract(['data-colour']);

            $product = new Product($capacity, $title, $price, $image, $availability, $shippingText, $colours);

            foreach($colours as $colour) {
                $result[] = [
                    'title' => $title,
                    'price' => (float) $price,
                    'imageUrl' => $product->getImage(),
                    'capacityMB' => $product->getCapacityInMb(),
                    'colour' => $colour,
                    'availabilityText' => $product->getavailabilityText(),
                    'isAvailable' => $product->getIsAvailable(),
                    'shippingText' => $shippingText,
                    'shippingDate' => $product->getShippingDate()
                ];
            }
        });

        return $result;

    }

    public function run(): void
    {
        $crawledData = [];
        $this->setPages();
        if(sizeof($this->pages) < 2) { // '0' value: the page have a single page with no pagination at all, '1': the page could have other pages, but currently data is enough to fill only 1 page
            $crawledProducts = $this->fetchPage();
            $crawledData = array_unique($crawledProducts, SORT_REGULAR);
            $crawledData = json_encode($crawledData, JSON_PRETTY_PRINT);
            file_put_contents('output.json', $crawledData);
        } else {
            foreach ($this->pages as $page) {
                $crawledProducts = $this->fetchPage($page);
                $crawledData = array_merge($crawledData, $crawledProducts);
                $crawledData = array_unique($crawledData, SORT_REGULAR);
            }
            $crawledData = json_encode($crawledData, JSON_PRETTY_PRINT);
            file_put_contents('output.json', $crawledData);
        }
    }
}

$scrape = new Scrape();
$scrape->run();
