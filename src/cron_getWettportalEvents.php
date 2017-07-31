<?php
#20min+1

require_once __DIR__ . '/config.php';
//exit;
/** @var \Service\WettportalService $ws */
$ws = $container->get('wettportal_service');
$ws->getAllSoccerEvents();