<?php

namespace Cronos\ConsoleCLI;

class ConsoleCLI
{
    protected string $command1;

    protected string $command2;

    protected string $command3;

    protected string $templatesPath;

    protected string $controllerPath;

    protected string $modelPath;

    protected string $middlewarePath;

    protected string $migrationsPath;

    public function __construct($data)
    {
        $this->command1 = isset($data[1]) ? $data[1] : ''; //make

        $this->command2 = isset($data[2]) ? $data[2] : ''; //name

        $this->command3 = isset($data[3]) ? $data[3] : ''; //folder

        //rutas de templates
        $this->templatesPath = dirname(__DIR__) . '/ConsoleCLI/templates/';

        //rutas de compilacion
        $this->controllerPath = dirname(__DIR__) . '/../App/Controllers/';
        $this->modelPath = dirname(__DIR__) . '/../App/Models/';
        $this->middlewarePath = dirname(__DIR__) . '/../App/Middlewares/';

        $this->migrationsPath = dirname(__DIR__) . '/../App/Migrations/';
    }

    public function run()
    {
        if ($this->command1 == 'make:controller') {
            return $this->controller();
        }

        if ($this->command1 == 'make:model') {
            return $this->model();
        }

        if ($this->command1 == 'make:middleware') {
            return $this->middleware();
        }

        if ($this->command1 == 'make:migration') {
            return $this->makeMigration();
        }

        if ($this->command1 == 'migrate') {
            return $this->migrate();
        }

        $text =   "\n" . "Command not found" . "\n";
        $text2 =    "\n" . "make:controller name folderName(optional)" . "\n";
        $text3 =   "make:model name folderName(optional)" . "\n";
        $text4 =   "make:middleware name" . "\n";
        $text5 = "make:migration database" . "\n";
        $text6 = "migrate" . "\n";

        print("\e[0;31m$text\e[0m");
        print("\e[0;36m$text2\e[0m");
        print("\e[0;36m$text3\e[0m");
        print("\e[0;36m$text4\e[0m");
        print("\e[0;36m$text5\e[0m");
        print("\e[0;36m$text6\e[0m");
        exit;
    }

    private function makeMigration()
    {
        if ($this->command2 !== 'database') {
            $text = "\n" . "Invalid migration command. Use: make:migration database" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        $templateMigration = file_get_contents($this->templatesPath . 'migration.php');
        $migrationPath = $this->migrationsPath;
        $nameMigration = 'Database.php';

        // Check if migrations directory exists
        if (!file_exists($migrationPath)) {
            mkdir($migrationPath, 0777, true);
        }

        // Check if migration file already exists
        if (file_exists($migrationPath . $nameMigration)) {
            $text = "\n" . "The $nameMigration file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        // Create migration file
        file_put_contents($migrationPath . $nameMigration, $templateMigration);

        $text = "\n" . "Migration file created successfully." . "\n";
        print("\e[0;34m$text\e[0m");
    }

    private function migrate()
    {
        $migrationFile = $this->migrationsPath . 'Database.php';

        if (!file_exists($migrationFile)) {
            $text = "\n" . "Migration file not found. Create it first with: make:migration database" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        require_once $migrationFile;

        $migration = new \App\Migrations\Database();
        $migration->migrate();
    }

    private function controller()
    {
        $templateController = file_get_contents($this->templatesPath . 'controller.php');
        $controllerPath = $this->controllerPath;
        $nameController = ucfirst($this->command2) . '.php';

        $buscar = ['NameController', 'Controllers'];
        $cambiar = [ucfirst($this->command2), 'Controllers'];

        if ($this->command3 !== '') {
            $controllerPath = $this->controllerPath . $this->command3 . '/';
            //debe ser 'Controllers\'. $this->command3;
            $cambiar = [ucfirst($this->command2), 'Controllers\\' . $this->command3];
        }

        //preguntar si existe el archivo
        if (file_exists($controllerPath . $nameController)) {
            $text =   "\n" . "The $nameController file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        //si mi carpeta no existe la creo
        if (!file_exists($controllerPath)) {
            mkdir($controllerPath, 0777, true);
        }

        //reemplazar
        $templateController = str_replace($buscar, $cambiar, $templateController);

        //crear archivo
        file_put_contents($controllerPath . $nameController, $templateController);

        $text =   "\n" . "successfully created." . "\n";
        print("\e[0;34m$text\e[0m");
    }

    private function model()
    {
        $templateModel = file_get_contents($this->templatesPath . 'model.php');
        $modelPath = $this->modelPath;
        $nameModel = ucfirst($this->command2) . '.php';

        $buscar = ['ModelName', 'Models'];
        $cambiar = [ucfirst($this->command2), 'Models'];

        if ($this->command3 !== '') {
            $modelPath = $this->modelPath . $this->command3 . '/';
            //debe ser 'Models\'. $this->command3;
            $cambiar = [ucfirst($this->command2), 'Models\\' . $this->command3];
        }

        //preguntar si existe el archivo
        if (file_exists($modelPath . $nameModel)) {
            $text =   "\n" . "The $nameModel file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }
        //si mi carpeta no existe la creo
        if (!file_exists($modelPath)) {
            mkdir($modelPath, 0777, true);
        }

        //reemplazar
        $templateModel = str_replace($buscar, $cambiar, $templateModel);

        //crear archivo
        file_put_contents($modelPath . $nameModel, $templateModel);

        $text =   "\n" . "successfully created." . "\n";
        print("\e[0;34m$text\e[0m");
    }

    private function middleware()
    {
        $templateMiddleware = file_get_contents($this->templatesPath . 'middleware.php');
        $middlewarePath = $this->middlewarePath;
        $nameMiddleware = ucfirst($this->command2) . '.php';

        $buscar = ['MameMiddleware'];
        $cambiar = [ucfirst($this->command2)];

        //preguntar si existe el archivo
        if (file_exists($middlewarePath . $nameMiddleware)) {
            $text =   "\n" . "The $nameMiddleware file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        //reemplazar
        $templateMiddleware = str_replace($buscar, $cambiar, $templateMiddleware);

        //crear archivo
        file_put_contents($middlewarePath . $nameMiddleware, $templateMiddleware);

        $text =   "\n" . "successfully created." . "\n";
        print("\e[0;34m$text\e[0m");
    }
}
