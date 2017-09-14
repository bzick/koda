<?php

namespace Koda;


class Samples
{

    /**
     * No one parameter
     */
    public static function noParams()
    {

    }

    /**
     * @param int $val
     *
     * @return int
     */
    public static function intRequired($val)
    {
        return $val;
    }

    /**
     * @param int $val
     *
     * @return int
     */
    public static function intHintRequired(int $val)
    {
        return $val;
    }

    /**
     * @param int $val
     *
     * @return int
     */
    public static function intOptionals($val = -1)
    {
        return $val;
    }

    /**
     * @param int[] $val
     *
     * @return int[]
     */
    public static function intsRequired(array $val)
    {
        return $val;
    }

    /**
     * @param int[] $val
     *
     * @return int[]
     */
    public static function intsOptional(array $val = [-1])
    {
        return $val;
    }

    /**
     * @param float $val
     *
     * @return float
     */
    public static function floatRequired($val)
    {
        return $val;
    }

    /**
     * @param float $val
     *
     * @return float
     */
    public static function floatHintRequired(float $val)
    {
        return $val;
    }

    /**
     * @param float $val
     *
     * @return float
     */
    public static function floatOptionals($val = -1.1)
    {
        return $val;
    }

    /**
     * @param float[] $val
     *
     * @return float
     */
    public static function floatsRequired(array $val)
    {
        return $val;
    }

    /**
     * @param float[] $val
     *
     * @return int
     */
    public static function floatsOptional(array $val = [-1.1])
    {
        return $val;
    }

    /**
     * @param array $val
     *
     * @return int
     */
    public static function arrayRequired($val)
    {
        return $val;
    }

    /**
     * @param array $val
     *
     * @return int
     */
    public static function arrayHintRequired(array $val)
    {
        return $val;
    }

    /**
     * @param array $val
     *
     * @return int
     */
    public static function arrayOptional(array $val = [])
    {
        return $val;
    }

    /**
     * @param array[] $val
     *
     * @return float
     */
    public static function arraysRequired(array $val)
    {
        return $val;
    }

    /**
     * @param array[] $val
     *
     * @return int
     */
    public static function arraysOptional(array $val = [[], []])
    {
        return $val;
    }


    /**
     * @param string $val
     *
     * @return string
     */
    public static function stringRequired($val)
    {
        return $val;
    }

    /**
     * @param string $val
     *
     * @return string
     */
    public static function stringHintRequired(string $val)
    {
        return $val;
    }

    /**
     * @param string $val
     *
     * @return string
     */
    public static function stringOptionals($val = "one")
    {
        return $val;
    }

    /**
     * @param string[] $val
     *
     * @return string[]
     */
    public static function stringsRequired(array $val)
    {
        return $val;
    }

    /**
     * @param string[] $val
     *
     * @return string[]
     */
    public static function stringsOptional(array $val = ["one"])
    {
        return $val;
    }


    /**
     * @param bool $val
     *
     * @return bool
     */
    public static function boolRequired($val)
    {
        return $val;
    }

    /**
     * @param bool $val
     *
     * @return bool
     */
    public static function boolHintRequired(bool $val)
    {
        return $val;
    }

    /**
     * @param bool $val
     *
     * @return bool
     */
    public static function boolOptionals($val = true)
    {
        return $val;
    }

    /**
     * @param bool[] $val
     *
     * @return bool[]
     */
    public static function boolsRequired(array $val)
    {
        return $val;
    }

    /**
     * @param bool[] $val
     *
     * @return bool[]
     */
    public static function boolsOptional(array $val = [true])
    {
        return $val;
    }

    /**
     * @param self $val
     *
     * @return self
     */
    public static function selfRequired($val)
    {
        return $val;
    }

    /**
     * @param self $val
     *
     * @return self
     */
    public static function selfHintRequired(self $val)
    {
        return $val;
    }


    /**
     * @param self[] $val
     *
     * @return self[]
     */
    public static function selfsRequired(array $val)
    {
        return $val;
    }

    /**
     * @param self[] $val
     *
     * @return self[]
     */
    public static function selfsOptional(array $val = [])
    {
        return $val;
    }


    /**
     * @param \stdClass $val
     *
     * @return self
     */
    public static function objectRequired($val)
    {
        return $val;
    }

    /**
     * @param self $val
     *
     * @return self
     */
    public static function objectHintRequired(\stdClass $val)
    {
        return $val;
    }


    /**
     * @param \stdClass[] $val
     *
     * @return \stdClass[]
     */
    public static function objectsRequired(array $val)
    {
        return $val;
    }

    /**
     * @param \stdClass[] $val
     *
     * @return \stdClass[]
     */
    public static function objectsOptional(array $val = [])
    {
        return $val;
    }

    /**
     * @param int $start
     * @param array $values
     *
     * @return number
     */
    public static function sum1($start = 0, array $values = [])
    {
        return $start + array_sum($values);
    }

    /**
     * @param int $param (inject age)
     *
     * @return int
     */
    public static function doInjection($param) {
        return $param;
    }

    /**
     * @param \ArrayObject $param
     *
     * @return \ArrayObject
     */
    public static function doFactory(\ArrayObject $param) {
        return $param;
    }

    /**
     * @param int $v1 (value 1..9)
     * @param int $v2 (value >10)
     * @param int $v3 (value >=10)
     * @param int $v4 (value <10)
     * @param int $v5 (value <=10)
     *
     * @return bool
     */
    public static function ranges($v1, $v2, $v3, $v4, $v5) {
        return true;
    }
}