<?php

use Aws\S3\S3Client;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function getS3Client()
{
    return new S3Client([
        'version'     => 'latest',
        'region'      => $_ENV['AWS_REGION'],
        'credentials' => [
            'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        ],
    ]);
}

function getS3Bucket()
{
    return $_ENV['AWS_BUCKET'];
}
