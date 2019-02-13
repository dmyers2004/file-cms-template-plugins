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

class Handlebars implements HandlebarsInterface {
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
	protected $template_extension = 'html';

	/**
	 * LightnCandy\LightnCandy Default Compile Flags
	 *
	 * @var integer
	 */
	protected $default_flags = LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_NAMEDARG | LightnCandy::FLAG_ADVARNAME | LightnCandy::FLAG_ERROR_LOG;

	/**
	 * Compile Cache Path (absolute)
	 *
	 * @var string
	 */
	protected $compiled_path = CACHEPATH;

	/**
	 * Handlebars Plugin Regular Expression
	 *
	 * @var string
	 */
	protected $plugin_regex = '(.*)\.plugin\.php';

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
	protected $plugins_paths = [];

	/**
	 * Internal storage for folders to search for partials
	 *
	 * @var array
	 */
	protected $partials_paths = [];

	/**
	 * Internal storage for partial name to file path
	 *
	 * @var array
	 */
	protected $partial_files = [];

	/**
	 * Internal storage to track if we've loaded partials.
	 *
	 * @var bool
	 */
	protected $partials_loaded = false;

	/**
	 * Internal storage to track if we've loaded plugins.
	 *
	 * @var bool
	 */
	protected $plugins_loaded = false;

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
		$this
			->debug(config('handlebars.debug',false))
			->flags(config('handlebars.flags',$this->default_flags))
			->template_extension(config('handlebars.template extension',$this->template_extension))
			->plugin_regex(config('handlebars.plugin regex',$this->plugin_regex))
			->compiled_path(config('handlebars.cache path',$this->compiled_path))
			->add_partial_path(config('handlebars.partials path'))
			->add_plugin_path(config('handlebars.plugin path'));
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $plugin_path
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function add_plugin_path($plugin_path) : Handlebars
	{
		if (is_array($plugin_path)) {
			foreach ($plugin_path as $pp) {
				$this->add_plugin_path($pp);
			}

			return $this;
		}

		if (!empty($plugin_path)) {
			$path = '/'.trim($plugin_path,'/');
	
			$this->plugins_paths[$path] = $path;
	
			$this->plugins_loaded = false;
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $partials_path
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function add_partial_path($partials_path) : Handlebars
	{
		if (is_array($partials_path)) {
			foreach ($partials_path as $pp) {
				$this->add_partial_path($pp);
			}

			return $this;
		}

		if (!empty($partials_path)) {
			$path = '/'.trim($partials_path,'/');
	
			$this->partials_paths[$path] = $path;
	
			$this->partials_loaded = false;
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $template_extension
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function template_extension(string $template_extension) : Handlebars
	{
		$this->template_extension = '.'.trim($template_extension,'.');

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $plugin_regex
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function plugin_regex(string $plugin_regex) : Handlebars
	{
		$this->plugin_extension = $plugin_regex;

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
	public function set_flag(int $flag) : Handlebars
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
	public function clear_flag(int $flag) : Handlebars
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
	public function debug(bool $bool=true) : Handlebars
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
	 * @param string $compiled_path
	 *
	 * @throws
	 * @return Handlebars_helper
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function compiled_path(string $compiled_path) : Handlebars
	{
		$this->compiled_path = '/'.trim($compiled_path,'/');

		/* testing is writable in compile since we don't actually need to "write" when we change this */
		if (!realpath($this->compiled_path)) {
			mkdir($this->compiled_path,0777,true);

			if (!realpath($this->compiled_path)) {
				throw new \Exception(__METHOD__.' Cannot locate compiled handlebars folder "'.$this->compiled_path.'"');
			}
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
	protected function load_plugins() : Handlebars
	{
		$cache_file_path = CACHEPATH.'/handlebars.plugins.php';

		if ($this->debug || !file_exists($cache_file_path)) {
			$content = '';

			/* attach the plugins */
			foreach ($this->plugins_paths as $plugin_path) {
				$plugin_files = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ROOTPATH.$plugin_path)),'#^'.$this->plugin_regex.'$#i',\RecursiveRegexIterator::GET_MATCH);

				foreach ($plugin_files as $plugin_file) {
					$php = trim(file_get_contents($plugin_file[0]));

					if (substr($php,0,5) == '<?php') {
						$php = substr($php,5);
					}

					if (substr($php,0,2) == '<?') {
						$php = substr($php,2);
					}

					if (substr($php,-2) == '?>') {
						$php = substr($php,0,-2);
					}

					$content .= $php;
				}
			}

			/* save it */
			atomic_file_put_contents($cache_file_path,'<?php '.$content);
		}

		$plugin = null;

		/* include the cache file */
		include $cache_file_path;

		if (is_array($plugin)) {
			/* merge with what might already be in there */
			$this->plugins = $plugin + $this->plugins;
		}

		$this->plugins_loaded = true;

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
	protected function load_partials() : Handlebars
	{
		$cache_file_path = CACHEPATH.'/handlebars.partials.php';

		if ($this->debug || !file_exists($cache_file_path)) {
			$partials = [];

			foreach ($this->partials_paths as $path) {
				if (!file_exists(ROOTPATH.$path)) {
					throw new \Exception('Partials path "'.$path.'" not found.');
				}

				$partial_files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ROOTPATH.$path));

				foreach ($partial_files as $partial_file) {
					$partial_file = $partial_file->getPathName();

					if (!is_dir($partial_file)) {
						$partials[strtolower(trim(substr(str_replace(ROOTPATH.$path,'',$partial_file),0,-strlen($this->template_extension)),'/'))] = $partial_file;
					}
				}
			}

			/* save it */
			atomic_file_put_contents($cache_file_path,'<?php return '.var_export($partials,true).';');
		}

		$this->partial_files = include $cache_file_path;

		$this->partials_loaded = true;

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
	public function add_plugin(string $name,callable $plugin) : Handlebars
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
	public function add_plugins(array $plugins) : Handlebars
	{
		foreach ($plugins as $name=>$plugin) {
			$this->add_plugin($name,$plugin);
		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $template_file
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
	public function parse(string $template_file, array $data=[]) : string
	{
		if (!file_exists($template_file)) {
			throw new \Exception('Template "'.$template_file.'" not found ');
		}

		return $this->parse_string(file_get_contents($template_file),$data,$template_file);
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $template_string
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
	public function parse_string(string $template_string, array $data=[], string $type='string') : string
	{
		$compiled_filename = $this->compile($template_string,$type);
		
		if (!$compiled_filename) {
			throw new \Exception('Error compiling template.');
		}

		$template_php = include $compiled_filename;

		/* send data into the magic void... */
		return $template_php($data);
	}

	/**
	 *
	 * Description Here
	 *
	 * @access protected
	 *
	 * @param string $compiled_filename
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
	protected function compile(string $template_string, string $type)
	{
		$compiled_filename = $this->compiled_path.'/'.md5($template_string).'.php';

		if (!is_writable(dirname($compiled_filename))) {
			throw new \Exception(__METHOD__.' Cannot write to folder "'.$this->compiled_path.'"');
		}

		/* delete the compiled file if we are in debug mode */
		if ($this->debug || $this->recompile) {
			if (file_exists($compiled_filename)) {
				unlink($compiled_filename);
			}
		}

		if (!file_exists($compiled_filename)) {
			$compiled_php = $this->_compile($template_string,$type);

			if (empty($compiled_php)) {
				echo 'Error compiling handlebars template "'.$template.'".'.PHP_EOL;
			}
		}
		
		/* incase they are forcing a recompile */ 
		$this->recompile = false;

		$success = false;

		if (!empty($compiled_php)) {
			file_put_contents($compiled_filename,'<?php '.$compiled_php.'?>');

			$success = $compiled_filename;
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
	protected function _compile(string $template_string,string $type) : string
	{
		/* at first compile load everything */
		if (!$this->partials_loaded) {
			$this->load_partials();
		}

		if (!$this->plugins_loaded) {
			$this->load_plugins();
		}

		$options = [
			'flags'=>$this->flags,
			'helpers'=>$this->plugins,
			'renderex'=>'/* compiled '.str_replace(ROOTPATH,'',$type).' @ '.date('Y-m-d h:i:s e').' */', /* added to compiled PHP */
			'partialresolver'=>function($context,$partial_name) { /* include / partial handler */
				$key = trim(strtolower($partial_name),'/');

				if (!isset($this->partial_files[$key])) {
					throw new \Exception('Partial "'.$key.'" not found ');
				}

				return file_get_contents($this->partial_files[$key]);
			},
		];

		/* compile it into a php magic! */
		return LightnCandy::compile($template_string,$options);
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
	public function compile_all($root)
	{
		if (empty(trim($this->template_extension,'.'))) {
			throw new \Exception('Template extension is empty.');
		}

		$templates = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

		foreach ($templates as $template) {
			if (!is_dir($template)) {
				$fileinfo = pathinfo($template);

				if ($fileinfo['extension'] === trim($this->template_extension,'.')) {
					if (!$this->compile(file_get_contents($template),$template)) {
						echo 'Error compiling handlebars template "'.$template.'".'.PHP_EOL;
					}
				}
			}
		}
	}

} /* end class */
