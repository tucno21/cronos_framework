<?php

declare(strict_types=1);

namespace Tests\Unit;

use Cronos\Model\ModelCollection;
use Cronos\Model\Factory;
use Cronos\Model\HasFactory;
use Cronos\Model\Model;
use PHPUnit\Framework\TestCase;

// Modelo falso para pruebas de Factory
class DummyUser extends Model
{
    use HasFactory;

    protected string $table = 'dummy_users';
    protected array $fillable = ['name', 'email', 'role', 'active'];
}

// Factory para el modelo DummyUser
class DummyUserFactory extends Factory
{
    protected string $model = DummyUser::class;

    public function definition(): array
    {
        return [
            'name' => 'Carlos Tucno',
            'email' => 'carlos@example.com',
            'role' => 'developer',
            'active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => 'administrator',
        ]);
    }
}

class FactoryTest extends TestCase
{
    public function testFactoryMakeGeneratesSingleModelInstance(): void
    {
        $factory = new DummyUserFactory();
        $user = $factory->make();

        $this->assertInstanceOf(DummyUser::class, $user);
        $this->assertSame('Carlos Tucno', $user->name);
        $this->assertSame('carlos@example.com', $user->email);
        $this->assertSame('developer', $user->role);
        $this->assertTrue($user->active);
    }

    public function testFactoryMakeAllowsAttributeOverrides(): void
    {
        $factory = new DummyUserFactory();
        $user = $factory->make([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('jane@example.com', $user->email);
        $this->assertSame('developer', $user->role);
    }

    public function testFactoryCountGeneratesCollectionOfModels(): void
    {
        $factory = new DummyUserFactory();
        $users = $factory->count(3)->make();

        $this->assertInstanceOf(ModelCollection::class, $users);
        $this->assertCount(3, $users);
        foreach ($users as $u) {
            $this->assertInstanceOf(DummyUser::class, $u);
            $this->assertSame('Carlos Tucno', $u->name);
        }
    }

    public function testFactoryStateModifiesAttributes(): void
    {
        $factory = new DummyUserFactory();
        $admin = $factory->admin()->make();

        $this->assertSame('administrator', $admin->role);
        $this->assertSame('Carlos Tucno', $admin->name);
    }

    public function testFactoryRawReturnsArray(): void
    {
        $factory = new DummyUserFactory();
        $raw = $factory->raw(['name' => 'Custom Name']);

        $this->assertIsArray($raw);
        $this->assertSame('Custom Name', $raw['name']);
        $this->assertSame('carlos@example.com', $raw['email']);
    }
}
