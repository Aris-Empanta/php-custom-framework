<?php

declare(strict_types=1);

//We import the autoloader so we can use namespaces.
require_once dirname(__DIR__) . '/vendor/autoload.php'; 

use App\App;

$app = new App();

$app->run();