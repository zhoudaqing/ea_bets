<?php
#20min+3

require_once __DIR__ . '/config.php';

/** @var \Parser\WettportalOddsParser $wop */
$wop = $container->get('wettportal_odds_parser');
$wop->getOdds();
