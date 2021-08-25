<?php

namespace Meduza\Config;

class ConfigLoader
{
    public function __construct()
    {
        
    }

    public function load(string ...$file): ConfigCollection
    {
        $data = $this->loadYamlFile(...$file);

        return new ConfigCollection($data);
    }

    protected function loadYamlFile(string ...$file): array
    {
        $data = [];

        foreach($file as $f){
            $content = \yaml_parse_file($f, 0);
            if($content === false){
                throw new \InvalidArgumentException("File $f is inaccessible!");
            }

            $data = self::array_merge_recursive_distinct($data, $content);
        }
        
        $data = $this->import($data);

        return $data;
    }

    protected function import(array $data): array
    {
        foreach($data as $k => $v){
            if($k === 'import'){
                foreach($v as $f){
                    unset($data[$k]);
                    $data = self::array_merge_recursive_distinct($this->loadYamlFile($f), $data);
                }
            }
        }
        return $data;
    }

    /**
     * Implementa a função array_merge_recursive_distinct de gabriel.sobrinho@gmail.com em https://www.php.net/manual/pt_BR/function.array-merge-recursive.php#92195
     */
    protected static function array_merge_recursive_distinct (array $array1, array $array2)
    {
      $merged = $array1;
    
      foreach ( $array2 as $key => &$value )
      {
        if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
        {
          $merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value );
        }
        else
        {
          $merged [$key] = $value;
        }
      }
    
      return $merged;
    }

}