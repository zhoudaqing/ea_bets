<?php

namespace Parser;

use Symfony\Component\DomCrawler\Crawler;

class WettportalResultParser {

    const ROOT_URL = 'http://www.totalcorner.com/match/today/ended';

    public function getResult($url) {

        $match_id = explode('-', $url);
        if (!isset($this->xpath)) {
            $page        = $this->getResultPage();
            $doc         = new \DOMDocument();
            @$doc->loadHTML($page);
            $this->xpath      = new \DomXPath($doc);
        }
        $div = $this->xpath->query('//tr[@data-match_id="' . $match_id[3] . '"]/td[@class="text-center match_goal"]');
        //var_dump($div);
        if ($div->length > 0) {
            return $div->item(0)->textContent;
        }
        return null;
    }

    private function getResultPage() {
        $ch = curl_init(self::ROOT_URL);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER         => false, // don't return headers
            CURLOPT_USERAGENT      => "Chrome/54.0.2840.98",
            CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
            CURLOPT_TIMEOUT        => 120, // timeout on response
            CURLOPT_MAXREDIRS      => 2, // stop after 10 redirects
            CURLOPT_SSL_VERIFYHOST => 0, // don't verify ssl
            CURLOPT_SSL_VERIFYPEER => false, //
            CURLOPT_REFERER        => self::ROOT_URL
        ]);

        $pageContent = curl_exec($ch);

        $error = curl_error($ch);
        $info  = curl_getinfo($ch);

        curl_close($ch);

        return $pageContent;
    }

}
