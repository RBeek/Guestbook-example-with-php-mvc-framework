<?php

define('BASE_URL', '/');
define('APP_PATH', realpath(__DIR__)."/../app");

use Framework\Support\AliasLoader;
use Framework\Config\Config;

$config = Config::getInstance();

AliasLoader::getInstance($config->config_values['aliases'])->register();


