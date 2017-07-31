<?php

namespace Parser;

use Symfony\Component\DomCrawler\Crawler;

class EventsParser
{
    public function getMatchesByLeague($league)
    {
        $leaguePage = file_get_contents($league);

        $crawler = new Crawler($leaguePage);

        $eventUrls = $crawler->filter('td.table-participant')->each(function(Crawler $node, $i){
            return $node->children()->attr('href');
        });

        var_dump($eventUrls);

        foreach ($eventUrls as $url) {
            //todo: push url to queue
        }

        $coefficientParser = new OddsportalCoefficientParser();
        $coefficientParser->parse($eventUrls[0]);
    }
}