<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

class ArgumentTest extends \PHPUnit_Framework_TestCase {

    public function providerCast() {
        $std = new \StdClass();
        $sample = new Samples();
        return array(
            // Integer
            ["intRequired", 3, 3],
            ["intRequired", 3, "3"],
            ["intRequired", 3, 3.0],
            ["intRequired", 3, 3.2],
            ["intRequired", 3, "3.0"],
            ["intRequired", 3, "3.2"],
            ["intRequired", null, "z"],
            ["intRequired", null, $std],
            ["intRequired", null, []],

            ["intHintRequired", 3, 3],
            ["intHintRequired", 3, "3"],
            ["intHintRequired", 3, 3.0],
            ["intHintRequired", 3, 3.2],
            ["intHintRequired", 3, "3.0"],
            ["intHintRequired", 3, "3.2"],
            ["intHintRequired", null, "z"],
            ["intHintRequired", null, $std],
            ["intHintRequired", null, []],

            ["intOptionals", -1],
            ["intOptionals", -1, null],
            ["intOptionals", 3, 3],

            ["intsRequired", [1, 2, 3, 4, 5], [1, 2.0, "3", "4.0", "5.3"]],
            ["intsRequired", [], []],
            ["intsRequired", [1], 1],
            ["intsRequired", [1], 1.2],

            ["intsOptional", [-1]],
            ["intsOptional", [3], [3]],
            ["intsOptional", [], []],

            // Float
            ["floatRequired", 3.0, 3],
            ["floatRequired", 3.0, "3"],
            ["floatRequired", 3.0, 3.0],
            ["floatRequired", 3.2, 3.2],
            ["floatRequired", 3.0, "3.0"],
            ["floatRequired", 3.2, "3.2"],
            ["floatRequired", null, "z"],
            ["floatRequired", null, $std],
            ["floatRequired", null, []],

            ["floatHintRequired", 3.0, 3],
            ["floatHintRequired", 3.0, "3"],
            ["floatHintRequired", 3.0, 3.0],
            ["floatHintRequired", 3.2, 3.2],
            ["floatHintRequired", 3.0, "3.0"],
            ["floatHintRequired", 3.2, "3.2"],
            ["floatHintRequired", null, "z"],
            ["floatHintRequired", null, $std],
            ["floatHintRequired", null, []],

            ["floatOptionals", -1.1],
            ["floatOptionals", -1.1, null],
            ["floatOptionals", 3.3, 3.3],

            ["floatsRequired", [1.0, 2.1, 3.0, 4.0, 5.3], [1, 2.1, "3", "4.0", "5.3"]],
            ["floatsRequired", [], []],
            ["floatsRequired", [1.0], 1],
            ["floatsRequired", [4.4], "4.4"],

            ["floatsOptional", [-1.1]],
            ["floatsOptional", [3.3], [3.3]],
            ["floatsOptional", [], []],

            // Boolean
            ["boolRequired", true, true],
            ["boolRequired", true, 1],
            ["boolRequired", true, 1.1],
            ["boolRequired", true, "1"],
            ["boolRequired", true, "z"],
            ["boolRequired", null, [1]],
            ["boolRequired", false, false],
            ["boolRequired", false, 0],
            ["boolRequired", false, 0.0],
            ["boolRequired", false, "0"],
            ["boolRequired", null, []],

            ["boolHintRequired", true, true],
            ["boolHintRequired", true, 1],
            ["boolHintRequired", true, 1.1],
            ["boolHintRequired", true, "1"],
            ["boolHintRequired", true, "z"],
            ["boolHintRequired", null, [1]],
            ["boolHintRequired", false, false],
            ["boolHintRequired", false, 0],
            ["boolHintRequired", false, 0.0],
            ["boolHintRequired", false, "0"],
            ["boolHintRequired", null, []],

            ["boolOptionals", true],
            ["boolOptionals", true, null],
            ["boolOptionals", false, false],

            ["boolsRequired", [true, true, true, false], [true, 1.1, 2, "0"]],
            ["boolsRequired", [], []],
            ["boolsRequired", [true], true],
            ["boolsRequired", [false], false],

            ["boolsOptional", [true]],
            ["boolsOptional", [true, false], [true, false]],
            ["boolsOptional", [], []],

            // Strings
            ["stringRequired", "", ""],
            ["stringRequired", "z", "z"],
            ["stringRequired", "3", 3],
            ["stringRequired", "3.2", 3.2],
            ["stringRequired", "1", true],
            ["stringRequired", null, $std],
            ["stringRequired", null, []],

            ["stringHintRequired", "", ""],
            ["stringHintRequired", "z", "z"],
            ["stringHintRequired", "3", 3],
            ["stringHintRequired", "3.2", 3.2],
            ["stringHintRequired", "1", true],
            ["stringHintRequired", null, $std],
            ["stringHintRequired", null, []],

            ["stringOptionals", "one"],
            ["stringOptionals", "one", null],
            ["stringOptionals", "two", "two"],

            ["stringsRequired", ["1", "2", "3", "z"], [true, 2.0, "3", "z"]],
            ["stringsRequired", [], []],
            ["stringsRequired", ["z"], "z"],

            ["stringsOptional", ["one"]],
            ["stringsOptional", ["two"], ["two"]],
            ["stringsOptional", null, [[]]],
            ["stringsOptional", [], []],

            // Arrays
            ["arrayRequired", [], []],
            ["arrayRequired", [1], [1]],
            ["arrayRequired", ["z"], "z"],
            ["arrayRequired", [1], 1],
            ["arrayRequired", [1.2], 1.2],
            ["arrayRequired", [$std], $std],

            ["arrayHintRequired", [], []],
            ["arrayHintRequired", [1], [1]],
            ["arrayHintRequired", ["z"], "z"],
            ["arrayHintRequired", [1], 1],
            ["arrayHintRequired", [1.2], 1.2],
            ["arrayHintRequired", [$std], $std],

            ["arraysRequired", [], []],
            ["arraysRequired", null, 1],
            ["arraysRequired", null, "z"],
            ["arraysRequired", null, new \stdClass()],

            ["arraysOptional", [[],[]]],
            ["arraysOptional", [[],[]], null],
            ["arraysOptional", [[1]], [[1]]],

            // Object
            ["objectRequired", $std, $std],
            ["objectRequired", null, "obj"],
            ["objectRequired", null, $sample],

            ["objectHintRequired", $std, $std],
            ["objectHintRequired", null, "obj"],
            ["objectHintRequired", null, $sample],

            ["objectsRequired", [$std], [$std]],
            ["objectsRequired", null, ["obj"]],
            ["objectsRequired", null, $sample],

            ["objectsOptional", []],
            ["objectsOptional", [], null],
            ["objectsOptional", null, [$sample]],
            ["objectsOptional", [$std], [$std]],

            // Self
            ["selfRequired", $sample, $sample],
            ["selfRequired", null, "obj"],
            ["selfRequired", null, $std],

            ["selfHintRequired", $sample, $sample],
            ["selfHintRequired", null, "obj"],
            ["selfHintRequired", null, $std],

            ["selfsRequired", [$sample], [$sample]],
            ["selfsRequired", null, ["obj"]],
            ["selfsRequired", null, $std],

            ["selfsOptional", []],
            ["selfsOptional", [], null],
            ["selfsOptional", null, [$std]],
            ["selfsOptional", [$sample], [$sample]],
        );
    }

    /**
     * @group        dev
     * @dataProvider providerCast
     *
     * @param string $method
     * @param mixed $result
     * @param array $arg
     *
     * @throws Error\CallableNotFoundException
     * @throws \Exception
     */
    public function testCast($method, $result, $arg = null) {
        $method = MethodInfo::scan('Koda\Samples', $method);
        if(func_num_args() > 2) {
            $args = [$arg];
        } else {
            $args = [];
        }

        try {
            $this->assertSame($result, $method->invoke($args));
        } catch(InvalidArgumentException $e) {
            if($result === null) {
                return;
            } else {
                throw $e;
            }
        }
        if(func_num_args() > 2) {
            $args = ["val" => $arg];
        } else {
            $args = [];
        }
        $this->assertEquals($result, $method->invoke($args));
    }
}