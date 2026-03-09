<?php

namespace Tests\Unit;

use Cronos\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para el componente Container.
 * 
 * Estos tests son independientes y no requieren
 * inicialización del framework completo.
 */
class ContainerTest extends TestCase
{
    /**
     * Test que verifica que el contenedor puede crear y almacenar singletons.
     */
    public function testSingletonCreatesAndReturnsSameInstance(): void
    {
        $instance = Container::singleton(\stdClass::class);
        $sameInstance = Container::singleton(\stdClass::class);

        $this->assertSame($instance, $sameInstance);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * Test que verifica que el contenedor puede crear singletons con callable.
     */
    public function testSingletonWithCallable(): void
    {
        $instance = Container::singleton(
            \stdClass::class,
            fn() => new \stdClass()
        );

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * Test que verifica que el contenedor puede crear singletons con nombre de clase alternativo.
     */
    public function testSingletonWithStringClass(): void
    {
        $instance = Container::singleton('my_instance', \stdClass::class);

        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * Test que verifica que resolve retorna singleton si existe.
     */
    public function testResolveReturnsExistingSingleton(): void
    {
        $singleton = Container::singleton(\stdClass::class);
        $resolved = Container::resolve(\stdClass::class);

        $this->assertSame($singleton, $resolved);
    }

    /**
     * Test que verifica que has verifica correctamente la existencia de singletons.
     */
    public function testHasReturnsTrueForExistingSingleton(): void
    {
        Container::singleton(\stdClass::class);

        $this->assertTrue(Container::has(\stdClass::class));
        $this->assertFalse(Container::has(\DateTime::class));
    }

    /**
     * Test que verifica que diferentes singletons tienen instancias diferentes.
     * 
     * Nota: Usamos clases sin dependencias complejas para evitar
     * errores de resolución automática del Container.
     */
    public function testDifferentSingletonsHaveDifferentInstances(): void
    {
        $instance1 = Container::singleton(\stdClass::class);
        $instance2 = Container::singleton(\ArrayObject::class);

        $this->assertNotSame($instance1, $instance2);
        $this->assertInstanceOf(\stdClass::class, $instance1);
        $this->assertInstanceOf(\ArrayObject::class, $instance2);
    }

    /**
     * Test que verifica que el contenedor mantiene múltiples singletons.
     * 
     * Nota: Usamos clases simples sin dependencias complejas.
     */
    public function testContainerMaintainsMultipleSingletons(): void
    {
        $obj1 = Container::singleton(\stdClass::class);
        $obj2 = Container::singleton(\ArrayObject::class);
        $obj3 = Container::singleton(\SplObjectStorage::class);

        $this->assertSame($obj1, Container::singleton(\stdClass::class));
        $this->assertSame($obj2, Container::singleton(\ArrayObject::class));
        $this->assertSame($obj3, Container::singleton(\SplObjectStorage::class));
    }
}
