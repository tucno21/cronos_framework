<?php

namespace Cronos;

use Throwable;
use Dotenv\Dotenv;
use Cronos\Model\Model;
use Cronos\Http\Request;
use Cronos\Config\Config;
use Cronos\Http\Response;
use Cronos\Routing\Router;
use Cronos\Http\HttpMethod;
use Cronos\Session\Session;
use Cronos\Container\Container;
use Cronos\Errors\RouteException;
use Cronos\Session\SessionStorage;
use Cronos\Database\DatabaseDriver;
use Cronos\Errors\HttpNotFoundException;

class App
{
    public static string $root;

    public Router $router;

    public Request $request;

    public Response $response;

    public Session $session;

    public DatabaseDriver $database;

    public static function bootstrap(string $root)
    {
        //obtenemos la ruta del proyecto framework
        self::$root = $root;

        //obtenemos la instancia de la clase App y almacenamos en el contenedor
        $app = Container::singleton(self::class);

        //retornamos la instancia de la clase App y ejecutamos el metodo runServiceProvider
        return $app
            ->loadConfig()
            ->runServiceProvider("boot")
            ->setHttpStartHandlers()
            ->setSessionHandler()
            ->setUpDatabaseConnection()
            ->runServiceProvider("runtime");
    }

    protected function loadConfig(): self
    {
        //cargamos el archivo .env
        Dotenv::createImmutable(self::$root)->load();
        //cargamos los archivos de configuracion de la carpeta config
        Config::loadConfig(self::$root . "/config");

        return $this;
    }

    protected function runServiceProvider(string $type): self
    {
        //si del archivo providers.php se obtiene el valor de la clave $type
        //$type='boot' ejecutamos todo del array boot
        //$type='runtime' ejecutamos todo del array runtime
        foreach (configGet("providers.$type", []) as $provider) {
            //instanciamos la clase del provider y ejecutamos el metodo registerServices
            $provider = new $provider();
            $provider->registerServices();
        }

        return $this;
    }

    protected function setHttpStartHandlers(): self
    {
        //instanciamos la clase Router y almacenamos en la propiedad router
        $this->router = Container::singleton(Router::class);

        //instanciamos la clase Request y almacenamos en la propiedad request
        $this->request = Container::singleton(Request::class);

        //instanciamos la clase Response y almacenamos en la propiedad response
        $this->response = Container::singleton(Response::class);

        return $this;
    }

    protected function setSessionHandler(): self
    {
        //instanciamos la clase SessionStorage que se instancio desde los providers
        //SessionStorage es la interface que tiene las bases para PhpNativeSessionStorage el cual titene los 
        //metodos que usar la clase Session
        //si alquien quiere cambiar la clase Session por otra debe colocar en su contructor SessionStorage::class
        $sesionStorage = Container::resolve(SessionStorage::class);
        $this->session = Container::singleton(Session::class, fn () => new Session($sesionStorage));

        $this->uriCurrent();

        return $this;
    }


    protected function setUpDatabaseConnection(): self
    {
        $this->database = Container::resolve(DatabaseDriver::class);
        $this->database->connect(
            configGet("database.connection"),
            configGet("database.host"),
            configGet("database.port"),
            configGet("database.database"),
            configGet("database.username"),
            configGet("database.password")
        );
        Model::setDB($this->database);

        return $this;
    }

    protected function uriCurrent()
    {
        //almacenamos la ruta actual en la variable de sesion previousPath
        if ($this->request->method() == HttpMethod::GET) {
            $this->session->previousPath($this->request->uri());
        }

        $sesion = $_SESSION["_cronos_previous_path"]["old"] == $this->request->uri();
        if (!$sesion) {
            session()->deleteErrorsInputs();
        }
    }

    public function run()
    {
        try {
            $response = $this->router->resolve($this->request);
            if (!is_null($response)) {
                //se ejecuta solo si es una instancia de la clase Response
                $this->response->sendResponse($response);
            }
        } catch (HttpNotFoundException $e) {
            $response = view('error/404');
            $this->abort($response->setStatusCode(404));
        } catch (RouteException $e) {
            $response = json(["message" => $e->getMessage()]);
            $this->abort($response->setStatusCode(500));
        } catch (Throwable $e) {
            $response = json([
                "Type error" => $e::class,
                "message" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTrace(),
                "TraceAsString" => $e->getTraceAsString(),
            ]);
            $this->abort($response->setStatusCode(500));
        }
    }

    public function abort(Response $response)
    {
        $this->terminate($response);
    }

    public function terminate(Response $response)
    {
        $this->response->sendResponse($response);
        exit();
    }
}
