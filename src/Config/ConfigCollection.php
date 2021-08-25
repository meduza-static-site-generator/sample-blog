<?php

namespace Meduza\Config;

class ConfigCollection
{
    protected array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getConfig(): array
    {
        return $this->data;
    }

    public function setConfig(array $data): void
    {
        $this->data = $data;
    }
}