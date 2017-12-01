<?php
/**
 *
 */

namespace Koda;

// Leave these uses. For tests
use Koda\Error\BaseException;
use Koda\Error\TypeCastingException as TypeException;
use Koda\Error\CallException as CException,
    Koda\Error\ClassNotFound,
    Koda\Error\CreateException as ProblemException;


class ParseKitTest extends TestCase
{
    public function testParseUse()
    {
//        $uses = ParseKit::parseUse(__FILE__, (new \ReflectionClass(__CLASS__))->getStartLine());
//
//        $this->assertSame([
//            'BaseException'    => 'Koda\Error\BaseException',
//            'TypeException'    => 'Koda\Error\TypeCastingException',
//            'CException'       => 'Koda\Error\CallException',
//            'ClassNotFound'    => 'Koda\Error\ClassNotFound',
//            'ProblemException' => 'Koda\Error\CreateException',
//        ], $uses);
    }

}