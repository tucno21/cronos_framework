<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\ConsoleCLI\ConsoleCLI;
use PHPUnit\Framework\TestCase;

class ConsoleMakeCommandsTest extends TestCase
{
    private string $migrationsPath;
    private string $seedersPath;

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2);
        $this->migrationsPath = $root . '/App/Migrations';
        $this->seedersPath = $root . '/App/Seeders';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return array lista de archivos creados por el comando (para limpiar)
     */
    private function runMakeMigration(string $name): array
    {
        $before = glob($this->migrationsPath . '/*.php') ?: [];
        (new ConsoleCLI(['cronos', 'make:migration', $name]))->run();
        $after = glob($this->migrationsPath . '/*.php') ?: [];

        return array_values(array_diff($after, $before));
    }

    private function cleanFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file) && str_contains($file, 'test_tmp_')) {
                unlink($file);
            }
        }
    }

    public function test_make_migration_crea_archivo_con_timestamp_y_tabla(): void
    {
        $created = $this->runMakeMigration('create_test_tmp_demo_table');

        $this->assertCount(1, $created);
        $file = $created[0];
        $content = file_get_contents($file);
        $this->cleanFiles([$file]);

        //formato YYYY_MM_DD_HHMMSS_name.php
        $this->assertMatchesRegularExpression(
            '/\d{4}_\d{2}_\d{2}_\d{6}_create_test_tmp_demo_table\.php$/',
            basename($file)
        );

        $this->assertIsString($content);
        $this->assertStringContainsString('extends Migration', $content);
        $this->assertStringContainsString("Schema::create('test_tmp_demo'", $content);
        $this->assertStringContainsString("Schema::dropIfExists('test_tmp_demo')", $content);
        $this->assertStringContainsString('public function up(): void', $content);
        $this->assertStringContainsString('public function down(): void', $content);
    }

    public function test_make_migration_sin_patron_create_usa_placeholder(): void
    {
        $created = $this->runMakeMigration('test_tmp_accion_custom');

        $this->assertCount(1, $created);
        $file = $created[0];
        $content = file_get_contents($file);
        $this->cleanFiles([$file]);

        $this->assertIsString($content);
        $this->assertStringContainsString('{{table}}', $content);
    }

    public function test_make_seeder_crea_clase_con_sufijo(): void
    {
        $file = $this->seedersPath . '/TestTmpDemoSeeder.php';

        if (file_exists($file)) {
            unlink($file);
        }

        (new ConsoleCLI(['cronos', 'make:seeder', 'TestTmpDemo']))->run();

        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('namespace App\Seeders;', $content);
        $this->assertStringContainsString('class TestTmpDemoSeeder extends Seeder', $content);

        unlink($file);
    }

    public function test_make_seeder_agrega_sufijo_si_falta(): void
    {
        $file = $this->seedersPath . '/TestTmpOtroSeeder.php';

        if (file_exists($file)) {
            unlink($file);
        }

        (new ConsoleCLI(['cronos', 'make:seeder', 'test_tmp_otro']))->run();

        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('class TestTmpOtroSeeder extends Seeder', $content);

        unlink($file);
    }

    public function test_make_request_crea_clase_form_request(): void
    {
        $root = dirname(__DIR__, 2);
        $file = $root . '/App/Requests/TestTmpUserRequest.php';

        if (file_exists($file)) {
            unlink($file);
        }

        (new ConsoleCLI(['cronos', 'make:request', 'TestTmpUserRequest']))->run();

        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('namespace App\Requests;', $content);
        $this->assertStringContainsString('class TestTmpUserRequest extends FormRequest', $content);
        $this->assertStringContainsString('public function authorize(): bool', $content);
        $this->assertStringContainsString('public function rules(): array', $content);

        unlink($file);
    }

    public function test_make_request_agrega_sufijo_request_si_falta(): void
    {
        $root = dirname(__DIR__, 2);
        $file = $root . '/App/Requests/TestTmpPostRequest.php';

        if (file_exists($file)) {
            unlink($file);
        }

        (new ConsoleCLI(['cronos', 'make:request', 'TestTmpPost']))->run();

        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('class TestTmpPostRequest extends FormRequest', $content);

        unlink($file);
    }

    public function test_make_resource_crea_clase_json_resource(): void
    {
        $root = dirname(__DIR__, 2);
        $file = $root . '/App/Resources/TestTmpUserResource.php';

        if (file_exists($file)) {
            unlink($file);
        }

        (new ConsoleCLI(['cronos', 'make:resource', 'TestTmpUserResource']))->run();

        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('namespace App\Resources;', $content);
        $this->assertStringContainsString('class TestTmpUserResource extends JsonResource', $content);
        $this->assertStringContainsString('public function toArray(): array', $content);

        unlink($file);
    }

    public function test_make_resource_agrega_sufijo_resource_si_falta(): void
    {
        $root = dirname(__DIR__, 2);
        $file = $root . '/App/Resources/TestTmpPostResource.php';

        if (file_exists($file)) {
            unlink($file);
        }

        (new ConsoleCLI(['cronos', 'make:resource', 'TestTmpPost']))->run();

        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertStringContainsString('class TestTmpPostResource extends JsonResource', $content);

        unlink($file);
    }
}
