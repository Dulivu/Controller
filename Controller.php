<?php

abstract class Controller {
	public $_data = array();
	public $_view = null;
	public $_view_ext = 'tpl';
	public $_view_path = '';
	public $_master = null;
	public static $controller = 0;

	public function __set ($name, $value) {
		$this->_data[$name] = $value;
	}

	public function __get ($name) {
		if (array_key_exists($name, $this->_data))
			return $this->_data[$name];
		elseif (isset($_POST[$name]))
			return $_POST[$name];
		elseif (isset($_GET[$name]))
			return $_GET[$name];
		else return null;
	}

	public function request ($key) {
		return isset($_POST[$key]) ? $_POST[$key] : isset($_GET[$key]) ? $_GET[$key] : null;
	}

	public function post ($key) {
		return isset($_POST[$key]) ? $_POST[$key] : null;
	}

	public function get ($key) {
		return isset($_GET[$key]) ? $_GET[$key] : null;
	}

	public function initialize () {}
	public function load () {}
	
	public function merge ($arr) {
		if (gettype($arr) == 'array')
			$this->_data = array_merge($this->_data, $arr);
	}

	public function loadTemplate () {
		extract($this->_data);
		$__view = $this->_view ?: get_called_class();
		$__view .= '.'.$this->_view_ext;
		$__view = $this->_view_path.$__view;

		if (File::exists($__view)) include($__view);
		else throw new Exception("Template '{$__view}' not found");

		if ($this->_master) {
			if (File::exists($this->_master)) {
				$__content__ = ob_get_clean();
				include($this->_master);
			}
			else throw new Exception("Master template '{$this->_master}' not found");
		}

		return ob_get_clean();
	}

	protected function exec ($method, $args) {
		if(!method_exists($this, "$method")) {
			throw new Exception("Method '$method' does not exist.");
			return false;
		}
		return call_user_func_array(array($this, "$method"), $args);
	}

	public static function run () {
		$klass = get_called_class();
		$pr = isset($GLOBALS['preventRun']) ? $GLOBALS['preventRun'] : false;
		if ($pr) { $GLOBALS['preventRun'] = false; return;}
		if (Controller::$controller++) return;

		$page = new $klass;

		$configFlag = false;
		if (File::exists(__DIR__.'\\Main.php')) {
			include_once(__DIR__.'\\Main.php');
			$configFlag = true;
		}

		if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'application/json') !== false) {
			$raw = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
			$arg = JSON::decode($raw, true);
			$method = str_replace($_SERVER["SCRIPT_NAME"]."/", "", $_SERVER["REQUEST_URI"]);
			header('Cache-Control: no-cache, must-revalidate');
			header('Content-type: application/json; charset=utf-8');
			try {
				if ($configFlag && method_exists('Main', 'initialize')) Main::initialize($page);
				$page->initialize();

				if (!method_exists($page, "_$method"))
					throw new Exception("Method '$method' does not exist");

				$r = call_user_func_array(array($page, "_$method"), $arg);
				if ($configFlag && method_exists('Main', 'exec')) Main::exec($page, $r, $m, $arr);
				header("HTTP/1.0 200 OK");
				$t = gettype($r);
				echo ($t == "array" || $t == "object") ? JSON::encode($r) : $r;
			}
			catch (Exception $e) {
				header("HTTP/1.0 500 Internal server error");
				echo json_encode(array(
					"message" => $e->getMessage(),
					"code" => $e->getCode(),
					"file" => $e->getFile(),
					"line" => $e->getLine(),
					"stackTrace" => $e->getTrace()
				));
			}
		}
		else {
			try {
				ob_start();
				if ($configFlag && method_exists('Main', 'initialize')) Main::initialize($page);
				$page->initialize();

				$arr = $_POST + $_GET;

				if (isset($arr['request']))	{
					$m = $arr['request']; unset($arr['request']);
					$r = $page->exec($m, $arr);
					if ($configFlag && method_exists('Main', 'exec')) Main::exec($r, $m, $arr, $page);

					if ($r !== null) {
						$type = gettype($r);
						if ($type == 'array' || $type == 'object')
							$r = JSON::encode($r);
						elseif ($type == 'boolean')
							$r = $r ? 'true' : 'false';
						die ($r."");
					}
				}

				$page->load();
				if ($configFlag && method_exists('Main', 'load')) Main::load($page);
				header("Content-Type: text/html; charset=utf-8");
				echo $page->loadTemplate();
			}
			catch (Exception $e) {
				if ($configFlag && method_exists('Main', 'exception')) {
					Main::exception($e, $page);
					$page->load();
					if ($configFlag && method_exists('Main', 'load')) Main::load($page);
					header("Content-Type: text/html; charset=utf-8");
					echo $page->loadTemplate();
				}
				else {
					header("HTTP/1.0 500 Internal server error");
					die ("Error: {$e->getMessage()} ");
				}
			}
		}
	}
}

?>
