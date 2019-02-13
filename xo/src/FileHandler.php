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

class FileHandler {
	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */	
	protected $app;
	
	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $app
	 *
	 * @throws
	 * @return 
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $template_name
	 *
	 * @throws
	 * @return bool
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function template_exists(string $template_name) : bool
	{
		return file_exists(ROOTPATH.$this->app->config('web path','/site/pages').'/'.trim($template_name,'/').'.'.ltrim($this->app->config('template extension','html'),'.'));
	}

	/* auto detect by extension */
	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return 
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function get(string $filename)
	{
		$ext = pathinfo($filename,PATHINFO_EXTENSION);

		$data = [];

		switch ($ext) {
			case 'md':
				$data = $this->get_md($filename);
			break;
			case 'yaml':
				$data = $this->get_yaml($filename);
			break;
			case 'ini':
				$data = $this->get_ini($filename);
			break;
			case 'array':
				$data = $this->get_array($filename);
			break;
			case 'json':
				$data = $this->get_json($filename);
			break;
		}

		return $data;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function get_array(string $filename) : array
	{
		if (substr($filename,-6) == '.array') {
			$filename = substr($filename,0,-6);
		}

		$filename = ROOTPATH.'/'.$this->create_path($this->app->config('data data').'/'.$filename.'.array');

		$array = '';

		if (file_exists($filename)) {
			$array = include $filename;
		}

		return $array;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function get_json(string $filename) : array
	{
		if (substr($filename,-5) == '.json') {
			$filename = substr($filename,0,-5);
		}

		$filename = ROOTPATH.'/'.$this->create_path($this->app->config('data data').'/'.$filename.'.json');

		$array = '';

		if (file_exists($filename)) {
			$array = json_decode(file_get_contents($filename),true);
		}

		return $array;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function get_md(string $filename) : string
	{
		if (substr($filename,-3) == '.md') {
			$filename = substr($filename,0,-3);
		}

		$filename = ROOTPATH.'/'.$this->create_path($this->app->config('data data').'/'.$filename.'.md');

		$html = '';

		if (file_exists($filename)) {
			$html = \Michelf\Markdown::defaultTransform(file_get_contents($filename));
		}

		return $html;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function get_yaml(string $filename) : array
	{
		if (substr($filename,-5) == '.yaml') {
			$filename = substr($filename,0,-5);
		}

		$filename = ROOTPATH.'/'.$this->create_path($this->app->config('data data').'/'.$filename.'.yaml');

		$yaml = '';

		if (file_exists($filename)) {
			$yaml = yaml_parse(file_get_contents($filename));
		}

		return $yaml;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function get_ini(string $filename) : array
	{
		if (substr($filename,-4) == '.ini') {
			$filename = substr($filename,0,-4);
		}

		$filename = ROOTPATH.'/'.$this->create_path($this->app->config('data data').'/'.$filename.'.ini');

		$ini = [];

		if (file_exists($filename)) {
			$ini = parse_ini_file($filename,true,INI_SCANNER_NORMAL);
		}

		return $ini;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $filename
	 *
	 * @throws
	 * @return array
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function load_remap_ini(string $filename) : array
	{
		$ini = [];

		if (file_exists($filename)) {
			$lines = file($filename);

			foreach ($lines as $line) {
				$line = trim($line);

				if ($line[0] != '#' && $line[0] != ';') {
					$x = str_getcsv($line,'=');

					$ini[trim($x[0])] = trim($x[1]);
				}
			}

		}

		return $ini;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $path
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function create_path(string $path) : string
	{
		return str_replace('//','/',$path);
	}
} /* end class */
