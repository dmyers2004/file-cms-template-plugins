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
	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	protected $server = [];

	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	protected $error_thrown = false; /* boolean false or array */

	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	protected $template = false; /* template loaded */

	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	protected $config = []; /* get this using the config method */

	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	public $data = [];

	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	public $handlebars;

	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	public $filehandler;
	
	/**
	 * errors configuration array
	 *
	 * @var {{}}
	 */
	protected $handlebars_service;

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $config_path
	 * @param array $server null
	 *
	 * @throws
	 * @return
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function __construct(string $config_path,array $server = null)
	{
		if (!file_exists($config_path)) {
			throw new \Exception('Configuration file "'.$config_path.'" does not exist.');
		}

		$ini = parse_ini_file($config_path,true,INI_SCANNER_NORMAL);

		if (!$ini) {
			throw new \Exception('Configuration file "'.$config_path.'" not formatted correctly.');
		}

		/* Application Config */
		$this->config = $ini;

		/* use what they sent in or the default */
		$this->server = ($server) ?? $_SERVER;

		define('CACHEPATH',ROOTPATH.'/'.trim($this->config('cache path','/cache'),'/'));

		/* Put ANY (POST, PUT, DELETE) posted into into $_POST */
		parse_str(file_get_contents('php://input'), $_POST);

		/* is this a ajax request? */
		$this->config['is_ajax'] = (isset($this->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ? true : false;

		/* what's our base url */
		$this->config['base_url'] = trim('http://'.$this->server['HTTP_HOST'].dirname($this->server['SCRIPT_NAME']), '/');

		/* The GET method is default so controller methods look like openAction, others are handled directly openPostAction, openPutAction, openDeleteAction, etc... */
		$this->config['request'] = strtolower($this->server['REQUEST_METHOD']);

		/* get the uri (uniform resource identifier) */
		$this->config['uri'] = trim(urldecode(substr(parse_url($this->server['REQUEST_URI'], PHP_URL_PATH), strlen(dirname($this->server['SCRIPT_NAME'])))), '/');

		/* get the uri pieces */
		$this->config['segs'] = explode('/',$this->config['uri']);

		/* set to default unless something provided */
		$this->config['data path'] = $this->config('data path','/site/data/');
		$this->config['default template'] = $this->config('default template','index');
		$this->config['error template'] = $this->config('error template','error');
		$this->config['site path'] = $this->config('site path','/site/pages');
		$this->config['template extension'] = $this->config('template extension','html');

		$this->handlebars_service = ($services['services.handlebars']) ?? '\xo\handlebars';

		$filehandler_service = ($services['services.filehandler']) ?? '\xo\filehandler';

		$this->filehandler = new $filehandler_service($this);
		
		$this->data = $this->config('data',[]);
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
	 * @return App
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function route() : App
	{
		$this->config['template'] = (empty($this->config['uri'])) ? $this->config['default template'] : $this->config['uri'];

		$this->config['remap_template'] = false;

		log_msg('Looking for "'.$this->config['template'].'".');

		/* first see if this file actually exists? */
		if ($this->site_file_exists($this->config['template'])) {
			log_msg('Found.');
			$this->template = $this->config['template'];
		} else {
			log_msg('Not Found, Let\'s try to remap.');

			foreach ($this->load_remap_ini(ROOTPATH.$this->config('page remap file','remap.ini')) as $regex=>$remap_template) {
				log_msg('Testing Map #^/'.ltrim($regex,'/').'$#im against /'.$this->config['template'].'.');

				if (preg_match('#^/'.ltrim($regex,'/').'$#im','/'.$this->config['template'],$params,PREG_OFFSET_CAPTURE,0)) {
					log_msg('Found.');
					$this->template = $this->config['remap_template'] = $remap_template;

					foreach ($params as $key=>$values) {
						log_msg('Captured '.$key.' '.$values[0].'.');
						$this->config['captured'][$key] = $values[0];
					}

					break; /* found one no need to stay in loop */
				}
			}

			if (!$this->site_file_exists($this->template)) {
				log_msg('No.');
				/* nope! error page */
				$this->error('Page Not Found');
			} else {
				log_msg('Yes.');
			}

		}

		return $this;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param bool $echo false
	 *
	 * @throws
	 * @return string
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function output(bool $echo = false) : string
	{
		$this->handlebars = new $this->handlebars_service($this);

		$this->handlebars
			->add_partial_path($this->config('partials path'))
			->add_plugin_path($this->config('plugin path'));

		log_msg('Output.');

		/* build our view data array */
		$view_data = ['data'=>$this->data,'config'=>$this->config,'error'=>$this->error_thrown];

		$template_file = ROOTPATH.$this->config['site path'].'/'.trim($this->template,'/').$this->config['template extension'];

		/* was a web page template specified? */
		if ($template_file) {
			log_msg('View "'.$template_file.'".');

			$html = $this->handlebars->parse($template_file,$view_data);
		}

		/* was an error thrown by the page or because the template is missing? This replaces the contents of the previous html */
		if ($this->error_thrown) {
			log_msg('View "'.$this->error_thrown['template'].'".');

			$html = $this->handlebars->parse($this->error_thrown['template'],$view_data);
		}

		if ($echo) {
			echo $html;
		}

		return $html;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param $input []
	 *
	 * @throws
	 * @return void
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function error($input=[]) : void
	{
		if (is_string($input)) {
			$options['msg'] = $input;
		} elseif (is_array($input)) {
			$options = $input;
		} else {
			throw new \Exception(__METHOD__.' input neither a string or array.');
		}

		log_msg('Error.');

		$defaults = [
			'msg'=>'Uh Oh!',
			'status'=>404,
			'template'=>$this->config['error template'],
		];

		$options = array_merge($defaults,$options);

		if (!$this->site_file_exists($options['template'])) {
			throw new \Exception(__METHOD__.' view "'.$options['template'].'" not found.');
		}

		/* turn off the default template */
		$this->template = false;

		/* set the header responds code */
		$this->responds_code($options['status']);

		log_msg('Template "'.$options['template'].'".');
		log_msg('Status "'.$options['status'].'".');
		log_msg('Message "'.$options['msg'].'".');

		/* save these for later */
		$this->error_thrown = $options;
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param int $code
	 *
	 * @throws
	 * @return void
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function responds_code(int $code) : void
	{
		http_response_code($code);
	}

	/**
	 *
	 * Description Here
	 *
	 * @access public
	 *
	 * @param string $name
	 * @param $default null
	 *
	 * @throws
	 * @return
	 *
	 * #### Example
	 * ```
	 *
	 * ```
	 */
	public function config(string $name,$default=null)
	{
		if (strpos($name,'.') !== false) {
			list($arg1,$arg2) = explode('.',$name,2);
			
			$value = (isset($this->config[$arg1][$arg2])) ? $this->config[$arg1][$arg2] : $default;
		} else {
			$value = (isset($this->config[$name])) ? $this->config[$name] : $default;
		}
		
		return $value;
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
	public function site_file_exists(string $template_name) : bool
	{
		$path = ROOTPATH.$this->config['site path'].'/'.trim($template_name,'/').'.'.ltrim($this->config['template extension'],'.');
	
		log_msg('Does "'.$path.'" exist.');
		
		return file_exists($path);
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
		log_msg('Load Remap INI file "'.$filename.'".');

		$ini = [];

		if (file_exists($filename)) {
			$lines = file($filename);

			foreach ($lines as $line) {
				$line = trim($line);

				if ($line[0] != '#' && $line[0] != ';') {
					$x = str_getcsv($line,'=');

					if (count($x) == 2) {
						$ini[trim($x[0])] = trim($x[1]);
					}
				}
			}

		}

		return $ini;
	}

} /* end class */