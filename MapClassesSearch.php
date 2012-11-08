<?php

/*
 * @package MapClasses
 * @version 1.0.0
 * @author Ricardo Sismeiro <ricardo@sismeiro.com>
 * @copyright 2012 Ricardo Sismeiro
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @filesource
 */

class MapClassesSearch extends MapClassesDirectory
{

  public static $listOfFiles;

  public static function log($m)
  {
    $o = '<script type="text/javascript">'
        . PHP_EOL
        . ' if (typeof(console) == "object") {console.log("' . $m . '");}'
        . PHP_EOL . '</script>';
    echo $o;
    ob_flush();
    flush();
  }

  public static function match($dir, $ext, $match, $returnMatch = false)
  {

    if (!isset(self::$listOfFiles)) {
      self::$listOfFiles = self::read($dir, $ext);
    }

    $result = array();

    foreach (self::$listOfFiles as $file) {

      if (is_readable($file)) {
        $handle = fopen($file, 'r');
        if ($handle) {
          $result[$file] = array();
          $i = 0;

          while (false !== ($buffer = fgets($handle, 4096))) {

            $i++;
            $buffer = trim($buffer);
            $m = array();
            if (preg_match('/' . $match . '/', $buffer, $m)) {
              if (!$returnMatch) {
                $result[$file][$i] = $buffer;
              } else {
                $result[$file][$i] = $m;
              }
            }
          }
          if (count($result[$file]) < 1) {
            unset($result[$file]);
          }
          fclose($handle);
        }
      }
    }
    return $result;
  }

  public static function classes($directory)
  {
    $result = array();

    $match = '(^[ ]+|^)(abstract )?class[ ]+([a-zA-Z0-9\_]+)[ ]*'
        . '([ ]+(extends|implements)[ ]+[\a-zA-Z0-9_]+)?([ \r\n\{]{1})?$';

    $m = self::match($directory, '.php', $match, true);
    foreach ($m as $k => $v) {
      foreach ($v as $i => $a) {
        if (isset($a[3])) {
          if (!isset($result[$a[3]])) {
            $result[$a[3]] = array();
          }
          $result[$a[3]][] = $k;
        }
      }
    }
    return $result;
  }

  public static function classesCalls($directory)
  {

    $cache = array('files' => null, 'classes' => null, 'data' => array());
    $cacheFilename = dirname(__FILE__) . '/' . md5($directory) . '.cache';
    self::log('cache: ' . basename($cacheFilename));

    if (file_exists($cacheFilename)) {
      $raw = file_get_contents($cacheFilename);
      $cache = unserialize($raw);
    }

    if (is_null($cache['classes']) || is_null($cache['files'])) {

      $classes = self::classes($directory);
      $cache['classes'] = array_keys($classes);
      unset($classes);

      $files = self::$listOfFiles;
      foreach ($files as $k => $file) {
        if (!is_readable($file)) {
          unset($files[$k]);
        }
      }
      $cache['files'] = $files;
      unset($files);

      $raw = serialize($cache);
      file_put_contents($cacheFilename, $raw);
      unset($raw);
    }

    $result = array();

    $_tfiles = count($cache['files']);
    $_schar = strlen((string) $_tfiles);

    self::log('-----------------------------------');
    self::log('classes : ' . count($cache['classes']));
    self::log('files   : ' . count($cache['files']));
    self::log('-----------------------------------');

    foreach ($cache['files'] as $k => $file) {

      if (isset($cache['data'][$file])) {
        $result[$file] = $cache['data'][$file];
        continue;
      }

      $handle = fopen($file, 'r');

      if ($handle) {
        $result[$file] = array();

        //reading file to memory and select only some lines
        $mem = array('count' => 0, 'data' => array());
        while (false !== ($buffer = fgets($handle, 4096))) {
          $buffer = trim($buffer);
          if ((false !== strpos($buffer, 'new ')) || (false !== strpos($buffer, '::'))) {
            $mem['data'][] = $buffer;
          }
        }

        unset($buffer);
        fclose($handle);

        $mem['count'] = count($mem['data']);

        self::log(sprintf('%0' . $_schar . 'd/%d - lines : %5d file: %s', ($k + 1), $_tfiles, $mem['count'], basename($file)));

        if ($mem['count'] > 0) {
          foreach ($cache['classes'] as $class) {
            $n = strlen($class) + 3;

            $match = '([\=& ,;\(\t\!@><]{1}|^)((new[ ]+([\a-zA-Z0-9_]+)?'
                . $class . ')|(([\a-zA-Z0-9_]+)?'
                . $class . '::[_a-zA-Z0-9]+))';

            $i = 0;
            while ($i < $mem['count']) {
              $buffer = $mem['data'][$i];
              if (isset($buffer{$n}) && (strpos($buffer, $class) !== false)) {
                if (preg_match('/' . $match . '/', $buffer)) {
                  if (!isset($result[$file][$class])) {
                    $result[$file][$class] = 0;
                  }
                  $result[$file][$class]++;
                }
              }
              $i++;
            }
          }
        }
      }

      $cache['data'] = $result;
      $raw = serialize($cache);
      file_put_contents($cacheFilename, $raw);
      $raw = null;
    }
    unset($cache);
    self::log('-----------------------------------');
    self::log('DONE!');

    foreach ($result as $k => $v) {
      if (count($v) < 1) {
        unset($result[$k]);
      }
    }
    return $result;
  }

}