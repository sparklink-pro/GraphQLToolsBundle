<?php

declare(strict_types=1);

namespace Sparklink\GraphQLToolsBundle\Utils;

class ConfigurationPath
{
    protected $getter = null;
    protected $setter = null;
    protected bool $ignoreNull = false;
    protected $ignored = null;

    public function setGetter(callable $callable): self
    {
        $this->getter = $callable;

        return $this;
    }

    public function getGetter(): ?callable
    {
        return $this->getter;
    }

    public function setSetter(callable $callable): self
    {
        $this->setter = $callable;

        return $this;
    }

    public function getSetter(): ?callable
    {
        return $this->setter;
    }

    public function setIgnoreNull(bool $ignoreNull): self
    {
        $this->ignoreNull = $ignoreNull;

        return $this;
    }

    public function isIgnoreNull(): bool
    {
        return $this->ignoreNull;
    }

    public function isIgnored(mixed $object, mixed $value): bool 
    {
        if (!$this->ignored) {
            return false;
        }

        if (is_callable($this->ignored)) {
            return($this->ignored)($object, $value);
        }

        return $this->ignored;
    }

    public function setIgnored($callable): self
    {
        if (is_callable($callable)) {
            $this->ignored = $callable;
        } else if (is_bool($callable)) {
            $this->ignored = $callable;
        } else {
            throw new \Exception('Invalid value for ignored. Bool or callable expected.');
        }

        return $this;
    }
}
