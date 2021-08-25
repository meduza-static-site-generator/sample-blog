<?php

namespace Meduza\Plugin;

use Meduza\Build\Build;

abstract class PluginBase
{
    protected Build $build;

    public function __construct(Build $build)
    {
        $this->build = $build;
    }

    abstract public function run(): void;
}