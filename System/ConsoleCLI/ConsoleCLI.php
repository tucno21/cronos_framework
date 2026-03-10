<?php

namespace Cronos\ConsoleCLI;

use Cronos\Database\DatabaseMigrate;

class ConsoleCLI
{
    protected string $command1;
    protected string $command2;
    protected string $command3;
    protected string $command4;

    protected string $templatesPath;
    protected string $controllerPath;
    protected string $modelPath;
    protected string $middlewarePath;
    protected string $migrationsPath;

    public function __construct($data)
    {
        $this->command1 = isset($data[1]) ? $data[1] : '';
        $this->command2 = isset($data[2]) ? $data[2] : '';
        $this->command3 = isset($data[3]) ? $data[3] : '';
        $this->command4 = isset($data[4]) ? $data[4] : '';

        $this->templatesPath = dirname(__DIR__) . '/ConsoleCLI/templates/';
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

        if ($this->command1 == 'migrate:rollback') {
            return $this->migrateRollback();
        }

        if ($this->command1 == 'migrate:reset') {
            return $this->migrateReset();
        }

        if ($this->command1 == 'migrate:refresh') {
            return $this->migrateRefresh();
        }

        if ($this->command1 == 'migrate:status') {
            return $this->migrateStatus();
        }

        $this->showHelp();
    }

    private function makeMigration()
    {
        if (empty($this->command2)) {
            $text = "\n" . "Error: Migration name is required. Use: make:migration nombre_migracion [--table=nombre]\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        $templateMigration = file_get_contents($this->templatesPath . 'migration.php');
        $migrationPath = $this->migrationsPath;

        // Check if migrations directory exists
        if (!file_exists($migrationPath)) {
            mkdir($migrationPath, 0777, true);
        }

        // Parse table name from --table option
        $tableName = null;
        if (strpos($this->command3, '--table=') === 0) {
            $tableName = substr($this->command3, 8);
        }

        // Generate timestamp prefix
        $timestamp = date('Y_m_d_His');
        $migrationName = $this->command2;
        $fileName = $timestamp . '_' . $migrationName . '.php';

        // Replace table name in template
        if ($tableName) {
            $templateMigration = str_replace('{{TABLE_NAME}}', $tableName, $templateMigration);
        } else {
            // Convert migration name to table name (snake_case)
            $tableName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $migrationName));
            $tableName = rtrim($tableName, 's'); // Remove trailing 's' if present
            $templateMigration = str_replace('{{TABLE_NAME}}', $tableName, $templateMigration);
        }

        // Check if migration file already exists
        if (file_exists($migrationPath . $fileName)) {
            $text = "\n" . "The migration file $fileName already exists\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        // Create migration file
        file_put_contents($migrationPath . $fileName, $templateMigration);

        $text = "\n" . "Migration file created successfully: {$fileName}\n";
        print("\e[0;34m$text\e[0m");
    }

    private function migrate()
    {
        $pdo = $this->getDatabaseConnection();
        $migrator = new DatabaseMigrate($pdo);
        $migrator->run();
    }

    private function migrateRollback()
    {
        $steps = 1;
        if (!empty($this->command2) && is_numeric($this->command2)) {
            $steps = (int) $this->command2;
        }

        $pdo = $this->getDatabaseConnection();
        $migrator = new DatabaseMigrate($pdo);
        $migrator->rollback($steps);
    }

    private function migrateReset()
    {
        $pdo = $this->getDatabaseConnection();
        $migrator = new DatabaseMigrate($pdo);
        $migrator->reset();
    }

    private function migrateRefresh()
    {
        $pdo = $this->getDatabaseConnection();
        $migrator = new DatabaseMigrate($pdo);
        $migrator->refresh();
    }

    private function migrateStatus()
    {
        $pdo = $this->getDatabaseConnection();
        $migrator = new DatabaseMigrate($pdo);
        $migrator->status();
    }

    private function getDatabaseConnection()
    {
        // Load environment variables
        $envFile = dirname(__DIR__, 3) . '/.env';
        $this->loadEnv($envFile);

        $connection = $this->env('DB_CONNECTION', 'mysql');
        $host = $this->env('DB_HOST', 'localhost');
        $port = $this->env('DB_PORT', 3306);
        $database = $this->env('DB_DATABASE', 'cronos');
        $username = $this->env('DB_USERNAME', 'root');
        $password = $this->env('DB_PASSWORD', '');

        try {
            $dsn = "{$connection}:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            return $pdo;
        } catch (\PDOException $e) {
            $text = "\n" . "Database connection failed: " . $e->getMessage() . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }
    }

    private function loadEnv($path)
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $value = trim($value, '"');
                $value = trim($value, "'");
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }

    private function env($key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
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
            $cambiar = [ucfirst($this->command2), 'Controllers\\' . $this->command3];
        }

        if (file_exists($controllerPath . $nameController)) {
            $text = "\n" . "The $nameController file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        if (!file_exists($controllerPath)) {
            mkdir($controllerPath, 0777, true);
        }

        $templateController = str_replace($buscar, $cambiar, $templateController);
        file_put_contents($controllerPath . $nameController, $templateController);

        $text = "\n" . "successfully created." . "\n";
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
            $cambiar = [ucfirst($this->command2), 'Models\\' . $this->command3];
        }

        if (file_exists($modelPath . $nameModel)) {
            $text = "\n" . "The $nameModel file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        if (!file_exists($modelPath)) {
            mkdir($modelPath, 0777, true);
        }

        $templateModel = str_replace($buscar, $cambiar, $templateModel);
        file_put_contents($modelPath . $nameModel, $templateModel);

        $text = "\n" . "successfully created." . "\n";
        print("\e[0;34m$text\e[0m");
    }

    private function middleware()
    {
        $templateMiddleware = file_get_contents($this->templatesPath . 'middleware.php');
        $middlewarePath = $this->middlewarePath;
        $nameMiddleware = ucfirst($this->command2) . '.php';

        $buscar = ['MameMiddleware'];
        $cambiar = [ucfirst($this->command2)];

        if (file_exists($middlewarePath . $nameMiddleware)) {
            $text = "\n" . "The $nameMiddleware file already exists" . "\n";
            print("\e[0;31m$text\e[0m");
            exit;
        }

        $templateMiddleware = str_replace($buscar, $cambiar, $templateMiddleware);
        file_put_contents($middlewarePath . $nameMiddleware, $templateMiddleware);

        $text = "\n" . "successfully created." . "\n";
        print("\e[0;34m$text\e[0m");
    }

    private function showHelp()
    {
        $text = "\n" . "Command not found" . "\n";
        $text2 = "\n" . "make:controller name folderName(optional)" . "\n";
        $text3 = "make:model name folderName(optional)" . "\n";
        $text4 = "make:middleware name" . "\n";
        $text5 = "make:migration nombre_migracion [--table=nombre]" . "\n";
        $text6 = "migrate" . "\n";
        $text7 = "migrate:rollback [steps]" . "\n";
        $text8 = "migrate:reset" . "\n";
        $text9 = "migrate:refresh" . "\n";
        $text10 = "migrate:status" . "\n";

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
        exit;
    }
}
