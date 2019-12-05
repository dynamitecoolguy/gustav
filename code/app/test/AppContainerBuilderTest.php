<?php


namespace Gustav\App;

use DI\DependencyException;
use DI\NotFoundException;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\DispatcherInterface;
use PHPUnit\Framework\TestCase;

class AppContainerBuilderTest extends TestCase
{
    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDispatcher() {
        // getContainer
        $builder = new AppContainerBuilder(
            new class implements ApplicationConfigInterface {
                public function getValue(string $category, string $key, ?string $default = null): string
                {
                    return 'dummy';
                }
            }
        );
        $container = $builder->build();

        $dispatcher = $container->get(DispatcherInterface::class);

        $this->assertInstanceOf(AppDispatcher::class, $dispatcher);
    }
}