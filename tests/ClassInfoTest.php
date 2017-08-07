<?php

namespace Koda;


/**
 * @property string $prop4 property 4
 * @package Koda
 */
class ClassInfoTest extends TestCase
{

    /**
     * @var int property 1
     */
    public $prop1;
    /**
     * @var int[] property 2
     */
    protected $prop2;
    /**
     * @var \Koda\TestCase property 3
     */
    private $prop3;

    /**
     * @var float property 1
     */
    public static $prop5;
    /**
     * @var float[] property 2
     */
    protected static $prop6;
    /**
     * @var array property 3
     */
    private static $prop7;

    public function func1() { }
    protected function func2() { }
    private function func3() { }
    public static function func4() { }
    protected static function func5() { }
    private static function func6() { }


    public function testScan() {
        $info = ClassInfo::scan(self::class, [
            "method" => ClassInfo::FLAG_PUBLIC | ClassInfo::FLAG_PROTECTED | ClassInfo::FLAG_PUBLIC | ClassInfo::FLAG_NON_STATIC,
            "property" => ClassInfo::FLAG_PUBLIC | ClassInfo::FLAG_PROTECTED | ClassInfo::FLAG_PUBLIC | ClassInfo::FLAG_NON_STATIC | ClassInfo::FLAG_DOCBLOCK
        ]);

        $this->assertTrue($info->hasProperty("prop1"));
        $this->assertTrue($info->hasProperty("prop2"));
        $this->assertFalse($info->hasProperty("prop3"));
        $this->assertTrue($info->hasProperty("prop4"));
        $this->assertFalse($info->hasProperty("prop5"));
        $this->assertFalse($info->hasProperty("prop6"));
        $this->assertFalse($info->hasProperty("prop7"));

        $this->assertTrue($info->hasMethod("func1"));
        $this->assertTrue($info->hasMethod("func2"));
        $this->assertFalse($info->hasMethod("func3"));
        $this->assertFalse($info->hasMethod("func4"));
        $this->assertFalse($info->hasMethod("func5"));
        $this->assertFalse($info->hasMethod("func6"));

        $this->assertSame(TestCase::class, $info->getParentClassName());
    }

    /**
     * @group dev
     */
    public function testProperties() {
        $info = ClassInfo::scan(self::class, [
            "property" => ClassInfo::FLAG_PUBLIC | ClassInfo::FLAG_NON_STATIC
        ]);

        $this->assertTrue($info->hasProperty("prop1"));

        $prop = $info->getProperty("prop1");

        $this->assertSame("int", $prop->type);
        $this->assertSame("prop1", $prop->name);
        $this->assertSame("property 1", $prop->desc);

    }
}