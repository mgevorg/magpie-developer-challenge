<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    public static function fetchDocument(string $url): Crawler
    {
        $client = new Client(['verify' => boolval($_ENV['CLIENT_VERIFY'])]);
        $response = $client->get($url);

        return new Crawler($response->getBody()->getContents(), $url);
    }
}
