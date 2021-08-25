<?php

use Meduza\Build\Builder;
use Meduza\Config\ConfigLoader;

require './vendor/autoload.php';

$configLoader = new ConfigLoader();
$config = $configLoader->load($argv[1]);
// print_r($config->getConfig());
// exit();

$builder = new Builder($config);
$builder->build();
// print_r($builder);