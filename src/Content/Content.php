<?php

namespace Meduza\Content;

class Content
{
    protected Frontmatter $frontmatter;
    protected string $markdown = '';
    protected string $source = '';
    protected string $html = '';
    
    public function __construct(string $file)
    {
        $this->source = $file;
        $this->parse();
    }

    protected function parse(): void
    {
        $fhandle = fopen($this->source, 'r');
        if($fhandle === false){
            throw new \InvalidArgumentException("File {$this->source} is inaccessible");
        }

        $readingFrontMatter = false;
        $readingMarkdown = false;
        $frontmatter = '';
        $markdown = '';
        do{
            $buffer = fgets($fhandle);
            if($readingMarkdown){
                $markdown .= $buffer;
                continue;
            }

            if($readingFrontMatter === false && trim($buffer) === '---'){
                $readingFrontMatter = true;
                $frontmatter .= $buffer;
                continue;
            }elseif($readingFrontMatter === true && trim($buffer) !== '---'){
                $frontmatter .= $buffer;
                continue;
            }elseif($readingFrontMatter === true && trim($buffer) === '---'){
                $frontmatter .= $buffer;
                $readingMarkdown = true;
                continue;
            }
        }while ($buffer !== false);

        $this->frontmatter = new Frontmatter($frontmatter);
        $this->markdown = $markdown;

        fclose($fhandle);
    }

    public function frontmatter(): Frontmatter
    {
        return $this->frontmatter;
    }

    public function getMarkdown(): string
    {
        return $this->markdown;
    }

    public function setMarkdown(string $markdown): void
    {
        $this->markdown = $markdown;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the value of html
     */ 
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     * Set the value of html
     *
     * @return  self
     */ 
    public function setHtml($html): void
    {
        $this->html = $html;
    }
}