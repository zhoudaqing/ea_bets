<?php
#20min

require_once __DIR__ . '/config.php';

/** @var \Service\PredictionService $ps */
$ps = $container->get('prediction_service');
$ps->getAndSavePredictions();
