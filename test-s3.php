<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$s3 = new S3Client([
    'version'     => 'latest',
    'region'      => $_ENV['AWS_REGION'],
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
]);

$bucket = $_ENV['AWS_BUCKET'];
$archivo_local = __DIR__ . '/test.jpg';
$key_destino = 'test/' . basename($archivo_local);

try {
    $result = $s3->putObject([
        'Bucket' => $bucket,
        'Key' => $key_destino,
        'SourceFile' => $archivo_local,
        'ContentType' => mime_content_type($archivo_local),
    ]);

    echo "✅ Imagen subida: " . $result['ObjectURL'] . "\n";
} catch (\Aws\Exception\AwsException $e) {
    echo "❌ Error: " . $e->getMessage();
}
