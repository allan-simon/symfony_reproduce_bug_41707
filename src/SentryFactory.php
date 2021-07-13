<?php

namespace App;

use App\Kernel;
use Jean85\PrettyVersions;
use Nyholm\Psr7\Factory\HttplugFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Sentry\Client;
use Sentry\ClientBuilder;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\Integration\RequestIntegration;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\Transport\DefaultTransportFactory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;

class SentryFactory
{
    public function create(
        ?string $dsn,
        string $projectRoot,
        string $cacheDir
    ): HubInterface {
        $clientBuilder = ClientBuilder::create([
            'dsn'                  => $dsn ?: null,
            'in_app_include'       => [$projectRoot],
            'in_app_exclude'       => [$cacheDir, "$projectRoot/vendor"],
            'prefixes'             => [$projectRoot],
            'default_integrations' => false,
            'send_attempts'        => 1,
        ]);

        $client       = HttpClient::create(['timeout' => 2]);
        $psr17Factory = new Psr17Factory();
        $httpClient   = new HttplugClient($client, $psr17Factory, $psr17Factory);

        $httpPlugFactory   = new HttplugFactory();
        $httpClientFactory = new HttpClientFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $httpClient,
            Client::SDK_IDENTIFIER,
            PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion()
        );

        $clientBuilder->setTransportFactory(new DefaultTransportFactory($psr17Factory, $psr17Factory, $httpClientFactory));

        // Enable Sentry RequestIntegration
        $options = $clientBuilder->getOptions();
        $options->setIntegrations([new RequestIntegration()]);

        $client = $clientBuilder->getClient();

        // A global HubInterface must be set otherwise some feature provided by the SDK does not work as they rely on this global state
        return SentrySdk::setCurrentHub(new Hub($client));
    }
}
