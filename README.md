# Elastic APM for Symfony HttpClient

This library supports Span traces of [Symfony HttpKernel](https://github.com/symfony/http-kernel) requests.

## Installation

1) Install via [composer](https://getcomposer.org/)

    ```shell script
    composer require pccomponentes/apm-symfony-http-client
    ```

## Usage

In all cases, an already created instance of [ElasticApmTracer](https://github.com/zoilomora/elastic-apm-agent-php) is assumed.

### Service Container (Symfony)

```yaml
amp.http_client:
    class: PcComponentes\RuleStorm\Infrastructure\TraceableApmHttpClient
    arguments: 
        $client: '@http_client'
        $elasticApmTracer: '@apm.tracer' # \ZoiloMora\ElasticAPM\ElasticApmTracer instance.
```

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT)

Read [LICENSE](LICENSE) for more information
