<?php
/**
 * XO
 *
 * File Based CMS
 * LightNCandy Handlebars Wrapper
 *
 * This content is released under the MIT License (MIT)
 * Copyright (c) 2014 - 2019, Project Orange Box
 */

namespace xo;

/**
 *
 * @package XO
 * @author Don Myers
 * @copyright 2019
 * @license http://opensource.org/licenses/MIT MIT License
 * @link https://github.com/dmyers2004
 * @version v1.0.0
 * @filesource
 *
 */

use LightnCandy\LightnCandy;

class Handlebars
{
	/**
	 * Track whether we are in debug mode or not
	 *
	 * @var boolean
	 */
	protected $debug = false;

	/**
	 * LightnCandy\LightnCandy Compile Flags
	 *
	 * @var integer
	 */
	protected $flags;

	/**
	 * Template File Extension
	 *
	 * @var string
	 */
	protected $templateExtension = 'html';

	/**
	 * LightnCandy\LightnCandy Default Compile Flags
	 *
	 * @var integer
	 */
	protected $defaultFlags = 0;

	/**
	 * Compile Cache Path (absolute)
	 *
	 * @var string
	 */
	protected $compiledPath;

	/**
	 * Handlebars Plugin Regular Expression
	 *
	 * @var string
	 */
	protected $pluginRegex = '(.*)\.plugin\.php';

	/**
	 * Internal storage for loaded plugins
	 *
	 * @var array
	 */
	protected $plugins = [];

	/**
	 * Internal storage for folders to search for plugins
	 *
	 * @var array
	 */
	protected $pluginsPaths = [];

	/**
	 * Internal storage for folders to search for partials
	 *
	 * @var array
	 */
	protected $partialsPaths = [];

	/**
	 * Internal storage for partial name to file path
	 *
	 * @var array
	 */
	protected $partialFiles = [];

	/**
	 * Internal storage to track if we've loaded partials.
	 *
	 * @var bool
	 */
	protected $partialsLoaded = false;

	/**
	 * Internal storage to track if we've loaded plugins.
	 *
	 * @var bool
	 */
	protected $pluginsLoaded = false;

	/**
	 * Internal storage to track if we should recompile the next template request.
	 *
	 * @var bool
	 */
	protected $recompile = false;

	/**
	 *
	 * Constructor
	 *
	 * @access public
	 *
	 */
	public function __construct()
	{

		$this->$defaultFlags = LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_NAMEDARG | LightnCandy::FLAG_ADVARNAME | LightnCandy::FLAG_ERROR_LOG;

		$this
			->debug(config('handlebars.debug', false))
			->flags(config('handlebars.flags', $this->$defaultFlags))
			->templateExtension(config('handlebars.template extension', $this->templateExtension))
			->pluginRegex(config('handlebars.plugin regex', $this->pluginRegex))
			->compiledPath(config('handlebars.cache path', config('cache path')))
			->addPartialPath(config('handlebars.partials path',null))
			->addPluginPath(config('handlebars.plugin path',null));
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $pluginPath
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function addPluginPath($pluginPath) : Handlebars
	{
		if (is_array($pluginPath)) {
			foreach ($pluginPath as $pp) {
				$this->addPluginPath($pp);
			}

			return $this;
		}

		if (!empty($pluginPath)) {
			$path = '/'.trim($pluginPath, '/');

			$this->pluginsPaths[$path] = $path;

			$this->pluginsLoaded = false;
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $partialsPath
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function addPartialPath($partialsPath) : Handlebars
	{
		if (is_array($partialsPath)) {
			foreach ($partialsPath as $pp) {
				$this->addPartialPath($pp);
			}

			return $this;
		}

		if (!empty($partialsPath)) {
			$path = '/'.trim($partialsPath, '/');

			$this->partialsPaths[$path] = $path;

			$this->partialsLoaded = false;
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $templateExtension
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function templateExtension(string $templateExtension) : Handlebars
	{
		$this->templateExtension = '.'.trim($templateExtension, '.');

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $pluginRegex
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function pluginRegex(string $pluginRegex) : Handlebars
	{
		$this->pluginExtension = $pluginRegex;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param int $flags
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function flags(int $flags) : Handlebars
	{
		$this->flags = $flags;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```php
	 * ci('handlebars_helper')->recompile()->parse('/handlebars.hbs');
	 * ```
	 */
	public function recompile() : Handlebars
	{
		$this->recompile = true;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param int $flag
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function setFlag(int $flag) : Handlebars
	{
		$this->flags = $this->flags | $flag;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param int $flag
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function clearFlag(int $flag) : Handlebars
	{
		$this->flags = $this->flags & (~ $flag);

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param bool $bool true
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function debug(bool $bool = true) : Handlebars
	{
		$this->debug = $bool;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $compiledPath absolute path to cache folder
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function compiledPath(string $compiledPath) : Handlebars
	{
		$this->compiledPath = '/'.trim($compiledPath, '/');

		if (!file_exists($this->compiledPath)) {
			$umask = umask(0);
			mkdir($this->compiledPath, 0777, true);
			umask($umask);
		}

		/**
		 * testing is writable in compile function
		 * since we don't actually need to "write"
		 * when we call compiledPath() this
		 */
		if (!realpath($this->compiledPath)) {
			throw new \Exception(__METHOD__.' Cannot locate compiled handlebars folder "'.$this->compiledPath.'"');
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access protected
	 *
	 * @param
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	protected function loadPlugins() : Handlebars
	{
		$cacheFilePath = config('cache path').'/handlebars.plugins.php';

		if ($this->debug || !file_exists($cacheFilePath)) {
			$content = '';

			/* attach the plugins */
			foreach ($this->pluginsPaths as $pluginPath) {
				if (!file_exists(ROOTPATH.$pluginPath)) {
					throw new \Exception('Plugin path "'.$pluginPath.'" not found.');
				}

				$pluginFiles = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ROOTPATH.$pluginPath)), '#^'.$this->pluginRegex.'$#i', \RecursiveRegexIterator::GET_MATCH);

				foreach ($pluginFiles as $pluginFile) {
					$php = trim(php_strip_whitespace($pluginFile[0]));

					if (substr($php, 0, 5) == '<?php') {
						$php = substr($php, 5);
					}

					if (substr($php, 0, 2) == '<?') {
						$php = substr($php, 2);
					}

					if (substr($php, -2) == '?>') {
						$php = substr($php, 0, -2);
					}

					$content .= $php;
				}
			}

			/* save it */
			atomic_file_put_contents($cacheFilePath, '<?php '.$content);
		}

		$plugin = null;

		/* include the cache file */
		include $cacheFilePath;

		if (is_array($plugin)) {
			/* merge with what might already be in there */
			$this->plugins = $plugin + $this->plugins;
		}

		$this->pluginsLoaded = true;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access protected
	 *
	 * @param
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	protected function loadPartials() : Handlebars
	{
		$cacheFilePath = config('cache path').'/handlebars.partials.php';

		if ($this->debug || !file_exists($cacheFilePath)) {
			$partials = [];

			foreach ($this->partialsPaths as $path) {
				if (!file_exists(ROOTPATH.$path)) {
					throw new \Exception('Partials path "'.$path.'" not found.');
				}

				$partialFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ROOTPATH.$path));

				foreach ($partialFiles as $partialFile) {
					$partialFile = $partialFile->getPathName();

					if (!is_dir($partialFile)) {
						$partials[strtolower(trim(substr(str_replace(ROOTPATH.$path, '', $partialFile), 0, -strlen($this->templateExtension)), '/'))] = $partialFile;
					}
				}
			}

			/* save it */
			atomic_file_put_contents($cacheFilePath, '<?php return '.var_export($partials, true).';');
		}

		$this->partialFiles = include $cacheFilePath;

		$this->partialsLoaded = true;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param callable $plugin
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function addPlugin(string $name, callable $plugin) : Handlebars
	{
		/* this is added dynamically to the plugin array so we don't / can't cache it */
		$this->plugins[strtolower($name)] = $plugin;

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param array $plugins
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function addPlugins(array $plugins) : Handlebars
	{
		foreach ($plugins as $name=>$plugin) {
			$this->addPlugin($name, $plugin);
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $templateFile
	 * @param array $data []
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function parse(string $templateFile, array $data=[]) : string
	{
		if (!file_exists($templateFile)) {
			throw new \Exception('Template "'.$templateFile.'" not found ');
		}

		return $this->parseString(file_get_contents($templateFile), $data, $templateFile);
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $templateString
	 * @param array $data []
	 * @param string $type string
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function parseString(string $templateString, array $data=[], string $type='string') : string
	{
		$compiledFilename = $this->compile($templateString, $type);

		if (!$compiledFilename) {
			throw new \Exception('Error compiling template.');
		}

		$templatePhp = include $compiledFilename;

		/* send data into the magic void... */
		return $templatePhp($data);
	}

	/**
	 *
	 * Description Here
	 *
	 * @access protected
	 *
	 * @param string $compiledFilename
	 * @param string $template
	 * @param string $type
	 *
	 * @throws
	 * @return bool
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	protected function compile(string $templateString, string $type) /* bool|string */
	{
		$compiledFilename = $this->compiledPath.'/'.md5($templateString).'.php';

		if (!is_writable(dirname($compiledFilename))) {
			throw new \Exception(__METHOD__.' Cannot write to folder "'.$this->compiledPath.'"');
		}

		/* delete the compiled file if we are in debug mode or if the recompile flag is true */
		if ($this->debug || $this->recompile) {
			if (file_exists($compiledFilename)) {
				if (!unlink($compiledFilename)) {
					throw new \Exception('Cannot unlink compiled template file.');
				}
			}
		}

		if (!file_exists($compiledFilename)) {
			$compiledPhp = $this->_compile($templateString, $type);

			if (empty($compiledPhp)) {
				throw new \Exception('Error compiling handlebars template "'.$type.'".');
			}
		}

		/* incase they are forcing a recompile */
		$this->recompile = false;

		$success = false;

		if (!empty($compiledPhp)) {
			file_put_contents($compiledFilename, '<?php '.$compiledPhp.'?>');

			$success = $compiledFilename;
		}

		return $success;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access protected
	 *
	 * @param string $template
	 * @param string $type
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	protected function _compile(string $templateString, string $type) : string
	{
		/* at first compile load everything */
		if (!$this->partialsLoaded) {
			$this->loadPartials();
		}

		if (!$this->pluginsLoaded) {
			$this->loadPlugins();
		}

		$options = [
			'flags'=>$this->flags,
			'helpers'=>$this->plugins,
			'renderex'=>'/* compiled '.str_replace(ROOTPATH, '', $type).' @ '.date('Y-m-d h:i:s e').' */', /* added to compiled PHP */
			'partialresolver'=>[$this,'partialLoader'],
		];

		/* compile it into a php magic! */
		return LightnCandy::compile($templateString, $options);
	}

	/**
	 *
	 * Handlebars Partial Loader
	 *
	 * @access public
	 *
	 * @param $context
	 * @param string $partialName
	 *
	 * @throws \Exception
	 * @return string
	 *
	 */
	public function partialLoader(array $context, string $partialName) : string
	{
		$key = trim(strtolower($partialName), '/');

		if (!isset($this->partialFiles[$key])) {
			throw new \Exception('Partial "'.$key.'" not found ');
		}

		return file_get_contents($this->partialFiles[$key]);
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $root
	 *
	 * @throws
	 * @return
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function compileAll(string $rootPath) : void
	{
		$templates = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ROOTPATH.$pluginPath)), '#^.*.'.$this->templateExtension.'$#i', \RecursiveRegexIterator::GET_MATCH);

		foreach ($templates as $template) {
			$this->compile(file_get_contents($template), $template);
		}
	}

} /* end class */
