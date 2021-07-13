<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

$dotEnv = new Dotenv();
$dotEnv->bootEnv(dirname(__DIR__).'/.env');


// used when deploying with Lambda
// so that nowhere in the CI/CD infrastructure
// the env variables are manipulated in clear text
//
$localEnvEncryptedWithKMS = dirname(__DIR__).'/.env.local.crypt';
if (file_exists('/tmp/env_kms_decrypted')) {
    $dotEnv->load('/tmp/env_kms_decrypted');

} elseif (file_exists($localEnvEncryptedWithKMS)) {

    $kmsClient = new \Aws\Kms\KmsClient([
        'version' => '2014-11-01',
        'region' => 'eu-west-3'
    ]);

    $ciphertext = base64_decode(file_get_contents($localEnvEncryptedWithKMS));

    $result = $kmsClient->decrypt([
        'CiphertextBlob' => $ciphertext,
    ]);
    $plaintext = $result['Plaintext'];

    $values = $dotEnv->parse($plaintext);
    $dotEnv->populate($values, overrideExistingVars: true);

    file_put_contents('/tmp/env_kms_decrypted', $plaintext);
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();

// without these, (especially X_FORWATED_PROTO) symfony would not see
// the https from let's say ngrok, and will always generate http
// url instead of URL
$request->setTrustedProxies(
    ['192.0.0.1', 'REMOTE_ADDR'],
    // trust *all* "X-Forwarded-*" headers
    Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
