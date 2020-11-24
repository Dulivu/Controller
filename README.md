# Controller
Controlador en PHP separa la vista de la lógica y ayuda a ordenar el código.

## Como utilizarlo
+ Una vez guardado el archivo en la carpeta que guste, solo debe importarla en el proyecto.
+ Cree un archivo '.php' con una clase que herede la funcionalidad del controlador.
+ Cree un archivo '.html' con el mismo nombre de la clase creada.
+ Ejecute el método estático heredado 'run'.

*Index.php*
```php
include('controller.php');

class Index extends Controller {
  
}

Index::run();
```

## Atributos
La clase creada se expone en la vista con la variable '$this' a través de ella puede usar todos los atributos y métodos que tenga la clase.

*Index.php*
```php
class Index extends Controller {
  public $msg = 'Hola mundo!!';
}
```
*Index.html*
```html
<body>
  <p><?=$this->msg?></p>
</body>
```

## Métodos heredados
### Initialize
Es un método que hereda de la clase 'Controller', este se ejecuta automáticamente al inicio de cada petición. Puede ser utilizado para inicializar variables o controllar el flujo inicial de la aplicación.

*Index.php*
```php
class Index extends Controller {
  public $msg = 'Hola mundo!!';
  
  public function initialize () {
    $this->con = new mysqli('localhost', 'user', 'pass', 'bd');
  }
}
```
### Load
Método que también hereda de la clase 'Controller', se ejecuta al final de la petición (después de cualquier otro método llamado). Puede ser utilizado para controllar el flujo final de la aplicación (inmediatamente después de este método se carga la vista).

*Index.php*
```php
class Index extends Controller {
  public $msg = 'Hola mundo!!';
  
  public function initialize () {
    $this->con = new mysqli('localhost', 'user', 'pass', 'bd');
  }
  
  public function load () {
    $this->list = $this->con->query('SELECT * FROM users');
  }
}
```
### Otros
Cualquier otro método creado por nosotros puede ser ejecutado desde el cliente (el método llamado será ejecutado despues de 'initialize' y antes de 'load'), en la vista se debe crear un formulario con algunas carácteristicas específicas.
+ El formulario preferentemente debe enviar la información por POST.
+ Por recomendación usar etiquetas 'button' en lugar del 'input[type="submit"]' para el envío del formulario.
+ El botón de envío debe tener un atributo [name="request"]
+ El botón de envío debe tener un atributo [value="metodo"], con el valor igual al método que desea ejecutar.

*Index.php*
```php
class Index extends Controller {

  public function initialize () {
    $this->msg = 'Hola mundo!!';
    $this->con = new mysqli('localhost', 'user', 'pass', 'bd');
  }
  
  public function validar ($uname, $upass) {
    $user = $this->con->query("SELECT * FROM users WHERE uname = '$uname'")->fetch_assoc();
    if ($user) {
      if ($upass == $user['upass']) {
        $this->msg = 'Inicio satisfactorio';
      }
      else $this->msg = 'Contraseña incorrecta';
    }
    else $this->msg = 'Usuario desconocido';
  }
  
  public function load () {
    $this->list = $this->con->query('SELECT * FROM users');
  }
}
```
*Index.html*
```html
<body>
  <form method="POST">
    <p>
      <label>Usuario:</label>
      <input name="uname" autofocus />
    </p>
 
    <p>
      <label>Contraseña:</label>
      <input name="upass" type="password"/>
    </p>
  
    <p>
      <button name="request" value="validar">Ingresar</button>
    </p>
  
    <p style="color: red; font-weight: bold;"><?= $this->msg ?></p>
  </form>
</body>
```

## Cambiar la vista
El controlador creado por defecto buscará un archivo '.html' con el mismo nombre de la clase si en algún momento queremos cargar otra plantilla lo hacemos por el atributo heredado '$_view'.
Como ejemplo creamos otro archivo llamado 'Template.html' con cualquier contenido de prueba.

*Index.php*
```php
class Index extends Controller {
  public function validate () {
    $this->_view = 'Template';
  }
}
```
Al llamar al método 'validate' el controlador no devolvera la vista por defecto 'Index.html'. Devolverá la vista 'Template.html'.

## Diseño (Layout)
El controlador permite seleccionar una plantilla que cumpla la función de 'layout', como ejemplo creamos un archivo llamado 'Layout.html' con el siguiente contenido (dentro se coloca la variable '$__page__' que será la que llame a la plantilla hija).

*Layout.html*
```html
<html>
  <head>
    <title>Hola mundo</title>
  </head>
  
  <body>
    <?= $__page__ ?>
  </body>
</html>
```

Al controlador le indicamos que usará un diseño maestro con la variable heredada '$_layout'.

*Index.php*
```php
class Index extends Controller {
  public $_layout = 'Layout';

  public function validate () {
    $this->_view = 'Template';
  }
}
```
