<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Utils;

class Configuration
{
    protected array $paths = [];
    protected array $ignoredPaths = [];
    protected $currentPath = null;

    public function at(string $path): self
    {
        $this->paths[$path] = [];
        $this->currentPath = $path;

        return $this;
    }

    public function setter(callable $callable): self
    {
        $this->paths[$this->currentPath]['setter'] = $callable;

        return $this;
    }

    public function getter(callable $callable): self
    {
        $this->paths[$this->currentPath]['getter'] = $callable;

        return $this;
    }

    public function ignore(...$paths): self
    {
        foreach ($paths as $path) {
            if (!in_array($path, $this->ignoredPaths)) {
                $this->ignoredPaths[] = $path;
            }
        }

        return $this;
    }

    public function ignoreNull(): self
    {
        $this->paths[$this->currentPath]['ignoreNull'] = true;

        return $this;
    }

    public function getGetter(string $path)
    {
        return $this->paths[$path]['getter'] ?? false;
    }

    public function getSetter(string $path)
    {
        return $this->paths[$path]['setter'] ?? false;
    }

    public function getIgnoreNull(string $path)
    {
        return $this->paths[$path]['ignoreNull'] ?? false;
    }

    public function getIgnoredPaths()
    {
        return $this->ignoredPaths;
    }
}
