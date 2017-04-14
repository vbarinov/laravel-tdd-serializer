<?php

namespace Tests\Unit;

use App\Serializer\ISerializer;
use App\Serializer\StringSerializer;
use Tests\TestCase;

class SerializerTest extends TestCase
{
    /**
     * @var ISerializer
     */
    protected $serializer;

    public function setUp()
    {
        $this->serializer = new StringSerializer;
    }

    /**
     * @test
     */
    public function it_implements_serializer_interface()
    {
        $this->assertInstanceOf(ISerializer::class, $this->serializer);
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_serialize_null()
    {
        $this->assertEquals('N;', $this->serializer->serialize(null));
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_serialize_boolean()
    {
        $this->assertEquals('b:0;', $this->serializer->serialize(false));
        $this->assertEquals('b:1;', $this->serializer->serialize(true));
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_compress_integer()
    {
        $this->assertEquals('i:3;', $this->serializer->serialize(3));
        $this->assertEquals('i:0;', $this->serializer->serialize(0));
        $this->assertEquals('i:-15;', $this->serializer->serialize(-15));
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_compress_double()
    {
        $this->assertEquals('d:23.134;', $this->serializer->serialize(23.134));
        $this->assertEquals('d:130000;', $this->serializer->serialize(1.3e+5));
        $this->assertEquals('d:0.0032;', $this->serializer->serialize(3.2e-3));
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_compress_a_string()
    {
        $string = "Hello, world;";
        $strLen = strlen($string);
        $expected = "s:{$strLen}:\"{$string}\";";

        $this->assertEquals($expected, $this->serializer->serialize($string));
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_compress_an_array()
    {
        $testArr = [
            1        => "test",
            null     => true,
            "subarr" => [1, 2]
        ];

        $expected = 'a:3:{i:1;s:4:"test";s:0:"";b:1;s:6:"subarr";a:2:{i:0;i:1;i:1;i:2;}}';

        $this->assertEquals($expected, $this->serializer->serialize($testArr));
    }

    /**
     * @test
     * @group serialization
     */
    public function it_can_compress_an_object()
    {
        $obj = $this->getTestClass([
            'hello'   => true,
            'world'   => null,
            'arrProp' => [22.3, 15]
        ]);

        $expected =
            'O:8:"stdClass":{s:5:"hello";b:1;s:5:"world";N;s:7:"arrProp";a:2:{i:0;d:22.3;i:1;i:15;}}';

        $this->assertEquals($expected, $this->serializer->serialize($obj));
    }

    /**
     * @test
     * @group serialization
     * @expectedException \App\Serializer\BadSerializedValueException
     */
    public function it_will_throw_an_exception_if_serialized_type_is_bad()
    {
        // closure
        $this->serializer->serialize(function () {
            return "I'm closure";
        });
        // anonymous class
        $this->serializer->serialize(new class
        {
            public $omg = "i wont serialize";
        });
    }

    protected function getTestClass(array $properties = [])
    {
        $mockObj = new \stdClass;

        foreach ($properties as $name => $propDef) {
            $mockObj->{$name} = $propDef;
        }

        return $mockObj;
    }

    /** @test */
    public function it_can_unserialize_null()
    {
        $this->assertSame(null, $this->serializer->unserialize('N;'));
    }

    /** @test */
    public function it_can_unserialize_boolean()
    {
        $this->assertSame(false, $this->serializer->unserialize('b:0;'));
        $this->assertSame(true, $this->serializer->unserialize('b:1;'));
    }

    /** @test */
    public function it_can_unserialize_integer()
    {
        $this->assertSame(3, $this->serializer->unserialize('i:3;'));
        $this->assertSame(0, $this->serializer->unserialize('i:0;'));
        $this->assertSame(-15, $this->serializer->unserialize('i:-15;'));
    }

    /** @test */
    public function it_can_unserialize_double()
    {
        $this->assertSame(23.134, $this->serializer->unserialize('d:23.134;'));
        $this->assertSame(130000.0, $this->serializer->unserialize('d:130000;'));
        $this->assertSame(0.0032, $this->serializer->unserialize('d:0.0032;'));
    }
}
