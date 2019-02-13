<?php
/**
 * XO
 *
 * File Based CMS
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
class App {
	protected $server = [];
	protected $site_data;
	protected $default_template;
	protected $error_template;
	protected $error_thrown = false;
	protected $template = false;

	public $handlebars;
	public $data = [];
	public $config = [];

	public function __construct(string $config_path = null,array $server = null)
	{
		if (!file_exists($config_path)) {
			throw new \Exception('Configuration file "'.$config_path.'" does not exist.');
		}

		$ini = parse_ini_file($config_path,true,INI_SCANNER_NORMAL);

		if (!$ini) {
			throw new \Exception('Configuration file "'.$config_path.'" not formatted correctly.');
		}

		$this->config = $ini;

		$this->server = $server;
		
		/* Put ANY (POST, PUT, DELETE) posted into into $_POST */
		parse_str(file_get_contents('php://input'), $_POST);

		$this->config['is_ajax'] = (isset($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ? true : false;
		$this->config['base_url'] = trim('http://'.$this->server['HTTP_HOST'].dirname($this->server['SCRIPT_NAME']), '/');

		/* The GET method is default so controller methods look like openAction, others are handled directly openPostAction, openPutAction, openDeleteAction, etc... */
		$this->config['request'] = strtolower($this->server['REQUEST_METHOD']);

		/* get the uri (uniform resource identifier) */
		$this->config['uri'] = trim(urldecode(substr(parse_url($this->server['REQUEST_URI'], PHP_URL_PATH), strlen(dirname($this->server['SCRIPT_NAME'])))), '/');

		/* get the uri pieces */
		$this->config['segs'] = explode('/',$this->config['uri']);

		/* real internal properties */
		$this->site_data = $this->config('data path','/site/data/');
		$this->default_template = $this->config('default template','index');
		$this->error_template = $this->config('error template','error');

		$this->handlebars = new Handlebars($this);
	}

	public function route() : App
	{
		$this->config['template'] = (empty($this->config['uri'])) ? $this->build_path($this->default_template,'') : $this->config['uri'];

		$this->config['remap_template'] = false;

		/* first see if this file actually exists? */
		if ($this->template_exists($this->config['template'])) {
			$this->template = $this->config['template'];
		} else {
			foreach ($this->load_remap_ini(ROOTPATH.$this->config('page remap file','remap.ini')) as $regex=>$remap_template) {
				if (preg_match('#^/'.ltrim($regex,'/').'$#im','/'.$this->config['template'],$params,PREG_OFFSET_CAPTURE,0)) {
					$this->template = $this->config['remap_template'] = $remap_template;

					foreach ($params as $key=>$values) {
						$this->config['captured'][$key] = $values[0];
					}

					break; /* found one no need to stay in loop */
				}
			}
		}

		if (!$this->template_exists($this->template)) {
			$this->error();
		}

		return $this;
	}

	public function template_exists(string $template_name) : bool
	{
		return file_exists(ROOTPATH.$this->config('web path','/site/pages').'/'.trim($template_name,'/').'.'.ltrim($this->config('template extension','html'),'.'));
	}

	public function output(bool $echo = false) : string
	{
		/* was an web page template specified? */
		if ($this->template) {
			$html = $this->handlebars->parse($this->template,$this->config);
		}

		/* was an error thrown? */
		if ($this->error_thrown) {
			$html = $this->handlebars->parse($this->error_thrown['template'],array_merge($this->config,$this->error_thrown));
		}

		if ($echo) {
			echo $html;
		}

		return $html;
	}

	public function error($input=[]) : void
	{
		if (is_string($input)) {
			$options['msg'] = $input;
		} else {
			$options = $input;
		}

		$defaults = [
			'msg'=>'Uh Oh!',
			'status'=>404,
			'template'=>$this->error_template,
		];

		$options = array_merge($defaults,$options);

		/* turn off the default template */
		$this->template = false;

		/* set the header responds code */
		$this->responds_code($options['status']);

		/* save these for later */
		$this->error_thrown = $options;
	}

	public function responds_code(int $code) : void
	{
		http_response_code($code);
	}

	public function config(string $name,$default=null) 
	{
		return (isset($this->config[$name])) ? $this->config[$name] : $default;
	}

	/* auto detect by extension */
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

	public function get_array(string $filename) : array
	{
		if (substr($filename,-6) == '.array') {
			$filename = substr($filename,0,-6);
		}

		$filename = ROOTPATH.'/'.$this->build_path($this->site_data,$filename,'.array');

		$array = '';

		if (file_exists($filename)) {
			$array = include $filename;
		}

		return $array;
	}

	public function get_json(string $filename) : array
	{
		if (substr($filename,-5) == '.json') {
			$filename = substr($filename,0,-5);
		}

		$filename = ROOTPATH.'/'.$this->build_path($this->site_data,$filename,'.json');

		$array = '';

		if (file_exists($filename)) {
			$array = json_decode(file_get_contents($filename),true);
		}

		return $array;
	}

	public function get_md(string $filename) : string
	{
		if (substr($filename,-3) == '.md') {
			$filename = substr($filename,0,-3);
		}

		$filename = ROOTPATH.'/'.$this->build_path($this->site_data,$filename,'.md');

		$html = '';

		if (file_exists($filename)) {
			$html = \Michelf\Markdown::defaultTransform(file_get_contents($filename));
		}

		return $html;
	}

	public function get_yaml(string $filename) : array
	{
		if (substr($filename,-5) == '.yaml') {
			$filename = substr($filename,0,-5);
		}

		$filename = ROOTPATH.'/'.$this->build_path($this->site_data,$filename,'.yaml');

		$yaml = '';

		if (file_exists($filename)) {
			$yaml = yaml_parse(file_get_contents($filename));
		}

		return $yaml;
	}

	public function get_ini(string $filename) : array
	{
		if (substr($filename,-4) == '.ini') {
			$filename = substr($filename,0,-4);
		}

		$filename = ROOTPATH.'/'.$this->build_path($this->site_data,$filename,'.ini');

		$ini = [];

		if (file_exists($filename)) {
			$ini = parse_ini_file($filename,true,INI_SCANNER_NORMAL);
		}

		return $ini;
	}

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

	public function build_path(string $string) : string
	{
		//return str_replace('//','/',$string);
		
		$args = func_get_args();

		/* last piece - try to clean up / normalize */
		$ext = array_pop($args);
		$ext = (!empty($ext)) ? '.'.trim($ext,'.') : '';

		foreach ($args as $idx=>$arg) {
			$args[$idx] = '/'.trim($args[$idx],'/');
		}

		$path = implode('',$args);
		$path = preg_replace('@[/]+@m','/', $path);
		$path = ltrim($path,'/');

		return $path.$ext;
	}

} /* end class */