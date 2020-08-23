<?php

/**
 * Permite separar la lógica de la vista
 *
 * @package 	Irbis-Controller
 * @author		Jorge Luis Quico C. <GeorgeL1102@gmail.com>
 * @version		2.0
 */
abstract class Controller {

	/**
	 * Arreglo para almacenar todos los valores no accesibles
	 * @var array
	 */
	private $_data = array();

	/**
	 * Nombre de la vista a usar, sino se establece
	 * se llena con el nombre de la clase
	 * @var string
	 */
	protected $_view = null;

	/**
	 * Extensión por defecto que usa para las vistas
	 * @var string
	 */
	protected $_view_ext = 'html';

	/**
	 * Directorio de la vista, si no se establece
	 * se buscará donde se encuentra la clase
	 * @var string
	 */
	protected $_view_path = '';

	/**
	 * Diseño que usará la vista, el diseño deberá 
	 * exponer una variable $__page__ que es la vista
	 * principal
	 * @var string
	 */
	protected $_layout = null;

	/**
	 * Contador de ejecuciones del controlador
	 * si es mayor a 1 significa que un controlador
	 * ya está preparando una respuesta
	 * @var int
	 */
	public static $controller = 0;

	/**
	 * Es llamado siempre al inicio de cada petición
	 */
	public function initialize () {}

	/**
	 * Es llamado siempre al final de cada petición
	 */
	public function load () {}

	/**
	 * Establece valores no accesibles
	 * los almacena en el arreglo '_data'
	 */
	public function __set ($name, $value) {
		$this->_data[$name] = $value;
	}

	/**
	 * Obtiene valores no accesibles
	 * @return mix
	 */
	public function __get ($name) {
		if (array_key_exists($name, $this->_data))
			return $this->_data[$name];
		elseif (isset($_POST[$name]))
			return $_POST[$name];
		elseif (isset($_GET[$name]))
			return $_GET[$name];
		else return null;
	}

	/**
	 * Obtiene un valor enviado por el cliente en el
	 * cuerpo del documento POST
	 * @param string $key
	 * @return mix
	 */
	public function input ($key) {
		return isset($_POST[$key]) ? $_POST[$key] : null;
	}

	/**
	 * Obtiene un valor enviado por el cliente en la 
	 * ruta de consulta GET
	 * @param string $key
	 * @return GET
	 */
	public function query ($key) {
		return isset($_GET[$key]) ? $_GET[$key] : null;
	}
	
	/**
	 * Combina un arreglo con los datos no accesibles
	 * @param array $arr
	 */
	public function merge (array $arr) {
		if (gettype($arr) == 'array')
			$this->_data = array_merge($this->_data, $arr);
	}

	/**
	 * Ejecuta un método dentro de la clase y devuelve el resultado
	 * si el método no existe lanza una expceción
	 * @param string $method_name
	 * @param array $args
	 */
	protected function exec (string $method_name, array $args) {
		if(!method_exists($this, "$method_name")) {
			throw new Exception("Method '$method_name' does not exist.");
			return false;
		}
		return call_user_func_array(array($this, "$method_name"), $args);
	}

	/**
	 * Renderiza la vista
	 * @return string
	 */
	public function render () {
		$__view = $this->_view ?: get_called_class();
		$__view .= '.'.$this->_view_ext;
		$__view = $this->_view_path.$__view;

		if (file_exists($__view)) include($__view);
		else throw new Exception("Template '{$__view}' not found");

		if ($this->_layout) {
			$__layout = $this->_layout.$this->_view_ext;
			if (file_exists($__layout)) {
				$__page__ = ob_get_clean();
				include($__layout);
			}
			else throw new Exception("Layout '{$__layout}' not found");
		}

		return ob_get_clean();
	}

	/** 
	 * Procesa una respuesta al cliente
	 * @return string
	 */
	public static function run () {
		$klass = get_called_class();
		if (Controller::$controller++) return;

		$page = new $klass;

		ob_start();
		$page->initialize();

		$arr = $_POST + $_GET;

		if (isset($arr['request']))	{
			$m = $arr['request']; 
			unset($arr['request']);
			$r = $page->exec($m, $arr);

			if ($r !== null) {
				$type = gettype($r);
				if ($type == 'array' || $type == 'object')
					$r = json_encode($r);
				elseif ($type == 'boolean')
					$r = $r ? 'true' : 'false';
				die ($r."");
			}
		}

		$page->load();
		echo $page->render();
	}
}

?>
