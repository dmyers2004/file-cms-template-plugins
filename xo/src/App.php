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
class App
{
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
	protected $errorThrown = false; /* boolean false or array */

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
	public $file;

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
	public function __construct(string $config_path, array $server = null)
	{
		/* set the most basic exception handler */
		set_exception_handler([$this,'exceptionHandler']);

		if (!file_exists($config_path)) {
			throw new \Exception('Configuration file "'.$config_path.'" does not exist.');
		}

		/* Application Config */
		$this->config = parse_ini_file($config_path, true, INI_SCANNER_NORMAL);

		if (!is_array($this->config)) {
			throw new \Exception('Configuration file "'.$config_path.'" not formatted correctly.');
		}

		define('DEBUG', ($this->config('debug', '0') == '1'));

		if (DEBUG) {
			error_reporting(E_ALL & ~E_NOTICE);
			ini_set('display_errors', 1);
		} else {
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
			ini_set('display_errors', 0);
		}

		/* set a new exception handler if they have one specified */
		set_exception_handler([$this,$this->config('exception handler','exceptionHandler')]);

		/* make cache path ready to use */
		$this->config['cache path'] = ROOTPATH.'/'.trim($this->config('cache path', '/cache'), '/');

		if (!file_exists($this->config['cache path'])) {
			$umask = umask(0);
			mkdir($this->config['cache path'], 0777, true);
			umask($umask);
		}

		/* use what they sent in or the default */
		$this->server = ($server) ?? $_SERVER;

		/* Attach me to the application function / service locator */
		app($this);

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
		$this->config['segs'] = explode('/', $this->config['uri']);

		/* set to default unless something provided */
		$this->config['data path'] = $this->config('data path', '/site/data/');
		$this->config['default template'] = $this->config('default template', 'index');
		$this->config['error template'] = $this->config('error template', 'error');
		$this->config['site path'] = $this->config('site path', '/site/pages');
		$this->config['template extension'] = $this->config('handlebars.template extension', 'html');

		$this->data = $this->config('data', []);
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

		logMsg('Looking for "'.$this->config['template'].'".');

		/* first see if this file actually exists? */
		if ($this->siteFileExists($this->config['template'])) {
			logMsg('Found.');
			$this->template = $this->config['template'];
		} else {
			logMsg('Not Found, Let\'s try to remap.');

			foreach ($this->loadRemapIni(ROOTPATH.$this->config('page remap file', 'remap.ini')) as $regex=>$remap_template) {
				logMsg('Testing Map #^/'.ltrim($regex, '/').'$#im against /'.$this->config['template'].'.');

				if (preg_match('#^/'.ltrim($regex, '/').'$#im', '/'.$this->config['template'], $params, PREG_OFFSET_CAPTURE, 0)) {
					logMsg('Found.');
					$this->template = $this->config['remap_template'] = $remap_template;

					foreach ($params as $key=>$values) {
						logMsg('Captured '.$key.' '.$values[0].'.');
						$this->config['captured'][$key] = $values[0];
					}

					break; /* found one no need to stay in loop */
				}
			}

			if (!$this->siteFileExists($this->template)) {
				logMsg('No.');
				/* nope! error page */
				$this->error('Page Not Found');
			} else {
				logMsg('Yes.');
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
		logMsg('Setup File Service.');

		$filehandler_service = $this->config('services.filehandler', '\xo\FileHandler');

		$this->file = new $filehandler_service($this);

		logMsg('Setup Handlebars Service.');

		$handlebars_service = $this->config('services.handlebars', '\xo\Handlebars');

		$this->handlebars = new $handlebars_service($this);

		$this->handlebars
						->compiledPath($this->config['cache path'])
						->addPartialPath($this->config['handlebars']['partials path'])
						->addPluginPath($this->config['handlebars']['plugin path']);

		logMsg('Output.');

		/* build our view data array */
		$viewData = [
			'data'=>$this->data,
			'page'=>$this->config('page', []),
			'config'=>$this->config,
			'error'=>$this->errorThrown,
		];

		/* was a web page template specified? */
		if ($this->template) {
			$templateFile = ROOTPATH.$this->config['site path'].'/'.trim($this->template, '/').$this->config['template extension'];

			logMsg('View "'.$templateFile.'".');

			$html = $this->handlebars->parse($templateFile, $viewData);
		}

		/**
		 * was an error thrown by the page
		 * or because the template is missing?
		 * This replaces the contents of the previous html
		 * so it can be set by the page
		 */
		if ($this->errorThrown) {
			$templateFile = ROOTPATH.$this->config['site path'].'/'.trim($this->errorThrown['template'], '/').$this->config['template extension'];

			logMsg('View "'.$templateFile.'".');

			$html = $this->handlebars->parse($templateFile, $viewData);
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

		logMsg('Error.');

		$defaults = [
			'msg'=>'Uh Oh!',
			'status'=>404,
			'template'=>$this->config['error template'],
		];

		$options = array_merge($defaults, $options);

		if (!$this->siteFileExists($options['template'])) {
			throw new \Exception(__METHOD__.' view "'.$options['template'].'" not found.');
		}

		/* turn off the default template */
		$this->template = false;

		/* set the header responds code */
		$this->respondsCode($options['status']);

		logMsg('Template "'.$options['template'].'"', 'Status "'.$options['status'].'"', 'Message "'.$options['msg'].'".');

		/* save these for later */
		$this->errorThrown = $options;
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
	public function respondsCode(int $code) : void
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
	public function config(string $name, $default=null)
	{
		if (strpos($name, '.') !== false) {
			list($arg1, $arg2) = explode('.', $name, 2);

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
	public function siteFileExists(string $template_name) : bool
	{
		$path = ROOTPATH.$this->config['site path'].'/'.trim($template_name, '/').'.'.ltrim($this->config['template extension'], '.');

		logMsg('Does "'.$path.'" exist.');

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
	public function loadRemapIni(string $filename) : array
	{
		logMsg('Load Remap INI file "'.$filename.'".');

		$cache_file_path = $this->config['cache path'].'/app.remap.ini.php';

		if ($this->debug || !file_exists($cache_file_path)) {
			$ini = [];

			if (file_exists($filename)) {
				$lines = file($filename);

				foreach ($lines as $line) {
					$line = trim($line);

					if ($line[0] != '#' && $line[0] != ';') {
						$x = str_getcsv($line, '=');

						if (count($x) == 2) {
							$ini[trim($x[0])] = trim($x[1]);
						}
					}
				}
			}

			atomic_file_put_contents($cache_file_path, '<?php return '.var_export($ini, true).';');
		}

		return include $cache_file_path;
	}

	public function exceptionHandler($exception) {
		echo '<div style="padding: 32px;font-family:monospace"><h2>Uh oh!<h2>Uncaught exception: '.$exception->getMessage().'</div>';
	  exit(1);
	}

} /* end class */
