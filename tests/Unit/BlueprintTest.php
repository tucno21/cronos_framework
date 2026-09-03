<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\Database\Blueprint;
use PHPUnit\Framework\TestCase;

class BlueprintTest extends TestCase
{
    public function test_crea_sql_de_tabla_basica(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $blueprint->string('name', 100);
        $blueprint->string('email', 191)->unique();
        $blueprint->string('password', 255);
        $blueprint->timestamps();

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('CREATE TABLE `users` (', $sql);
        $this->assertStringContainsString('`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', $sql);
        $this->assertStringContainsString('`name` VARCHAR(100) NOT NULL', $sql);
        $this->assertStringContainsString('`email` VARCHAR(191) NOT NULL', $sql);
        $this->assertStringContainsString("UNIQUE KEY `users_email_unique` (`email`)", $sql);
        $this->assertStringContainsString('`password` VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('`created_at` TIMESTAMP NULL', $sql);
        $this->assertStringContainsString('`updated_at` TIMESTAMP NULL', $sql);
        $this->assertStringContainsString('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci', $sql);
    }

    public function test_enum_con_default_y_nullable(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->enum('status', ['draft', 'published', 'archived'])->default('draft');
        $blueprint->string('cover_image')->nullable();

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString(
            "`status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft'",
            $sql
        );
        $this->assertStringContainsString('`cover_image` VARCHAR(255) NULL', $sql);
    }

    public function test_integer_con_default_y_booleans(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->integer('views')->default(0);
        $blueprint->boolean('active');

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('`views` INT NOT NULL DEFAULT 0', $sql);
        $this->assertStringContainsString('`active` TINYINT(1) NOT NULL DEFAULT 0', $sql);
    }

    public function test_foreign_id_constrained_deriva_tabla_y_cascade(): void
    {
        $blueprint = new Blueprint('blogs');
        $blueprint->id();
        $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('`user_id` BIGINT UNSIGNED NOT NULL', $sql);
        $this->assertStringContainsString(
            'CONSTRAINT `blogs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE',
            $sql
        );
    }

    public function test_null_on_delete(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('`category_id` BIGINT UNSIGNED NULL', $sql);
        $this->assertStringContainsString('ON DELETE SET NULL', $sql);
    }

    public function test_foreign_manual_con_references_y_on(): void
    {
        $blueprint = new Blueprint('comments');
        $blueprint->unsignedBigInteger('post_id');
        $blueprint->foreign('post_id')->references('uuid')->on('posts')->onDelete('cascade')->onUpdate('cascade');

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString(
            'CONSTRAINT `comments_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE',
            $sql
        );
    }

    public function test_foreign_sin_on_lanza_excepcion(): void
    {
        $this->expectException(\RuntimeException::class);

        $blueprint = new Blueprint('comments');
        $blueprint->unsignedBigInteger('post_id');
        $blueprint->foreign('post_id');

        $blueprint->toCreateSql();
    }

    public function test_primary_compuesto(): void
    {
        $blueprint = new Blueprint('post_tag');
        $blueprint->foreignId('post_id');
        $blueprint->foreignId('tag_id');
        $blueprint->primary(['post_id', 'tag_id']);

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('PRIMARY KEY (`post_id`, `tag_id`)', $sql);
    }

    public function test_index_con_nombre_estandar(): void
    {
        $blueprint = new Blueprint('password_resets');
        $blueprint->string('email', 191)->index();

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('KEY `password_resets_email_index` (`email`)', $sql);
    }

    public function test_soft_deletes_y_remember_token(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->softDeletes();
        $blueprint->rememberToken();

        $sql = $blueprint->toCreateSql();

        $this->assertStringContainsString('`deleted_at` TIMESTAMP NULL', $sql);
        $this->assertStringContainsString('`remember_token` VARCHAR(100) NULL', $sql);
    }

    public function test_alter_sql_agrega_y_elimina_columnas(): void
    {
        $blueprint = new Blueprint('users', 'table');
        $blueprint->string('phone', 50)->nullable();
        $blueprint->dropColumn('legacy_field', 'old_column');

        $sql = $blueprint->toAlterSql();

        $this->assertStringStartsWith('ALTER TABLE `users` ', $sql);
        $this->assertStringContainsString('DROP COLUMN `legacy_field`', $sql);
        $this->assertStringContainsString('DROP COLUMN `old_column`', $sql);
        $this->assertStringContainsString('ADD COLUMN `phone` VARCHAR(50) NULL', $sql);
    }

    public function test_alter_sin_cambios_lanza_excepcion(): void
    {
        $this->expectException(\RuntimeException::class);

        $blueprint = new Blueprint('users', 'table');
        $blueprint->toAlterSql();
    }

    public function test_create_sin_columnas_lanza_excepcion(): void
    {
        $this->expectException(\RuntimeException::class);

        $blueprint = new Blueprint('vacia');
        $blueprint->toCreateSql();
    }

    public function test_pluralize_reglas_basicas(): void
    {
        $blueprint = new Blueprint('x');

        $this->assertSame('users', $blueprint->pluralize('user'));
        $this->assertSame('categories', $blueprint->pluralize('category'));
        $this->assertSame('boxes', $blueprint->pluralize('box'));
        $this->assertSame('buses', $blueprint->pluralize('bus'));
    }

    public function test_reutiliza_foreign_existente_para_constrained_y_cascade(): void
    {
        $blueprint = new Blueprint('blogs');
        $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();

        $sql = $blueprint->toCreateSql();

        //debe aparecer UNA sola vez la constraint (no duplicada)
        $this->assertSame(1, substr_count($sql, 'blogs_user_id_foreign'));
        $this->assertStringContainsString('ON DELETE CASCADE', $sql);
    }
}
