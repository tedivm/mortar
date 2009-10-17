<?php

/*
 * This file is part of Twig.
 *
 * (c) 2009 Fabien Potencier
 * (c) 2009 Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base loader class for all builtin loaders.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
abstract class Twig_Loader implements Twig_LoaderInterface
{
  protected $cache;
  protected $autoReload;
  protected $env;

  /**
   * Constructor.
   *
   * The cache can be one of three values:
   *
   *  * null (the default): Twig will create a sub-directory under the system tmp directory
   *         (not recommended as templates from two projects with the same name will share the cache)
   *
   *  * false: disable the compile cache altogether
   *
   *  * An absolute path where to store the compiled templates
   *
   * @param string  $cache      The compiler cache directory
   * @param Boolean $autoReload Whether to reload the template is the original source changed
   */
  public function __construct($cache = null, $autoReload = true)
  {
    $this->cache = null === $cache ? sys_get_temp_dir().DIRECTORY_SEPARATOR.'twig_'.md5(dirname(__FILE__)) : $cache;

    if (false !== $this->cache && !is_dir($this->cache))
    {
      mkdir($this->cache, 0777, true);
    }

    $this->autoReload = $autoReload;
  }

  /**
   * Loads a template by name.
   *
   * @param  string $name The template name
   *
   * @return string The class name of the compiled template
   */
  public function load($name)
  {

	list($template, $mtime) = $this->getSource($name);

	if(preg_match('{\{% extends "(.*?)" %\}}', $template, $subs) === 1)
	{
		$parent = $this->load($subs[1]);
		$cls = $this->getTemplateName($template . $parent);
	}else{
		$parent = null;
		$cls = $this->getTemplateName($template);
	}

    if (class_exists($cls, false))
    {
      return $cls;
    }

    if (false === $this->cache)
    {
      $this->evalString($template, $name, $cls);

      return $cls;
    }


	if($this->loadFromCache($name, $cls, $parent))
		return $cls;

    $cache = $this->getCacheFilename($name);
	$content = $this->compile($template, $name, $cls);

	if(file_put_contents($cache, $content))
	{
		include($cache);
	}else{
		$this->evalString($template, $md5);
	}

    return $cls;
  }


  public function loadFromCache($name, $className, $parent = null)
  {
    $cache = $this->getCacheFilename($name);

    if(!file_exists($cache))
      return false;

    $fp = @fopen($cache, r);

    if(!$fp)
        return false;

	$classLine = 'class ' . $className;
	$lineNumber = isset($parent) ? 5 : 3;

    for($i = 0; $i <= $lineNumber; $i++)
    {
        if(feof($fp))
            break;

        $line = fgets($fp);
    }

	fclose($fp);

	if(!isset($line) || strpos($line, $classLine) !== 0)
		return false;

    include($cache);
    return class_exists($className, false);
  }

  public function setEnvironment(Twig_Environment $env)
  {
    $this->env = $env;
  }

  public function getTemplateName($name)
  {
    return '__TwigTemplate_'.md5($name);
  }

  public function getCacheFilename($name)
  {
    return $this->cache.'/twig_'.md5($name).'.cache.php';
  }

  protected function compile($source, $name, $classname = null)
  {
    return $this->env->compile($this->env->parse($this->env->tokenize($source, $name, $classname)));
  }

  protected function evalString($source, $name, $classname = null)
  {
    eval('?>'.$this->compile($source, $name, $classname));
  }

  /**
   * Gets the source code of a template, given its name.
   *
   * @param  string $name string The name of the template to load
   *
   * @return array An array consisting of the source code as the first element,
   *               and the last modification time as the second one
   *               or false if it's not relevant
   */
  abstract protected function getSource($name);
}
