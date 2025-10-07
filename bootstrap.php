<?php
declare(strict_types=1);

use Wartollex\Database;
use Wartollex\Services\Localization;

require __DIR__ . '/src/Support/autoload.php';

$config = require __DIR__ . '/config.php';

Database::initialize($config['database_path']);

$localization = Localization::defaults();
