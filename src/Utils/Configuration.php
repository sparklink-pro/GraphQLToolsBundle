<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Utils;

class Configuration
{
    protected array $paths = [];
    protected ?string $currentPath = null;

    public function __clone()
    {
        $this->currentPath = null;
        foreach($this->paths as $path => $configurationPath) {
            $this->paths[$path] = clone $configurationPath;
        }
    }

    public function at(string $path): self
    {
        $this->currentPath  = $path;

        return $this;
    }

    public function get(?string $path = null): ConfigurationPath
    {
        if (!$path) {
            $path = $this->currentPath;
        }
        
        if (!$path) {
            throw new \Exception('Current path is empty');
        }

        if (!array_key_exists($path, $this->paths)) {
            $this->paths[$path] = new ConfigurationPath();
        }
        
        return $this->paths[$path] ?? null;
    }

    public function ignore(...$paths): self
    {
        foreach ($paths as $path) {
            $this->get($path, true)->setIgnored(true);
        }

        return $this;
    }

    public function setter(callable $callable): self
    {
        $this->get()->setSetter($callable);

        return $this;
    }

    public function getter(callable $callable): self
    {
        $this->get()->setGetter($callable);

        return $this;
    }

    public function ignoreNull(): self
    {
        $this->get()->setIgnoreNull(true);
        
        return $this;
    }
}
