<?php
declare(strict_types=1);

namespace PcComponentes\ApmSymfonyHttpClient;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Contracts\Service\ResetInterface;
use ZoiloMora\ElasticAPM\ElasticApmTracer;
use ZoiloMora\ElasticAPM\Events\Span\Context;
use function sprintf;

class TraceableApmHttpClient implements HttpClientInterface, ResetInterface
{
    private const SPAN_SUBTYPE = 'Http Client';
    private const STACKTRACE_SKIP = 11;

    private HttpClientInterface $client;
    private array $tracedRequests = [];
    private ElasticApmTracer $elasticApmTracer;

    public function __construct(HttpClientInterface $client, ElasticApmTracer $elasticApmTracer)
    {
        $this->client = $client;
        $this->elasticApmTracer = $elasticApmTracer;
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $traceInfo = [];
        $this->tracedRequests[] = [
            'method' => $method,
            'url' => $url,
            'options' => $options,
            'info' => &$traceInfo,
        ];

        $onProgress = $options['on_progress'] ?? null;

        $options['on_progress'] = function (int $dlNow, int $dlSize, array $info) use (&$traceInfo, $onProgress) {
            $traceInfo = $info;

            if (null !== $onProgress) {
                $onProgress($dlNow, $dlSize, $info);
            }
        };

        $name = sprintf('%s %s', $method, $url);

        return $this->elasticApmTracer->active()
            ? $this->requestWithApm($name, $method, $url, $options)
            : $this->client->request($method, $url, $options)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function getTracedRequests(): array
    {
        return $this->tracedRequests;
    }

    public function reset()
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }

        $this->tracedRequests = [];
    }

    /**
     * @param string $name
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed
     */
    private function requestWithApm(string $name, string $method, string $url, array $options)
    {
        $span = $this->elasticApmTracer->startSpan(
            $name,
            'request',
            self::SPAN_SUBTYPE,
            null,
            new Context(),
            self::STACKTRACE_SKIP,
        );

        $response = $this->client->request($method, $url, $options);

        $span->stop();

        $span->context()->setHttp(
            new Context\Http(
                (string)$response->getInfo('url'),
                $response->getStatusCode(),
                $response->getInfo('http_method'),
            )
        );
        return $response;
    }

    public function withOptions(array $options): static
    {
        // TODO: Implement withOptions() method.
    }
}