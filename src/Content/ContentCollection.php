<?php

namespace Meduza\Content;

class ContentCollection
{

    protected array $content = [];

    public function __construct()
    {
        
    }

    public function appendContent(Content $content): void
    {
        $this->content[] = $content;
    }

    public function getIterator(): \ArrayIterator
    {
        $obj = new \ArrayObject($this->content);
        return $obj->getIterator();
    }
}