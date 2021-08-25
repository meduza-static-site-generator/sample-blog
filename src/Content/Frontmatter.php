<?php

namespace Meduza\Content;

class Frontmatter
{
    protected array $frontmatter = [];

    public function __construct(string $frontmatter)
    {
        $this->frontmatter = yaml_parse($frontmatter, 0);
    }

    public function getFrontmatter(): array
    {
        return $this->frontmatter;
    }

    public function setFrontmatter(array $frontmatter): void
    {
        $this->frontmatter = $frontmatter;
    }
}