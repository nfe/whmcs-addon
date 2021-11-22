<?php

namespace WHMCSExpert\Helper;

// use WHMCSExpert\Helper\SymlinkDetective;
/**
 *
 */
class Helper
{
  /**
   * Return WHMCS version as absolute float version
   * @return float WHMCS version
   * @see https://github.com/logical-and/whmcs-module-framework/blob/master/src/Helper.php#L134
   */
  public static function getWHMCSVersion()
  {
    global $CONFIG;
    // convert to true float
    $version = $CONFIG['Version'];
    $version = explode('.', $version);
    $version = rtrim(($version[0] . '.' . join('', array_slice($version, 1))), '.');

    return (float) $version;
  }

  /**
   * Produce absolute url for given path and arguments
   *
   * @param  $path
   * @param  array  $args
   * @return string
   */
  public static function getPathUrl($path, array $args = [])
  {
    global $CONFIG;
    $systemUrl = $CONFIG['SystemURL'];

    return rtrim($systemUrl, '/') .
           '/' . ltrim($path, '/') .
           (!$args ? '' : ('?' . http_build_query($args)));
  }

  /**
   * Get the root dir
   * @return string root dir
   */
  public static function getRootDir()
  {
    return rtrim(ROOTDIR, '/');
  }

  public function getDirectory()
  {
    // $directory = SymlinkDetective::detectPath(dirname($file));
    // return $directory;
    return dirname(\Composer\Factory::getComposerFile());
  }


}
