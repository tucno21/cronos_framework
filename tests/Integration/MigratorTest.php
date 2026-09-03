<?php

declare(strict_types=1);

namespace Tests\Integration;

use Cronos\Database\Migrator;
use Cronos\Database\Schema;
use PDO;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    private const TEST_DB = 'cronos_migrate_test';

    private ?PDO $serverPdo = null;
    private ?PDO $dbPdo = null;
    private string $migrationsPath;
    private ?Migrator $migrator = null;

    private const MIGRATION_CREATE = <<<'PHP'
        <?php

        use Cronos\Database\Migration;
        use Cronos\Database\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('demotable', function ($table) {
                    $table->id();
                    $table->string('name');
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('demotable');
            }
        };
        PHP;

    private const MIGRATION_ADD_COLUMN = <<<'PHP'
        <?php

        use Cronos\Database\Migration;
        use Cronos\Database\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::table('demotable', function ($table) {
                    $table->string('email', 100)->nullable();
                });
            }

            public function down(): void
            {
                Schema::table('demotable', function ($table) {
                    $table->dropColumn('email');
                });
            }
        };
        PHP;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->serverPdo = new PDO(
                'mysql:host=127.0.0.1;port=3306',
                'root',
                'root',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]
            );
        } catch (\PDOException $e) {
            $this->markTestSkipped('MySQL no disponible: ' . $e->getMessage());
        }

        $this->serverPdo->exec('DROP DATABASE IF EXISTS `' . self::TEST_DB . '`');
        $this->serverPdo->exec('CREATE DATABASE `' . self::TEST_DB . '`');

        $this->dbPdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;dbname=' . self::TEST_DB,
            'root',
            'root',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        Schema::setPDO($this->dbPdo);

        $this->migrationsPath = sys_get_temp_dir() . '/cronos_migrator_test_' . uniqid();
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0777, true);
        }

        file_put_contents($this->migrationsPath . '/2026_01_01_000001_create_demotable_table.php', self::MIGRATION_CREATE);

        $this->migrator = new Migrator($this->dbPdo, $this->migrationsPath);
    }

    protected function tearDown(): void
    {
        $this->serverPdo?->exec('DROP DATABASE IF EXISTS `' . self::TEST_DB . '`');
        $this->deleteDirectory($this->migrationsPath);
        $this->serverPdo = null;
        $this->dbPdo = null;
        $this->migrator = null;

        parent::tearDown();
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    public function test_run_pending_ejecuta_migraciones_y_registra_lote(): void
    {
        $count = $this->migrator->runPending();

        $this->assertSame(1, $count);
        $this->assertTrue($this->tableExists('demotable'));
        $this->assertTrue($this->columnExists('demotable', 'name'));

        $ran = $this->migrator->ran();
        $this->assertSame(['2026_01_01_000001_create_demotable_table'], $ran);
        $this->assertSame(1, $this->migrator->getLastBatch());
    }

    public function test_run_pending_no_repite_migraciones_ya_ejecutadas(): void
    {
        $this->migrator->runPending();
        $count = $this->migrator->runPending();

        $this->assertSame(0, $count);
    }

    public function test_rollback_revierte_el_ultimo_lote(): void
    {
        $this->migrator->runPending();
        $count = $this->migrator->rollback();

        $this->assertSame(1, $count);
        $this->assertFalse($this->tableExists('demotable'));
        $this->assertSame([], $this->migrator->ran());
        $this->assertNull($this->migrator->getLastBatch());
    }

    public function test_lotes_sucesivos_y_rollback_del_ultimo(): void
    {
        $this->migrator->runPending();

        file_put_contents($this->migrationsPath . '/2026_01_01_000002_add_email_to_demotable_table.php', self::MIGRATION_ADD_COLUMN);

        $count = $this->migrator->runPending();
        $this->assertSame(1, $count);
        $this->assertTrue($this->columnExists('demotable', 'email'));
        $this->assertSame(2, $this->migrator->getLastBatch());

        //rollback solo del lote 2
        $rolled = $this->migrator->rollback();
        $this->assertSame(1, $rolled);
        $this->assertFalse($this->columnExists('demotable', 'email'));
        $this->assertTrue($this->tableExists('demotable'));
        $this->assertSame(1, $this->migrator->getLastBatch());
    }

    public function test_rollback_con_steps_revierte_varios_lotes(): void
    {
        $this->migrator->runPending();
        file_put_contents($this->migrationsPath . '/2026_01_01_000002_add_email_to_demotable_table.php', self::MIGRATION_ADD_COLUMN);
        $this->migrator->runPending();

        $rolled = $this->migrator->rollback(5);

        $this->assertSame(2, $rolled);
        $this->assertFalse($this->tableExists('demotable'));
        $this->assertSame([], $this->migrator->ran());
    }

    public function test_status_devuelve_estado_correcto(): void
    {
        file_put_contents($this->migrationsPath . '/2026_01_01_000002_add_email_to_demotable_table.php', self::MIGRATION_ADD_COLUMN);

        $this->migrator->runPending();

        $status = $this->migrator->status();

        $this->assertCount(2, $status);
        $this->assertSame(1, $status[0]['batch']);
        $this->assertSame(1, $status[1]['batch']);

        //nueva migracion sin ejecutar aparece pendiente (batch null)
        file_put_contents($this->migrationsPath . '/2026_01_01_000003_otra_migracion.php', self::MIGRATION_CREATE);
        $status = $this->migrator->status();

        $this->assertCount(3, $status);
        $this->assertNull($status[2]['batch']);
    }

    public function test_fresh_elimina_todas_las_tablas_y_vuelve_a_migrar(): void
    {
        $this->migrator->runPending();

        //tabla extra fuera del sistema de migraciones
        $this->dbPdo->exec('CREATE TABLE `extra` (`id` INT PRIMARY KEY)');
        $this->assertTrue($this->tableExists('extra'));

        $count = $this->migrator->fresh();

        $this->assertSame(1, $count);
        $this->assertFalse($this->tableExists('extra'));
        $this->assertTrue($this->tableExists('demotable'));
        $this->assertTrue($this->tableExists('migrations'));
        $this->assertSame(1, $this->migrator->getLastBatch());
    }

    public function test_refresh_revierte_y_vuelve_a_ejecutar(): void
    {
        file_put_contents($this->migrationsPath . '/2026_01_01_000002_add_email_to_demotable_table.php', self::MIGRATION_ADD_COLUMN);

        $this->migrator->runPending();
        $count = $this->migrator->refresh();

        $this->assertSame(2, $count);
        $this->assertTrue($this->columnExists('demotable', 'email'));
        $this->assertCount(2, $this->migrator->ran());
        $this->assertSame(1, $this->migrator->getLastBatch());
    }

    public function test_get_migration_files_ordenados_por_nombre(): void
    {
        file_put_contents($this->migrationsPath . '/2026_01_01_000002_add_email_to_demotable_table.php', self::MIGRATION_ADD_COLUMN);

        $files = Migrator::getMigrationFiles($this->migrationsPath);
        $names = array_map([Migrator::class, 'getMigrationName'], $files);

        $this->assertSame([
            '2026_01_01_000001_create_demotable_table',
            '2026_01_01_000002_add_email_to_demotable_table',
        ], $names);
    }

    public function test_resolve_rechaza_archivo_que_no_retorna_migracion(): void
    {
        $this->expectException(\RuntimeException::class);

        file_put_contents($this->migrationsPath . '/2026_01_01_000009_invalido.php', '<?php return "no-soy-migracion";');

        $this->migrator->resolve($this->migrationsPath . '/2026_01_01_000009_invalido.php');
    }
}
