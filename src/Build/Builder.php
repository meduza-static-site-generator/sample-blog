<?php

namespace Meduza\Build;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Meduza\Config\ConfigCollection;
use Meduza\Content\Content;
use Meduza\Content\ContentCollection;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Builder
{

    protected ConfigCollection $config;
    protected Build $build;

    public function __construct(ConfigCollection $config)
    {
        $this->config = $config;
        $this->build = $this->prepareBuild();
    }

    public function build(): void
    {
        echo "Building proccess started...", PHP_EOL;
        //lê o conteúdo
        $this->parseContent();

        //lê o conteúdo do tema
        $this->parseThemeContent();

        //faz operações extras no conteúdo
        $this->extraOperationsOverContent();

        //aplica os plugins
        $this->runPlugins();

        //limpa o build anterior
        $this->clearLastBuild();

        //copia conteúdo estático
        $this->copyStaticContent();

        //copia conteúdo estático do tema
        $this->copyThemeStaticContent();

        //gera html e salva
        $this->buildOutput();
        echo "Building proccess finished...", PHP_EOL;
    }

    protected function parseContent(): void
    {
        $collection = new ContentCollection();
        $config = $this->config->getConfig();
        $inputPath = $config['build']['input'];
        echo "Prossessing input dir for content [$inputPath] ...", PHP_EOL;

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($inputPath));
        $it->rewind();
        while ($it->valid()) {
            if (!$it->isFile()) {
                $it->next();
                continue;
            }
            $collection->appendContent(new Content($it->getPathname()));
            $it->next();
        }
        $this->build->setContent($collection);
    }

    protected function parseThemeContent(): void
    {
        echo "Prossessing theme content...", PHP_EOL;
        $collection = $this->build->getContent();
        $config = $this->config->getConfig();
        if (!key_exists('content', $config['theme'])) {
            echo "\tSkipped!.", PHP_EOL;
            return;
        }
        $inputPath = $config['theme']['content'];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($inputPath));
        $it->rewind();
        while ($it->valid()) {
            if (!$it->isFile()) {
                $it->next();
                continue;
            }
            // echo $it->getPathname(), PHP_EOL;
            $collection->appendContent(new Content($it->getPathname()));
            $it->next();
        }
        $this->build->setContent($collection);
    }

    protected function extraOperationsOverContent(): void
    {
        echo "Extra operations over content...", PHP_EOL;
        $this->applyDefaultsOverFrontMatter();
        $this->createOutputFilePath();
        $this->createOutputUrl();
    }

    protected function createOutputFilePath(): void
    {
        $config = $this->config->getConfig();
        $inputPath = $config['build']['input'];
        $themeInputPath = $config['theme']['content'];
        $outputPath = $config['build']['output'];

        foreach ($this->build->getContent()->getIterator() as $contentKey => $contentItem) {
            $source = $contentItem->getSource();
            $outputFilePath = str_replace($inputPath, $outputPath, $source, $count);
            if ($count === 0) {
                $outputFilePath = str_replace($themeInputPath, $outputPath, $source);
            }
            $outputFilePath = str_replace('.' . pathinfo($source, PATHINFO_EXTENSION), '.html', $outputFilePath);
            $frontmatter = $contentItem->frontmatter()->getFrontmatter();
            $frontmatter['saveTo'] = $outputFilePath;
            $contentItem->frontmatter()->setFrontmatter($frontmatter);
        }
    }

    protected function createOutputUrl(): void
    {
        $config = $this->config->getConfig();
        $outputPath = $config['build']['output'];

        foreach ($this->build->getContent()->getIterator() as $contentKey => $contentItem) {
            $frontmatter = $contentItem->frontmatter()->getFrontmatter();
            $base = substr($config['site']['baseUrl'], 0, strlen($config['site']['baseUrl']) - 1);//retira a última barra/contra-barra
            $url = str_replace($outputPath, $base, $frontmatter['saveTo']);
            $frontmatter['url'] = $url;
            // echo $outputPath, ' -> ', $url, PHP_EOL;
            $contentItem->frontmatter()->setFrontmatter($frontmatter);
        }
    }

    protected function applyDefaultsOverFrontMatter(): void
    {
        $defaults = $this->config->getConfig()['defaults'];
        foreach ($this->build->getContent()->getIterator() as $contentKey => $contentItem) {
            foreach ($defaults as $scope => $defaulValues) {
                $pattern = "/$scope/";
                $subject = $contentItem->getSource();
                $match = preg_match($pattern, $subject);
                // echo "$scope -> $subject = $match (", gettype($match), ")", PHP_EOL;
                if ($match === 1) {
                    $frontmatter = $contentItem->frontmatter()->getFrontmatter();
                    foreach ($defaulValues as $key => $value) {
                        if (!key_exists($key, $frontmatter)) {
                            $frontmatter[$key] = $value;
                        }
                    }
                    $contentItem->frontmatter()->setFrontmatter($frontmatter);
                }
            }
        }
    }

    protected function runPlugins(): void
    {
        echo "Running plugins...", PHP_EOL;
        $plugins = $this->config->getConfig()['plugins'];
        foreach ($plugins as $pluginName => $pluginOptions) {
            $pluginClassName = "Meduza\\Plugin\\$pluginName";
            require $pluginOptions['source'];
            $pluginInstance = new $pluginClassName($this->build);
            $pluginInstance->run();
        }
    }

    protected function clearLastBuild(): void
    {
        echo "cleaning last build...", PHP_EOL;
        $buildDir = $this->config->getConfig()['build']['output'];
        self::delTree($buildDir);
        mkdir($buildDir);
    }

    /**
     * Cópia da função de nbari@dalmp.com em https://www.php.net/manual/en/function.rmdir.php#110489
     */
    public static function delTree(string $dir)
    {
        if(!file_exists($dir)) return;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    protected function copyStaticContent(): void
    {
        echo "Coping static content...", PHP_EOL;
        $config = $this->config->getConfig();
        $staticDir = $config['build']['static'];
        $outputDir = $config['build']['output'];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($staticDir));
        $it->rewind();
        while ($it->valid()) {
            if (!$it->isFile()) {
                $it->next();
                continue;
            }
            // echo $it->getPath(), PHP_EOL;
            $destinyDir = str_replace($staticDir, $outputDir, $it->getPath());
            if (!file_exists($destinyDir)) {
                mkdir($destinyDir, 0777, true);
            }
            $destinyFile = str_replace($staticDir, $outputDir, $it->getPathname());
            // echo $it->getPathname(), ' -> ', $destinyFile, PHP_EOL;
            copy($it->getPathname(), $destinyFile);
            $it->next();
        }
    }

    protected function copyThemeStaticContent(): void
    {
        echo "Coping theme static content...", PHP_EOL;
        $config = $this->config->getConfig();
        if (!key_exists('static', $config['theme'])) {
            echo "\tSkipped!.", PHP_EOL;
            return;
        }
        $staticDir = $config['theme']['static'];
        $outputDir = $config['build']['output'];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($staticDir));
        $it->rewind();
        while ($it->valid()) {
            if (!$it->isFile()) {
                $it->next();
                continue;
            }
            // echo $it->getPath(), PHP_EOL;
            $destinyDir = str_replace($staticDir, $outputDir, $it->getPath());
            if (!file_exists($destinyDir)) {
                mkdir($destinyDir, 0777, true);
            }
            $destinyFile = str_replace($staticDir, $outputDir, $it->getPathname());
            // echo $it->getPathname(), ' -> ', $destinyFile, PHP_EOL;
            copy($it->getPathname(), $destinyFile);
            $it->next();
        }
    }

    protected function buildOutput(): void
    {
        echo "Building output...", PHP_EOL;
        $this->buildHtml();
        $this->mergeAndWrite();
    }

    protected function getMarkdownParser(): GithubFlavoredMarkdownConverter
    {
        return new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => true
        ]);
    }

    protected function buildHtml(): void
    {
        $parser = $this->getMarkdownParser();

        foreach ($this->build->getContent()->getIterator() as $contentKey => $contentItem) {
            $contentItem->setHtml($parser->convertToHtml($contentItem->getMarkdown()));
        }
    }

    protected function mergeAndWrite(): void
    {
        $config = $this->config->getConfig();
        $loader = new FilesystemLoader($config['theme']['layouts']);
        $twig = new Environment($loader, [
            'autoescape' => false
        ]);

        foreach ($this->build->getContent()->getIterator() as $contentKey => $contentItem) {
            $frontmatter = $contentItem->frontmatter()->getFrontmatter();
            $template = $twig->load($frontmatter['layout'] . '.twig');
            $env = [
                'content' => $contentItem->getHtml(),
                'site' => $config['site'],
                'theme' => $config['theme'],
                'page' => $frontmatter,
                'plugins' => [
                    'config' => $config['plugins'],
                    'data' => $this->build->getAllPluginData()
                ]
            ];
            // print_r($env['plugins']);exit();
            $html = $template->render($env);

            $this->writeOutput($frontmatter['saveTo'], $html);
        }
    }

    protected function writeOutput(string $destiny, string $data): void
    {
        if (!file_exists(dirname($destiny))) {
            mkdir(dirname($destiny), 0777, true);
        }
        file_put_contents($destiny, $data);
    }

    protected function prepareBuild(): Build
    {
        return new Build($this->config);
    }
}
