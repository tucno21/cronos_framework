<?php

namespace Cronos\ConsoleCLI;

use Cronos\Database\Migrator;

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

    protected string $requestPath;

    protected string $resourcePath;

    public function __construct(array $data)
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
        $this->requestPath = dirname(__DIR__) . '/../App/Requests/';
        $this->resourcePath = dirname(__DIR__) . '/../App/Resources/';

        $this->migrationsPath = dirname(__DIR__) . '/../App/Migrations/';
    }

    public function run()
    {
        if ($this->command1 == '--version' || $this->command1 == '-v' || $this->command1 == 'version') {
            print("\e[1;32mCronos Framework\e[0m version \e[1;33m" . \Cronos\App::VERSION . "\e[0m\n");
            return;
        }

        if ($this->command1 == 'make:controller') {
            return $this->controller();
        }

        if ($this->command1 == 'make:model') {
            return $this->model();
        }

        if ($this->command1 == 'make:middleware') {
            return $this->middleware();
        }

        if ($this->command1 == 'make:request') {
            return $this->request();
        }

        if ($this->command1 == 'make:resource') {
            return $this->resource();
        }

        if ($this->command1 == 'make:migration') {
            return $this->makeMigration();
        }

        if ($this->command1 == 'make:seeder') {
            return $this->makeSeeder();
        }

        if ($this->command1 == 'migrate') {
            return $this->migrate();
        }

        if ($this->command1 == 'migrate:rollback') {
            return $this->migrateRollback();
        }

        if ($this->command1 == 'migrate:status') {
            return $this->migrateStatus();
        }

        if ($this->command1 == 'migrate:fresh') {
            return $this->migrateFresh();
        }

        if ($this->command1 == 'migrate:refresh') {
            return $this->migrateRefresh();
        }

        if ($this->command1 == 'db:seed') {
            return $this->dbSeed();
        }

        $text =   "\n" . "Command not found" . "\n";
        $text2 =    "\n" . "make:controller name folderName(optional)" . "\n";
        $text3 =   "make:model name folderName(optional)" . "\n";
        $text4 =   "make:middleware name" . "\n";
        $text5 = "make:migration name (ej: create_users_table)" . "\n";
        $text6 = "make:seeder name" . "\n";
        $text7 = "migrate" . "\n";
        $text8 = "migrate:rollback (steps opcional)" . "\n";
        $text9 = "migrate:status" . "\n";
        $text10 = "migrate:fresh" . "\n";
        $text11 = "migrate:refresh" . "\n";
        $text12 = "db:seed" . "\n";

        print("\e[0;31m$text\e[0m");
        print("\e[0;36m$text2\e[0m");
        print("\e[0;36m$text3\e[0m");
        print("\e[0;36m$text4\e[0m");
        print("\e[0;36m$text5\e[0m");
        print("\e[0;36m$text6\e[0m");
        print("\e[0;36m$text7\e[0m");
        print("\e[0;36m$text8\e[0m");
        print("\e[0;36m$text9\e[0m");
        print("\e[0;36m$text10\e[0m");
        print("\e[0;36m$text11\e[0m");
        print("\e[0;36m$text12\e[0m");
        exit;
    }

    private function printError(string $text): void
    {
        print("\e[0;31m\n{$text}\n\e[0m");
    }

    private function printSuccess(string $text): void
    {
        print("\e[0;34m\n{$text}\n\e[0m");
    }

    private function makeMigration()
    {
        $name = $this->command2;

        if ($name === '' || !preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            $this->printError('Nombre de migracion invalido. Use snake_case, ej: create_users_table');
            exit;
        }

        $file = $this->migrationsPath . date('Y_m_d_His') . "_{$name}.php";

        if (glob($this->migrationsPath . '*_' . $name . '.php')) {
            $this->printError("Ya existe una migracion con el nombre {$name}");
            exit;
        }

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0777, true);
        }

        $template = file_get_contents($this->templatesPath . 'migration.stub');

        //si el nombre es create_X_table o update_X_table prefillamos el nombre de la tabla
        if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
            $template = str_replace('{{table}}', $matches[1], $template);
        }

        file_put_contents($file, $template);

        $this->printSuccess('Migracion creada: ' . basename($file));
    }

    private function makeSeeder()
    {
        $name = $this->command2;

        if ($name === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
            $this->printError('Nombre de seeder invalido. Ej: php cronos make:seeder UserSeeder');
            exit;
        }

        $class = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
        if (!str_ends_with($class, 'Seeder')) {
            $class .= 'Seeder';
        }

        $seedersPath = dirname(__DIR__) . '/../App/Seeders/';
        $file = $seedersPath . $class . '.php';

        if (!is_dir($seedersPath)) {
            mkdir($seedersPath, 0777, true);
        }

        if (file_exists($file)) {
            $this->printError("El archivo {$class}.php ya existe");
            exit;
        }

        $template = file_get_contents($this->templatesPath . 'seeder.stub');
        $template = str_replace('{{class}}', $class, $template);

        file_put_contents($file, $template);

        $this->printSuccess('Seeder creado: ' . basename($file));
    }

    private function migrate()
    {
        (new Migrator)->runPending();
    }

    private function migrateRollback()
    {
        $steps = is_numeric($this->command2) ? (int) $this->command2 : 1;
        (new Migrator)->rollback($steps);
    }

    private function migrateStatus()
    {
        $migrator = new Migrator;
        $status = $migrator->status();

        echo "\n" . str_pad('Migracion', 55) . ' | Lote';
        echo "\n" . str_repeat('-', 55) . ' | -----' . "\n";

        foreach ($status as $row) {
            $batch = $row['batch'] === null ? "\e[0;33mPendiente\e[0m" : $row['batch'];
            echo str_pad($row['migration'], 55) . ' | ' . $batch . "\n";
        }

        echo "\n";
    }

    private function migrateFresh()
    {
        (new Migrator)->fresh();
    }

    private function migrateRefresh()
    {
        (new Migrator)->refresh();
    }

    private function dbSeed()
    {
        $seederFile = dirname(__DIR__) . '/../App/Seeders/DatabaseSeeder.php';

        if (!file_exists($seederFile)) {
            $this->printError('No existe App/Seeders/DatabaseSeeder.php. Créalo con: php cronos make:seeder DatabaseSeeder');
            exit;
        }

        require_once $seederFile;

        $seederClass = 'App\Seeders\DatabaseSeeder';

        if (!class_exists($seederClass)) {
            $this->printError('La clase App\Seeders\DatabaseSeeder no existe');
            exit;
        }

        (new $seederClass)->run();

        $this->printSuccess('Seed completado.');
    }

    private function controller()
    {
        $templateController = file_get_contents($this->templatesPath . 'controller.stub');
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
        $templateModel = file_get_contents($this->templatesPath . 'model.stub');
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
        $templateMiddleware = file_get_contents($this->templatesPath . 'middleware.stub');
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

    private function request()
    {
        $templateRequest = file_get_contents($this->templatesPath . 'request.stub');
        $requestPath = $this->requestPath;
        $nameRequest = ucfirst($this->command2);

        if ($nameRequest === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $nameRequest)) {
            $this->printError('Nombre de request invalido. Ej: php cronos make:request CreateUserRequest');
            exit;
        }

        if (!str_ends_with($nameRequest, 'Request')) {
            $nameRequest .= 'Request';
        }

        $fileName = $nameRequest . '.php';
        $namespaceSuffix = '';

        if ($this->command3 !== '') {
            $folder = trim(str_replace(['/', '\\'], '/', $this->command3), '/');
            $requestPath = $this->requestPath . $folder . '/';
            $namespaceSuffix = '\\' . str_replace('/', '\\', $folder);
        }

        if (!file_exists($requestPath)) {
            mkdir($requestPath, 0777, true);
        }

        if (file_exists($requestPath . $fileName)) {
            $this->printError("El archivo {$fileName} ya existe");
            exit;
        }

        $templateRequest = str_replace(
            ['{{class}}', '{{namespace_suffix}}'],
            [$nameRequest, $namespaceSuffix],
            $templateRequest
        );

        file_put_contents($requestPath . $fileName, $templateRequest);

        $this->printSuccess("Request creado: {$fileName}");
    }

    private function resource()
    {
        $templateResource = file_get_contents($this->templatesPath . 'resource.stub');
        $resourcePath = $this->resourcePath;
        $nameResource = ucfirst($this->command2);

        if ($nameResource === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $nameResource)) {
            $this->printError('Nombre de resource invalido. Ej: php cronos make:resource UserResource');
            exit;
        }

        if (!str_ends_with($nameResource, 'Resource')) {
            $nameResource .= 'Resource';
        }

        $fileName = $nameResource . '.php';
        $namespaceSuffix = '';

        if ($this->command3 !== '') {
            $folder = trim(str_replace(['/', '\\'], '/', $this->command3), '/');
            $resourcePath = $this->resourcePath . $folder . '/';
            $namespaceSuffix = '\\' . str_replace('/', '\\', $folder);
        }

        if (!file_exists($resourcePath)) {
            mkdir($resourcePath, 0777, true);
        }

        if (file_exists($resourcePath . $fileName)) {
            $this->printError("El archivo {$fileName} ya existe");
            exit;
        }

        $templateResource = str_replace(
            ['{{class}}', '{{namespace_suffix}}'],
            [$nameResource, $namespaceSuffix],
            $templateResource
        );

        file_put_contents($resourcePath . $fileName, $templateResource);

        $this->printSuccess("Resource creado: {$fileName}");
    }
}
