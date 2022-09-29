<?php

declare(strict_types=1);

namespace Iammati\PhpDockerWrapper;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class Daemon
{
    use ApiTrait;

    protected readonly Client $client;

    public function connect($unixSocketPath = '/var/run/docker.sock'): self
    {
        $this->client = new Client([
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => $unixSocketPath,
            ],
        ]);
        return $this;
    }

    public function list(bool $all = true): ResponseInterface
    {
        return $this->client->get(
            $this->getApiUri("containers/json?all={$all}"),
        );
    }

    public function get(string $id): ResponseInterface
    {
        return $this->client->get($this->getApiUri("containers/{$id}/json"));
    }

    public function create(string $name, string $image, array $exposedPorts = []): ResponseInterface
    {
        $options = json_decode(
            json: file_get_contents(__DIR__.'/config/create.json'),
            associative: false,
            flags: JSON_OBJECT_AS_ARRAY
        );

        $options->Image = $image;

        if (!empty($exposedPorts)) {
            $_exposedPorts = json_decode(json_encode($options->ExposedPorts), true);
            $_portBindings = json_decode(json_encode($options->HostConfig->PortBindings), true);

            foreach ($exposedPorts as $port) {
                $_exposedPorts["{$port}/tcp"] = new stdClass;

                $_portBindings["{$port}/tcp"] = [
                    [
                        'HostIp' => '127.0.0.1',
                        'HostPort' => (string)$port,
                    ]
                ];
            }

            $options->ExposedPorts = json_decode(json_encode($_exposedPorts));
            $options->HostConfig->PortBindings = json_decode(json_encode($_portBindings));
        }

        $request = $this->client->post(
            $this->getApiUri("containers/create?name={$name}"),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($options),
            ]
        );

        return $request;
    }

    public function start(string $id): PromiseInterface
    {
        return $this->client->postAsync(
            $this->getApiUri("containers/{$id}/start")
        );
    }

    public function restart(string $id): ResponseInterface
    {
        return $this->client->post(
            $this->getApiUri("containers/{$id}/restart")
        );
    }

    public function stop(string $id): ResponseInterface
    {
        return $this->client->post(
            $this->getApiUri("containers/{$id}/stop")
        );
    }

    public function kill(string $id): ResponseInterface
    {
        return $this->client->post(
            $this->getApiUri("containers/{$id}/kill")
        );
    }

    public function pause(string $id): ResponseInterface
    {
        return $this->client->post(
            $this->getApiUri("containers/{$id}/pause")
        );
    }

    public function unpause(string $id): ResponseInterface
    {
        return $this->client->post(
            $this->getApiUri("containers/{$id}/unpause")
        );
    }

    public function remove(string $id): ResponseInterface
    {
        return $this->client->delete(
            $this->getApiUri("containers/{$id}")
        );
    }

    public function logs(string $id): string
    {
        $response = $this->client->get(
            $this->getApiUri("containers/{$id}/logs?stdout=1&timestampts=1")
        );

        return (string)$response->getBody();
    }

    public function exec(string $id, string $command, array $envVars = []): Exec
    {
$config = '{
"AttachStdin": false,
"AttachStdout": true,
"AttachStderr": true,
"DetachKeys": "ctrl-p,ctrl-q",
"Tty": true,    
"Cmd": ' . json_encode(explode(' ', $command)) . ',
"Env": ' . json_encode($envVars) . '
}';

        $request = $this->client->post(
            $this->getApiUri("containers/{$id}/exec"),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_decode(json_encode($config)),
            ]
        );

        $execId = json_decode((string)$request->getBody(), true)['Id'];

        return new Exec($this->client, $request, $id, $execId, $config);
    }
}
