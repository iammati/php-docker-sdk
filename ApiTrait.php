<?php

declare(strict_types=1);

namespace Iammati\PhpDockerWrapper;

trait ApiTrait
{
    protected string $DOCKER_ENGINE_API_VERSION = 'v1.41';

    protected function getApiUri(string $pathname = ''): string
    {
        return "http://localhost/{$this->DOCKER_ENGINE_API_VERSION}/{$pathname}";
    }
}
