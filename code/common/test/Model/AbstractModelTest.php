<?php


namespace Gustav\Common\Model;

use Gustav\Common\Exception\ModelException;
use PHPUnit\Framework\TestCase;

class AbstractModelTest extends TestCase
{
    /**
     * @test
     */
    public function normalSetterGetter()
    {
        $m = new DummyAbstractModel1();

        // private property/private method
        $m->setAaa(1);
        $this->assertEquals(1, $m->getAaa());
        $this->assertEquals(1, $m->directAaa());

        // private property/public method
        $m->setBbb(2);
        $this->assertEquals(2, $m->getBbb());
        $this->assertEquals(2, $m->directBbb());

        // protected property/public method
        $m->setPpp(3);
        $this->assertEquals(3, $m->getPpp());
        $this->assertEquals(3, $m->directPpp());

        // private property/no method
        $m->setEee(4);
        $this->assertEquals(4, $m->getEee());
        $this->assertEquals(4, $m->directEee());

        // protected property/no method
        $m->setQqq(5);
        $this->assertEquals(5, $m->getQqq());
        $this->assertEquals(5, $m->directQqq());
    }

    /**
     * @test
     */
    public function inheritedSetterGetter()
    {
        $m = new DummyAbstractModel2();

        // parent public method
        $m->setBbb(1);
        $this->assertEquals(1, $m->getBbb());
        $this->assertEquals(1, $m->directBbb());

        // current public method
        $m->setDdd(2);
        $this->assertEquals(2, $m->getDdd());
        $this->assertEquals(2, $m->directDdd());

        // protected property/parent public method
        $m->setPpp(3);
        $this->assertEquals(3, $m->getPpp());
        $this->assertEquals(3, $m->directPpp());

        // protected property/parent no method
        $m->setQqq(4);
        $this->assertEquals(4, $m->getQqq());
        $this->assertEquals(4, $m->directQqq());
    }

    /**
     * @test
     * @throws ModelException
     */
    public function noSuchProperty()
    {
        $this->expectException(ModelException::class);

        $m = new DummyAbstractModel1();
        $m->setFff(1);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function parentPrivateMethod()
    {
        $this->expectException(ModelException::class);

        $m = new DummyAbstractModel2();
        $m->setAaa(1);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function parentPrivateProperty()
    {
        $this->expectException(ModelException::class);

        $m = new DummyAbstractModel2();
        $m->setEee(1);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function noSuchMethod()
    {
        $this->expectException(ModelException::class);

        $m = new DummyAbstractModel2();
        $m->foo();
    }

    /**
     * @test
     */
    public function constructor()
    {
        $m = new DummyAbstractModel1([
            'aaa' => 1,
            'bbb' => 2,
            'ppp' => 3,
            'eee' => 4,
            'qqq' => 5
        ]);

        $this->assertEquals(1, $m->getAaa());
        $this->assertEquals(1, $m->directAaa());
        $this->assertEquals(2, $m->getBbb());
        $this->assertEquals(2, $m->directBbb());
        $this->assertEquals(3, $m->getPpp());
        $this->assertEquals(3, $m->directPpp());
        $this->assertEquals(4, $m->getEee());
        $this->assertEquals(4, $m->directEee());
        $this->assertEquals(5, $m->getQqq());
        $this->assertEquals(5, $m->directQqq());

        $n = new DummyAbstractModel2([
            'bbb' => 1,
            'ddd' => 2,
            'ppp' => 3,
            'qqq' => 4
        ]);

        $this->assertEquals(1, $n->getBbb());
        $this->assertEquals(1, $n->directBbb());
        $this->assertEquals(2, $n->getDdd());
        $this->assertEquals(2, $n->directDdd());
        $this->assertEquals(3, $n->getPpp());
        $this->assertEquals(3, $n->directPpp());
        $this->assertEquals(4, $n->getQqq());
        $this->assertEquals(4, $n->directQqq());
    }

    /**
     * @test
     */
    public function constructorFailed1(): void
    {
        $this->expectException(ModelException::class);
        new DummyAbstractModel3([
            'y' => 0
        ]);
    }

    /**
     * @test
     */
    public function constructorFailed2(): void
    {
        $this->expectException(ModelException::class);
        new DummyAbstractModel3([
            'x' => 0
        ]);
    }

    /**
     * @test
     */
    public function isMethod(): void
    {
        $m = new DummyAbstractModel4([
            'b1' => 1,
            'b2' => true,
            'b3' => null,
            'b4' => 'YES'
        ]);

        $this->assertTrue($m->isB1());
        $this->assertTrue($m->isB2());
        $this->assertFalse($m->isB3());
        $this->assertTrue($m->isB4());
    }

}

class DummyAbstractModel1 extends AbstractModel
{
    private $aaa = 0;
    private $bbb = 0;
    private $ccc = 0;
    private $ddd = 0;
    private $eee = 0;
    protected $ppp = 0;
    protected $qqq = 0;

    private function getAaa() { return $this->aaa; }
    private function setAaa($a) { $this->aaa = $a; }
    public function getBbb() { return $this->bbb; }
    public function setBbb($a) { $this->bbb = $a; }
    private function getCcc() { return $this->ccc; }
    private function setCcc($a) { $this->ccc = $a; }
    public function getDdd() { return $this->ddd; }
    public function setDdd($a) { $this->ddd = $a; }
    public function getPpp() { return $this->ppp; }
    public function setPpp($a) { $this->ppp = $a; }

    public function directAaa() { return $this->aaa; }
    public function directBbb() { return $this->bbb; }
    public function directCcc() { return $this->ccc; }
    public function directDdd() { return $this->ddd; }
    public function directEee() { return $this->eee; }
    public function directPpp() { return $this->ppp; }
    public function directQqq() { return $this->qqq; }
}

class DummyAbstractModel2 extends DummyAbstractModel1
{
    private $ccc;
    private $ddd;

    private function getCcc() { return $this->ccc; }
    private function setCcc($a) { $this->ccc = $a; }
    public function getDdd() { return $this->ddd; }
    public function setDdd($a) { $this->ddd = $a; }
    public function directCcc() { return $this->ccc; }
    public function directDdd() { return $this->ddd; }
}

class DummyAbstractModel3 extends AbstractModel
{
    private function setX() { throw new \Exception(); }
}

class DummyAbstractModel4 extends AbstractModel
{
    private $b1;
    private $b2;
    private $b3;
    private $b4;
}