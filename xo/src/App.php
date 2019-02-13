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
		$this->config['data data'] = $this->config('data path','/site/data/');
		$this->config['default template'] = $this->config('default template','index');
		$this->config['error template'] = $this->config('error template','error');

		$this->handlebars = new Handlebars($this);
		$this->filehandler = new FileHandler($this);
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

		/* first see if this file actually exists? */
		if ($this->filehandler->template_exists($this->config['template'])) {
			$this->template = $this->config['template'];
		} else {
			foreach ($this->filehandler->load_remap_ini(ROOTPATH.$this->config('page remap file','remap.ini')) as $regex=>$remap_template) {
				if (preg_match('#^/'.ltrim($regex,'/').'$#im','/'.$this->config['template'],$params,PREG_OFFSET_CAPTURE,0)) {
					$this->template = $this->config['remap_template'] = $remap_template;

					foreach ($params as $key=>$values) {
						$this->config['captured'][$key] = $values[0];
					}

					break; /* found one no need to stay in loop */
				}
			}
		}
		
		/* does this template even exist? */
		if (!$this->filehandler->template_exists($this->template)) {
			/* nope! error page */
			$this->error();
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
		/* build our view data array */
		$view_data = ['data'=>$this->data,'config'=>$this->config,'error'=>$this->error_thrown];
		
		/* was a web page template specified? */
		if ($this->template) {
			$html = $this->handlebars->parse($this->template,$view_data);
		}

		/* was an error thrown? */
		if ($this->error_thrown) {
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
		$defaults = [
			'msg'=>'Uh Oh!',
			'status'=>404,
			'template'=>$this->config['error template'],
		];

		if (is_string($input)) {
			$options['msg'] = $input;
		} else {
			$options = $input;
		}

		$options = array_merge($defaults,$options);

		/* turn off the default template */
		$this->template = false;

		/* set the header responds code */
		$this->responds_code($options['status']);

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
		return (isset($this->config[$name])) ? $this->config[$name] : $default;
	}

} /* end class */

/* wrapper to get the application singleton */
function app() : \xo\app { global $app; return $app; }