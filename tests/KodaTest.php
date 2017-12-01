<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

class KodaTest extends TestCase
{

    /**
     * @param int[] $a (unsigned)
     * @param int[] $b (value 1..10)
     *
     * @return bool
     */
    public static function staticEquals(array $a, array $b)
    {
        return $a == $b;
    }

    public function _testParseDocBlock() {
        $parsed = ParseKit::parseDocBlock(<<<DOC
    /**
     * Description
     * @param int[] \$a (unsigned)
     * @param int[] \$b (value 1..10)
     *
     * @return bool value
     */
DOC
);
        $this->assertSame([
            "desc" => [
                "Description"
            ],
            "param" => [
                "int[] \$a (unsigned)",
                "int[] \$b (value 1..10)"
            ],
            "return" => [
                "bool value"
            ]
        ], $parsed);
    }

    public function providerCall()
    {
        return [
//            [KodaTest::class . "::staticEquals", [[1], [1.0]], true],
            [[KodaTest::class, "staticEquals"], [[1], [1.0]], true],
            [[$this, "staticEquals"], [[1], [1.0]], true],
            [KodaTest::class . "::staticEquals", [[1], [11]], new InvalidArgumentException()],
        ];
    }

    /**
     * @dataProvider providerCall
     *
     * @param $cb
     * @param $args
     * @param $result
     *
     * @throws \Exception
     */
    public function testCall($cb, $args, $result)
    {
        try {
            $this->assertEquals($result, $this->koda->call($cb, $args));
        } catch (\Exception $e) {
            if ($result instanceof \Exception) {
                $this->assertInstanceOf(get_class($result), $e);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @group dev
     */
    public function testObject() {
        $object = $this->koda->make(Samples::class, ["index" => 16]);
        $this->assertEquals(new Samples(), $object);
        $this->koda->setInjector(function(ArgumentInfo $info, $value) {
            return $value . "2";
        });
        $this->koda->setFactory(function(ArgumentInfo $info, $value) {
            return new \ArrayObject(["param" => $value]);
        });
        $object = $this->koda->make(SampleObject::class, ["index" => 16, "factory" => 64, "inject" => 3]);
        $this->assertEquals(new SampleObject(16, 32, new \ArrayObject(["param" => 64])), $object);
    }
}