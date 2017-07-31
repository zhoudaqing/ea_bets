<?php

namespace Parser;

use Symfony\Component\DomCrawler\Crawler;

class BetstudyPredictionsParser
{
    const PREDICTIONS_PAGE_URL = 'http://www.betstudy.com/predictions/';

    public function parse()
    {
        $predictionsHtml = file_get_contents(self::PREDICTIONS_PAGE_URL);

        if (!$predictionsHtml) {
            echo 'Can\'t load predictions page';
            return [];
        }

        $crawler = new Crawler($predictionsHtml);
        #.soccer-table > tbody:nth-child(1) > tr:nth-child(6) > td:nth-child(7)

        $nodes = $crawler->filter('.soccer-table')->filter('tr')->each(function(Crawler $node, $i){

            $tds = $node->filter('td');

            if (!$tds->count()) {
                return [];
            }

            $data = [
                'timestamp' => $tds->eq(0)->filter('span')->attr('data-value'),
                'link' => $tds->eq(1)->filter('a')->eq(1)->attr('href'),
                'title' => $this->clear_tild($tds->eq(1)->filter('a')->eq(1)->text()),
                '1' => $tds->eq(2)->text(),
                'X' => $tds->eq(3)->text(),
                '2' => $tds->eq(4)->text(),
                'over' => $tds->eq(5)->text(),
                'under' => $tds->eq(6)->text(),
                'prediction' => $tds->eq(7)->text(),
            ];

            return $data;
        });

        return $nodes;
    }
    function clear_tild($str) {
        return mb_convert_encoding(preg_replace('/&(.)(tilde|uml|acute|circ);/', "$1", mb_convert_encoding(trim($str), 'HTML-ENTITIES', 'utf-8')), 'utf-8', 'HTML-ENTITIES');
    }
}