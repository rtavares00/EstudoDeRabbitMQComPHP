<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

if (file_exists(__DIR__ . '/../.env')):
    $dotenv = new Dotenv();
    $dotenv->load(__DIR__ . '/../.env');
endif;

// Load configuration
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_PORT', $_ENV['DB_PORT']);
define('DB_DATABASE', $_ENV['DB_DATABASE']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);

define('RABBITMQ_HOST', $_ENV['RABBITMQ_HOST']);
define('RABBITMQ_PORT', $_ENV['RABBITMQ_PORT']);
define('RABBITMQ_USER', $_ENV['RABBITMQ_USER']);
define('RABBITMQ_PASSWORD', $_ENV['RABBITMQ_PASSWORD']);
define('RABBITMQ_VHOST', $_ENV['RABBITMQ_VHOST']);

define('APP_ENV', $_ENV['APP_ENV']);
define('APP_DEBUG', $_ENV['APP_DEBUG']);
