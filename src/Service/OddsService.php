<?php


namespace Service;


class OddsService
{
    //48 - id of bet365 bookie
    public function getBet365Odds($event)
    {
//        if ($id) {
//            $regex = new Regex("/.*$id.*/", 'i');
//            $odds = $this->mongoClient->selectCollection(DB_NAME, 'wettportalOdds')->findOne(['link' => $regex]);
//        }
//        else {
//            $odds = $this->mongoClient->selectCollection(DB_NAME, 'wettportalOdds')->findOne([], ['sort' => ['$natural' => -1]]);
//        }

        $way = [];
        foreach ($event->odds->{'3way'} as $bookie) {
            if ($bookie->bookie_id == 48) {
                $way['home'] = $bookie['home'];
                $way['draw'] = $bookie['draw'];
                $way['away'] = $bookie['away'];
            }
        }
        $event->odds->{'3way'} = $way;

        $way = [];
        foreach ($event->odds->correctScore as $bookie) {
            if ($bookie->bookie_id == 48) {
                $way[] = ['score' => $bookie['score'], 'price' => $bookie['price']];
            }
        }
        $event->odds->correctScore = $way;

        $way = [];
        foreach ($event->odds->overUnder as $bookie) {
            if ($bookie->bookie_id == 48) {
                $way[] = ['goalline' => $bookie['goalline'], 'over' => $bookie['over'], 'under' => $bookie['under']];
            }
        }
        $event->odds->overUnder = $way;

        return $event;
    }

    public function getOddsForScore($odds, $score)
    {
        foreach ($odds as $odd) {
            $odd = (array) $odd;
            if ($odd['score'] == $score) {
                return $odd['price'];
            }
        }

        //todo: calculate and return average
        return 0;
    }

    public function getNewBet365OddsForScore($odds, $score)
    {
        foreach ($odds as $odd) {
            if ($odd->score == $score && $odd->bookie_id == 48) {
                return $odd->price;
            }
        }

        return 0;
    }

    public function getOddsForTotal($odds, $total)
    {
        foreach ($odds as $odd) {
            if ($odd['goalline'] == $total) {
                return $odd;
            }
        }

        //todo: calculate and return average
        return 0;
    }
}