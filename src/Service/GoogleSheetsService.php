<?php

namespace Service;

use GoogleSheets\Sheets;

class GoogleSheetsService {

    private $oddsService;
    private $spreadsheetId;
    private $sheetId;

    /**
     * @var Sheets
     */
    private $sheet;

    public function __construct(OddsService $oddsService, $spreadsheetId, $sheetId) {
        $this->oddsService   = $oddsService;
        $this->spreadsheetId = $spreadsheetId;
        $this->sheetId       = $sheetId;

        $this->initSheet();
    }

    public function addEventToSheet($event) {
        $values = new \Google_Service_Sheets_ValueRange();

        $teams = explode(' - ', $event['title'], 2);
        //$odds  = $this->oddsService->getBet365Odds($event);

        $totals = $event['odds']->underover->{'2.5'}; //$this->oddsService->getOddsForTotal($odds->odds->overUnder, '2.5');
        $league = '';
        if (isset($event['country']) && isset($event['league'])) {
            $league = $event['country'] . ' ' . $event['league'];
        }

        $data = [
            $event['betstudyId'],
            date('d/m/Y', $event['timestamp']),
            $league,
            $teams[0],
            $teams[1],
            (float) $event['odds']->correctScore->{'2-1'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '2-1'),
            (float) $event['odds']->correctScore->{'1-0'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '1-0'),
            (float) $event['odds']->correctScore->{'2-0'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '2-0'),
            (float) $event['odds']->correctScore->{'0-0'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '0-0'),
            (float) $event['odds']->correctScore->{'1-1'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '1-1'),
            (float) $event['odds']->correctScore->{'0-1'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '0-1'),
            (float) $event['odds']->correctScore->{'0-2'}->bet_slip, //$this->oddsService->getOddsForScore($odds->odds->correctScore, '0-2'),
            (float) $event['odds']->correctScore->{'1-2'}->bet_slip, //$event['under'] . '%',
            (float) $totals->under_slip,
            (float) $totals->over_slip,
        ];
        $values->setValues($data);

        $response = $this->sheet
                ->spreadsheet($this->spreadsheetId)
                ->sheet($this->sheetId)
                ->append([$values]);

        $updatedRange = $response->getUpdates()['updatedRange'];

        preg_match('/![a-zA-Z]{1,2}(\d*):/', $updatedRange, $matches);

        $excelLineNumber = (int) $matches[1];

        return $excelLineNumber;
    }

    public function getLineNumberByDateAndResult() {
        $sheet = $this->getSheet();

        $rowNumber = [];
        foreach ($sheet as $id => $row) {
            $date      = explode('/', $row[1], 2);
            $yesterday = date('d', time() - 86400);
            $ym        = date('m', time() - 86400);
            $m         = date('m');
            $etcDate   = $m . '/' . date('Y');
            $yetcDate  = $ym . '/' . date('Y');
            if (($date[0] === date('d') || $date[0] === $yesterday) && ($date[1] === $etcDate || $date[1] === $yetcDate) && empty($row[RESULT_COLUMN_NUMBER])) {
                $rowNumber[] = ($id + 1);
            }
        }

        if (empty($rowNumber)) {
            return false;
        }
        return $rowNumber;
    }

    public function postResultByBetstudyId($betstudyId, $result) {
        $sheet = $this->getSheet();

        $rowNumber = 0;
        foreach ($sheet as $id => $row) {
            if ($row[0] == $betstudyId) {
                $rowNumber = $id + 1;
            }
        }

        if ($rowNumber == 0) {
            return false;
        }

        $values = new \Google_Service_Sheets_ValueRange();

        $values->setValues($result);

        $response = $this->sheet
                ->spreadsheet($this->spreadsheetId)
                ->sheet($this->sheetId)
                ->range(sprintf('%s%s:%s%s', RESULT_COLUMN_CHAR, $rowNumber, RESULT_COLUMN_CHAR, $rowNumber))
                ->update([$values]);

        //var_dump($response);

        return $response;
    }

    public function postResultByLineNumber($lineNumber, $result) {
        $values = new \Google_Service_Sheets_ValueRange();

        $values->setValues($result);

        $response = $this->sheet
                ->spreadsheet($this->spreadsheetId)
                ->sheet($this->sheetId)
                ->range(sprintf('%s%s:%s%s', RESULT_COLUMN_CHAR, $lineNumber, RESULT_COLUMN_CHAR, $lineNumber))
                ->update([$values]);

        return $response;
    }

    public function updateCoefficientByLineNumber($lineNumber, $score, $newCoefficient) {
        $values = new \Google_Service_Sheets_ValueRange();

        $values->setValues($newCoefficient);

        $column = SCORE_COLUMN[$score];
        if (!$column) return;

        $response = $this->sheet
                ->spreadsheet($this->spreadsheetId)
                ->sheet($this->sheetId)
                ->range(sprintf('%s%s:%s%s', $column, $lineNumber, $column, $lineNumber))
                ->update([$values]);

        return $response;
    }

    public function getSheet() {
        $sheet = $this->sheet
                ->spreadsheet($this->spreadsheetId)
                ->sheet($this->sheetId)
                ->all();

        return $sheet;
    }

    /**
     * @return void
     */
    private function initSheet() {
        $client  = new \Google_Client();
        $client->setScopes([\Google_Service_Sheets::DRIVE, \Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig(CLIENT_SECRET_PATH);
        $client->getAccessToken();
        $service = new \Google_Service_Sheets($client);

        $this->sheet = new Sheets();
        $this->sheet->setService($service);
    }

}
