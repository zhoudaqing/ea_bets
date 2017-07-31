<?php

namespace Parser;

class OddsportalCoefficientParser implements ParserInterface
{
    const EVENT_URL_TEMPLATE = 'http://www.oddsportal.com%s';

    /**
     * @example http://fb.oddsportal.com/feed/match/1-1-8v5sbO1c-1-2-yj1aa.dat?_=1481077159778
     * where 8v5sbO1c - id of event (from url)
     * 1-2 - id of scope (ajax url by clicking tab)
     * yj1aa - hash from js
     * 1481077159778 - timestamp
     */
    const ODDS_SCOPE_URL_TEMPLATE = 'http://fb.oddsportal.com/feed/match/1-1-%s-%s-%s.dat?_=%s';

    public function parse(string $url)
    {
        $url = sprintf(self::EVENT_URL_TEMPLATE, $url);

        $eventPageData = file_get_contents($url);

        $matches = [];

        preg_match('/,"xhash":"(.*)","xhashf"/U', $eventPageData, $matches);

        $hash = urldecode($matches[1]);

        $matches = [];

        preg_match('/-(.{8})\/?$/', $url, $matches);

        $eventHash = $matches[1];

        $scopeUrl = sprintf(self::ODDS_SCOPE_URL_TEMPLATE, $eventHash, '1-2', $hash, time());

        $scopeData = $this->getScopeData($scopeUrl, $url);

        $matches = [];

        preg_match('/(\{.*\})/', $scopeData, $matches);

        $scopeData = $matches[0];

        $scopeJsonObject = json_decode($scopeData);

        $oddsKey = sprintf('E-%s-0-0-0', '1-2');
        var_dump($scopeJsonObject->d->oddsdata->back->{$oddsKey}->odds);

        #$scopeJsonObject->d->oddsdata->back->{'E-0-1-0-0-0'}->odds;

        return 'parsed data';
    }

    private function getScopeData(string $scopeUrl, string $referer)
    {
        $ch = curl_init($scopeUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,                  // return web page
            CURLOPT_HEADER         => false,                 // don't return headers
            CURLOPT_USERAGENT      => "Chrome/54.0.2840.98",
            CURLOPT_CONNECTTIMEOUT => 120,                   // timeout on connect
            CURLOPT_TIMEOUT        => 120,                   // timeout on response
            CURLOPT_MAXREDIRS      => 2,                     // stop after 10 redirects
            CURLOPT_SSL_VERIFYHOST => 0,                     // don't verify ssl
            CURLOPT_SSL_VERIFYPEER => false,                 //
            CURLOPT_REFERER        => $referer               //
        ]);

        $scopeData = curl_exec($ch);
        #error case: globals.jsonpCallback('/feed/match/1-1-8v5sbO1c-1-2-yj901.dat?_=1481154639', {'e':'404'});

        $error = curl_error($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return $scopeData;
    }

}

#curl 'http://fb.oddsportal.com/feed/match/1-1-8v5sbO1c-1-2-yje09.dat?_=1481153395817' -H 'Accept-Encoding: gzip, deflate, sdch' -H 'Accept-Language: en-US,en;q=0.8,ru;q=0.6,sr;q=0.4' -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.98 Safari/537.36' -H 'Accept: */*' -H 'Referer: http://www.oddsportal.com/soccer/england/premier-league/watford-everton-8v5sbO1c/' -H 'Cookie: __utmt=1; __utma=136771054.995764837.1481065644.1481080227.1481153371.6; __utmb=136771054.6.8.1481153393654; __utmc=136771054; __utmz=136771054.1481065644.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)' -H 'Connection: keep-alive' --compressed
#curl 'http://fb.oddsportal.com/feed/match/1-1-8v5sbO1c-1-2-yje09.dat?_=1481153395' -H 'Referer: http://www.oddsportal.com/soccer/england/premier-league/watford-everton-8v5sbO1c/'
#Referer: http://www.oddsportal.com/soccer/england/premier-league/watford-everton-8v5sbO1c/
#Referer: http://www.oddsportal.com/soccer/england/premier-league/watford-everton-8v5sbO1c/
