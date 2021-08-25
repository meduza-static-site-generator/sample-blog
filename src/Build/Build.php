<?php

namespace Meduza\Build;

use Meduza\Config\ConfigCollection;
use Meduza\Content\ContentCollection;

class Build
{
    protected ConfigCollection $config;

    protected ContentCollection $content;

    protected array $pluginData = [];

    public function __construct(ConfigCollection $config)
    {
        $this->config = $config;
    }

    public function config(): ConfigCollection
    {
        return $this->config;
    }

    public function getContent(): ContentCollection
    {
        return $this->content;
    }

    public function setContent(ContentCollection $content): void
    {
        $this->content = $content;
    }

    public function getPluginData(string $pluginName): array
    {
        return $this->pluginData[$pluginName];
    }

    public function setPluginData(string $pluginName, array $pluginData): void
    {
        $this->pluginData[$pluginName] = $pluginData;
    }

    public function getAllPluginData(): array
    {
        return $this->pluginData;
    }
}
