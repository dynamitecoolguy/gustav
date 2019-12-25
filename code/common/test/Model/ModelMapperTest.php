<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;
use PHPUnit\Framework\TestCase;

class ModelMapperTest extends TestCase
{
    /**
     * @test
     */
    public function registerModel(): void
    {
        ModelMapper::resetMap();
        ModelMapper::registerModel('P1', 'CLASS1');
        ModelMapper::registerModel('P2', 'CLASS2');
        ModelMapper::registerModel('P2', 'CLASS2'); // duplicated
        ModelMapper::registerModel('P3', 'CLASS2');

        $this->assertEquals('CLASS1', ModelMapper::findModelClass('P1'));
        $this->assertEquals('CLASS2', ModelMapper::findModelClass('P2'));
        $this->assertEquals('CLASS2', ModelMapper::findModelClass('P3'));
    }

    /**
     * @test
     */
    public function duplicatePackType(): void
    {
        $this->expectException(ModelException::class);

        ModelMapper::resetMap();
        ModelMapper::registerModel('P1', 'CLASS1');
        ModelMapper::registerModel('P1', 'CLASS2');
    }

    /**
     * @test
     */
    public function noSuchPackType(): void
    {
        $this->expectException(ModelException::class);

        ModelMapper::resetMap();
        ModelMapper::registerModel('P1', 'CLASS1');

        ModelMapper::findModelClass('P2');
    }
}