<?php

require __DIR__ . '/../vendor/autoload.php';

use YourImageShare\YourImageShare;
use YourImageShare\YourImageShareError;

$client = new YourImageShare('YOUR_API_KEY');

try {
    $result = $client->upload(__DIR__ . '/photo.jpg');
    echo $result->direct . PHP_EOL;
} catch (YourImageShareError $e) {
    fwrite(STDERR, $e->getStatus() . ': ' . $e->getApiMessage() . PHP_EOL);
    exit(1);
}
