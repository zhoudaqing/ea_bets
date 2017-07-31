<?php

namespace Parser;


use Symfony\Component\DomCrawler\Crawler;

/**
 * For search by wettportal
 * @deprecated
 */
class WettportalSearchParser
{
    const ROOT_URL = 'https://uk.wettportal.com';
    const SEARCH_PATH = '/searchresults/';
    const ODDS_COMPARISON_SECTION_TEXT = 'odds comparison';

    public function parse($name)
    {
        //http://uk.wettportal.com/Soccer/Kuwait/Crown_Prince_Cup/Al-Qadsia_-_Yarmouk_2101323.html
        //http://uk.wettportal.com/lib/ajax/getOddsJSON.php?partner=wettportal&lang=en&period=fulltime&betgame=&eventmode=upcoming&event_id=2101323&bettype=overunder

        $searchResultPage = $this->getSearchResultPage($name);

        $crawler = new Crawler($searchResultPage);
        $mainContent = $crawler->filter('#oc_content');

        if (!$mainContent->filter('h2')->count()) {
            echo 'Event not found';
            return false;
        }

        $oddsComparisonSectionText = $mainContent->filter('h2')->first()->text();

        if ($oddsComparisonSectionText !== self::ODDS_COMPARISON_SECTION_TEXT) {
            echo 'Odds for event not found';
            return false;
        }

        $tournament = $this->getTournament($mainContent);
        $link       = $this->getLink($mainContent);

        return $link;
    }

    private function getSearchResultPage($name)
    {
        $ch = curl_init(self::ROOT_URL . self::SEARCH_PATH);

        $data = ['pattern' => $name];

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,                  // method: post
            CURLOPT_POSTFIELDS     => $data,                 // post data
            CURLOPT_RETURNTRANSFER => true,                  // return web page
            CURLOPT_HEADER         => false,                 // don't return headers
            CURLOPT_USERAGENT      => "Chrome/54.0.2840.98",
            CURLOPT_CONNECTTIMEOUT => 120,                   // timeout on connect
            CURLOPT_TIMEOUT        => 120,                   // timeout on response
            CURLOPT_MAXREDIRS      => 2,                     // stop after 10 redirects
            CURLOPT_SSL_VERIFYHOST => 0,                     // don't verify ssl
            CURLOPT_SSL_VERIFYPEER => false,                 //

            CURLOPT_REFERER        => self::ROOT_URL . self::SEARCH_PATH
        ]);

        $pageContent = curl_exec($ch);

        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return $pageContent;
    }

    /**
     * @param Crawler $mainContent
     *
     * @return array
     */
    private function getTournament(Crawler $mainContent): array
    {
        $tournament = $mainContent->filter('div.content-box')->filter('h2')->first()->text();

        $tournament = explode('|', $tournament, 3); #Soccer | Israel | Toto Cup Premier

        return [
            'sport'      => trim($tournament[0]),
            'country'    => trim($tournament[1]),
            'tournament' => trim($tournament[2])
        ];
    }

    private function getLink(Crawler $mainContent): string
    {
        $path = $mainContent->filter('div.content-box')->filter('a')->attr('href');

        return self::ROOT_URL . $path;
    }
}