<?php

require_once __DIR__ . '/config.php';

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$container = new ContainerBuilder();

$container
    ->register('mongo_client', \MongoDB\Client::class);
    //->addArgument('mongodb://betstrategyadmin:betstrategypwd@mongo/wettportal');

$container
    ->register('mysqli_client', \mysqli::class)
    ->addArgument(MYSQL_HOST)
    ->addArgument(MYSQL_USER)
    ->addArgument(MYSQL_PASS)
    ->addArgument(MYSQL_DB);

$container
    ->register('betstudy_prediction_parser', \Parser\BetstudyPredictionsParser::class);

$container
    ->register('wettportal_search_parser', \Parser\WettportalSearchParser::class);

$container
    ->register('wettportal_eventlist_parser', \Parser\WettportalEventListParser::class);

$container
    ->register('wettportal_result_parser', \Parser\WettportalResultParser::class);

$container
    ->register('wettportal_odds_parser', \Parser\WettportalOddsParser::class)
    ->addArgument(new Reference('mysqli_client'));


$container
    ->register('prediction_service', \Service\PredictionService::class)
    ->addArgument(new Reference('betstudy_prediction_parser'))
    ->addArgument(new Reference('mysqli_client'));


$container
    ->register('wettportal_service', \Service\WettportalService::class)
    ->addArgument(new Reference('wettportal_search_parser'))
    ->addArgument(new Reference('wettportal_eventlist_parser'))
    ->addArgument(new Reference('wettportal_result_parser'))
    ->addArgument(new Reference('mysqli_client'));

$container
    ->register('match_resolver_service', \Service\MatchResolverService::class)
    ->addArgument(new Reference('prediction_service'))
    ->addArgument(new Reference('mysqli_client'));

$container
    ->register('odds_service', \Service\OddsService::class);

$container
    ->register('google_sheets_service', \Service\GoogleSheetsService::class)
    ->addArgument(new Reference('odds_service'))
    ->addArgument(SPREADSHEET_ID)
    ->addArgument(SHEET);

$container
    ->register('telegram', \Telegram\Bot\Api::class)
    ->addArgument(TELEGRAM_TOKEN);
