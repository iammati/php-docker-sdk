<?php

declare(strict_types=1);

namespace Iammati\PhpDockerSdk;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Exec
{
    use ApiTrait;

    protected readonly Client $client;
    protected readonly ResponseInterface $request;
    protected readonly string $containerId;
    protected readonly string $execId;
    protected readonly string $config;

    public function __construct(
        Client $client,
        ResponseInterface $request,
        string $containerId,
        string $execId,
        string $config
    )
    {
        $this->client = $client;
        $this->request = $request;
        $this->containerId = $containerId;
        $this->execId = $execId;
        $this->config = $config;
    }

    public function getRequest(): ResponseInterface
    {
        return $this->request;
    }

    public function getConfig(): string
    {
        return $this->config;
    }

    public function start(): ResponseInterface
    {
        return $this->client->post(
            $this->getApiUri("exec/{$this->execId}/start"),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_decode(json_encode($this->config)),
            ]
        );
    }
}
