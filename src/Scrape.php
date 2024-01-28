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
            
            if (preg_match('/^(\d+)\s*GB$/i', $capacity, $matches)) {
                $size = (int)$matches[1];
                $size *= 1024;
                $capacity = $size;
            } elseif (preg_match('/(\d+)\s?mb$/i', $capacity, $matches)) {
                $capacity = (int)$matches[1];
            } else {
                $capacity = '';
            }

            $availabilityText = (str_contains($availability, 'In Stock')) ? 'In Stock' : (str_contains($availability, 'Out of Stock') ? 'Out of Stock' : 'Invalid Data');
            $isAvailable = (str_contains($availability, 'In Stock')) ? true : false;

            $patternwithDateSuffix = '~\b(\d{1,2}(st|nd|rd|th)\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})\b~';
            $patternwithoutDateSuffix = '~\b(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4})\b~';

            if (preg_match($patternwithDateSuffix, $shippingText, $matches) || preg_match($patternwithoutDateSuffix, $shippingText, $matches)) {
                $date = $matches[0];
                $parsedDate = date_create_from_format('jS M Y', $date);
                $shippingDate = $parsedDate->format('d-m-Y');
            } else {
                $shippingDate = '';
            }

            $image = stripslashes($image);
            if (substr($image, 0, 3) === '../') {
                $image = substr($image, 3);
            }
            
            $image = "https://www.magpiehq.com/developer-challenge/" . $image;

            foreach($colours as $colour) {
                $result[] = [
                    'title' => $title,
                    'price' => $price,
                    'imageUrl' => $image,
                    'capacityMB' => $capacity,
                    'colour' => $colour,
                    'availabilityText' => $availabilityText,
                    'isAvailable' => $isAvailable,
                    'shippingText' => $shippingText,
                    'shippingDate' => $shippingDate
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
            file_put_contents('output.json', json_encode($crawledProducts, JSON_PRETTY_PRINT));
        } else {
            foreach ($this->pages as $page) {
                $crawledProducts = $this->fetchPage($page);
                $crawledData = array_merge($crawledData, $crawledProducts);
                file_put_contents('output.json', json_encode($crawledData, JSON_PRETTY_PRINT));
            }
        }
    }
}

$scrape = new Scrape();
$scrape->run();
