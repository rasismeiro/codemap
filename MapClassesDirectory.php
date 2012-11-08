<?php

/*
 * @package MapClasses
 * @version 1.0.0
 * @author Ricardo Sismeiro <ricardo@sismeiro.com>
 * @copyright 2012 Ricardo Sismeiro
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @filesource
 */

class MapClassesDirectory
{

  private static $_ext;
  private static $_a;
  private static $_regex;

  public static function read($dir, $ext = '')
  {

    $result = array();

    if (!isset(self::$_a)) {
      if (false !== strpos($ext, '*')) {
        self::$_a = true;
        $ext = str_replace(array(':', '?', '^', '$', '(', ')', '[', ']', '"', '/', '\\', "'"), '', $ext);
        self::$_regex = '/^' . strtr($ext, array('.' => '\.', '*' => '(.+?)')) . '$/i';
      } else {
        self::$_a = false;
      }
    }

    if (!isset(self::$_ext)) {
      self::$_ext = array('type' => $ext, 'size' => -1 * strlen($ext));
    }

    $_dir = str_replace(array('\\', '//'), '/', trim($dir));
    $dir = ('/' != substr($_dir, -1)) ? $_dir . '/' : $_dir;

    if (is_dir($dir)) {
      $handle = @opendir($dir);
      if ($handle) {
        while (false !== ($entry = readdir($handle))) {
          $f = ('.' == substr($entry, 0, 1)) ? true : false;
          if (!in_array($entry, array('.', '..')) && !$f) {

            $filename = $dir . $entry;

            if (is_dir($filename) && is_readable($filename)) {
              $_result = self::read($filename);
              $result = array_merge($result, $_result);
            } else {

              if (!empty(self::$_ext['type'])) {
                if (self::$_a) {
                  if (preg_match(self::$_regex, $entry)) {
                    $result[] = $filename;
                  }
                } else {
                  if (self::$_ext['type'] == substr($entry, self::$_ext['size'])) {
                    $result[] = $filename;
                  }
                }
              } else {
                $result[] = $filename;
              }
            }
          }
        }
        closedir($handle);
      }
    }

    return $result;
  }

}