<?php
/**
 * @link https://github.com/zhuravljov/yii2-queue
 * @copyright Copyright (c) 2017 Roman Zhuravlev
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace tests\serializers;

use tests\app\SimpleJob;
use yii\base\Object;
use yii\queue\serializers\Serializer;

/**
 * Class TestCase
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends \tests\TestCase
{
    /**
     * @return Serializer
     */
    abstract protected function createSerializer();

    /**
     * @dataProvider providerSerialize
     * @param mixed $expected
     */
    public function testSerialize($expected)
    {
        $serializer = $this->createSerializer();

        $serialized = $serializer->serialize($expected);
        $actual = $serializer->unserialize($serialized);

        $this->assertEquals($expected, $actual, "Payload: $serialized");
    }

    public function providerSerialize()
    {
        return [
            // Job object
            [
                new SimpleJob(['uid' => 123])
            ],
            // Any object
            [
                new TestObject([
                    'foo' => 1,
                    'bar' => [
                        new TestObject(['foo' => 1]),
                    ]
                ]),
            ],
            // Array of mixed data
            [
                [
                    'a' => 'b',
                    'c' => [
                        222,
                        new TestObject(),
                    ],
                    'd' => [
                        new TestObject(),
                    ],
                ],
            ],
            // Scalar
            [
                'string value'
            ],
        ];
    }
}

class TestObject extends Object
{
    public $foo;
    public $bar;
}