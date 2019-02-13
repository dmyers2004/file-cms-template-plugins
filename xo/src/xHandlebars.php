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
	protected $debug = false;
	protected $flags;
	protected $compiled_path;
	protected $plugins = [];
	protected $partials_path;
	protected $template_extension;
	protected $plugin_extension;
	protected $web_path;
	protected $plugin_path;

	public function __construct(&$app)
	{
		$this->app = $app;
		
		$this
			->debug($app->config('handlebars debug',false))
			->flags(LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_NAMEDARG | LightnCandy::FLAG_ADVARNAME | LightnCandy::FLAG_NOESCAPE)
			->web_path($app->config('web path','/site/pages'))
			->template_extension($app->config('template extension','html'))
			->partials_path($app->config('partials path','/site/partials'))
			->compiled_path($app->config('cache path','/cache'))
			->plugin_path($app->config('plugin path','/plugins'))
			->plugin_extension($app->config('plugin extension','plugin.php'))
			->load_plugins();
	}

	public function plugin_path(string $plugin_path) : Handlebars
	{
		$this->plugin_path = ROOTPATH.'/'.trim($plugin_path,'/');

		/* chain-able */
		return $this;
	}

	public function partials_path(string $partials_path) : Handlebars
	{
		$this->partials_path = ROOTPATH.'/'.trim($partials_path,'/');

		/* chain-able */
		return $this;
	}

	public function template_extension(string $template_extension) : Handlebars
	{
		$this->template_extension = '.'.trim($template_extension,'.');

		/* chain-able */
		return $this;
	}

	public function plugin_extension(string $plugin_extension) : Handlebars
	{
		$this->plugin_extension = '.'.trim($plugin_extension,'.');

		/* chain-able */
		return $this;
	}

	public function web_path(string $web_path) : Handlebars
	{
		$this->web_path = ROOTPATH.'/'.trim($web_path,'/');

		/* chain-able */
		return $this;
	}

	public function flags(int $flags) : Handlebars
	{
		$this->flags = $flags;

		/* chain-able */
		return $this;
	}

	public function debug(bool $bool=true) : Handlebars
	{
		$this->debug = $bool;

		/* chain-able */
		return $this;
	}

	public function compiled_path(string $compiled_path) : Handlebars
	{
		$this->compiled_path = ROOTPATH.'/'.trim($compiled_path,'/');

		/* testing is writable in compile since we don't actually need to "write" when we change this */
		if (!realpath($this->compiled_path)) {
			mkdir($this->compiled_path,0777,true);
			
			if (!realpath($this->compiled_path)) {
				throw new \Exception(__METHOD__.' Cannot locate compiled handlebars folder "'.$this->compiled_path.'"');
			}
		}

		/* chain-able */
		return $this;
	}

	public function load_plugins() : Handlebars
	{
		$search = $this->plugin_path.'/*'.$this->plugin_extension;

		$plugins = glob($search);

		/* attach the plugins */
		foreach ($plugins as $plugin_file) {
			$name = basename($plugin_file,$this->plugin_extension);

			$name = str_replace('.',':',$name);

			$this->add_plugin($name,include $plugin_file);
		}

		/* chain-able */
		return $this;
	}

	public function add_plugin(string $name,callable $plugin) : Handlebars
	{
		$this->plugins[strtolower($name)] = $plugin;

		/* chain-able */
		return $this;
	}

	public function parse(string $view,array $data=[]) : string
	{
		$this->app->log('Handlebars Parse "'.$view.'".');
		
		$template_file = $this->web_path.'/'.trim($view,'/').$this->template_extension;

		if (!file_exists($template_file)) {
			throw new \Exception('Could not locate your handlebars template');
		}

		return $this->parse_string(file_get_contents($template_file),$data);
	}

	public function parse_string(string $template_string, array $data=[]) : string
	{
		$compiled_file = $this->compiled_path.'/'.md5($template_string).'.php';

		/* delete the compiled file if we are in debug mode */
		if ($this->debug) {
			if (file_exists($compiled_file)) {
				unlink($compiled_file);
			}
		}

		/* compile if it's not there */
		if (!file_exists($compiled_file)) {
			if (!$this->compile($compiled_file,$template_string)) {
				throw new \Exception('Error compiling your handlebars template');
			}
		}

		$template_php = include $compiled_file;

		/* send data into the magic void... */
		return $template_php($data);
	}

	public function get_partial(string $template_name) : string
	{
		$template_path = $this->partials_path.'/'.$template_name.$this->template_extension;

		if (!file_exists($template_path)) {
			throw new \Exception('Partial '.$template_path.' not found.');
		}

		/* if we get anything but false that means we found something so return it's contents */
		return file_get_contents($template_path);
	}

	/* template file path */
	protected function compile(string $complie_file,string $template) : bool
	{
		if (!is_writable($this->compiled_path)) {
			throw new \Exception(__METHOD__.' Cannot write to folder "'.$this->compiled_path.'"');
		}

		$options = [
			'flags'=>$this->flags,
			'helpers'=>$this->plugins,
			'renderex'=>'/* '.$template_name.' compiled @ '.date('Y-m-d h:i:s e').' */', /* added to compiled PHP */
			'partialresolver'=>function($context,$template_name) { /* include / partial handler */
				$template_path = $this->partials_path.'/'.$template_name.$this->template_extension;

				if (!file_exists($template_path)) {
					throw new \Exception('Partial '.$template_path.' not found.');
				}

				/* if we get anything but false that means we found something so return it's contents */
				return file_get_contents($template_path);
			},
		];

		/* compile it into a php magic! */
		$compiled_php = LightnCandy::compile($template,$options);

		return ($compiled_php) ? (bool)file_put_contents($complie_file,'<?php '.$compiled_php.'?>') : false;
	}

} /* end class */