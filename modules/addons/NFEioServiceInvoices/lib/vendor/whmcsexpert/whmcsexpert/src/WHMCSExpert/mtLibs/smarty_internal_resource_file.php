<?php

/**
 * Permits the correct reading of Encrypted Smarty TPL files
 * with Ioncube
 */
class SmartyEncrypt extends Smarty_Internal_Resource_File
{

  public function getContent(Smarty_Template_Source $source)
  {
      if ($source->timestamp)
      {
          if (function_exists('ioncube_read_file'))
          {
              $res = ioncube_read_file($source->filepath);
              if (is_int($res)) $res = false;
              return $res;
          }
          else
          {
              return file_get_contents($source->filepath);
          }
      }
      if ($source instanceof Smarty_Config_Source)
      {
          throw new SmartyException("Unable to read config {$source->type} '{$source->name}'");
      }
      throw new SmartyException("Unable to read template {$source->type} '{$source->name}'");
  }

}
