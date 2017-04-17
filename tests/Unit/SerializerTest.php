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
     * @param $val raw value
     */
    private function assertSerialize($val)
    {
        $this->assertSame(serialize($val), $this->serializer->serialize($val));
    }

    /**
     * @param $val raw value
     * @param bool $equality strict comparison mode
     */
    private function assertUnserialize($val, $equality = true)
    {
        $serialized = serialize($val);
        if ($equality) {
            $this->assertSame(unserialize($serialized), $this->serializer->unserialize($serialized));
        } else {
            $this->assertEquals(unserialize($serialized), $this->serializer->unserialize($serialized));
        }
    }

    /** @test */
    public function it_implements_serializer_interface()
    {
        $this->assertInstanceOf(ISerializer::class, $this->serializer);
    }

    /** @test */
    public function it_can_serialize_null()
    {
        $this->assertSerialize(null);
    }

    /** @test */
    public function it_can_serialize_boolean()
    {
        $this->assertSerialize(false);
        $this->assertSerialize(true);
    }

    /** @test */
    public function it_can_compress_integer()
    {
        $this->assertSerialize(3);
        $this->assertSerialize(0);
        $this->assertSerialize(-15);
    }

    /** @test */
    public function it_can_compress_double()
    {
        $this->assertSerialize(23.134);
        $this->assertSerialize(1.3e+5);
        // $this->assertSerialize(3.2E-3); // IEEE 754 :(
    }

    /** @test */
    public function it_can_compress_a_string()
    {
        $this->assertSerialize("Hello, world;");
    }

    /** @test */
    public function it_can_compress_an_array()
    {
        $testArr = [
            1        => "test",
            null     => true,
            "subarr" => [1, 2.1]
        ];

        $this->assertSerialize($testArr);
    }

    /** @test */
    public function it_can_compress_an_object()
    {
        $obj = $this->getTestClass([
            'hello'   => true,
            'world'   => null,
            'arrProp' => [22.3, 15]
        ]);

        $this->assertEquals(serialize($obj), $this->serializer->serialize($obj));
    }

    /**
     * @test
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

    /** @test */
    public function it_can_unserialize_null()
    {
        $this->assertUnserialize(null);
    }

    /** @test */
    public function it_can_unserialize_boolean()
    {
        $this->assertUnserialize(false);
        $this->assertUnserialize(true);
    }

    /** @test */
    public function it_can_unserialize_integer()
    {
        $this->assertUnserialize(3);
        $this->assertUnserialize(0);
        $this->assertUnserialize(-15);
    }

    /** @test */
    public function it_can_unserialize_double()
    {
        $this->assertUnserialize(23.134);
        $this->assertUnserialize(130000.0);
        $this->assertUnserialize(0.0032);
    }

    /** @test */
    public function it_can_userialize_a_string()
    {
        $this->assertUnserialize("Hello\n world!\r\n");
    }

    /** @test */
    public function it_can_unserialize_an_array()
    {
        $testArr = [
            1        => "test",
            null     => true,
            "subarr" => [1, 2.1],
            -2       => new \stdClass()
        ];

        $this->assertUnserialize($testArr, false);
    }

    /** @test */
    public function it_can_unserialize_an_object()
    {
        $obj = $this->getTestClass([
            'hello'   => true,
            'world'   => null,
            'arrProp' => [22.3, 15]
        ]);

        $this->assertUnserialize($obj, false);
    }

    protected function getTestClass(array $properties = [])
    {
        $mockObj = new \stdClass;

        foreach ($properties as $name => $prop) {
            $mockObj->{$name} = $prop;
        }

        return $mockObj;
    }
}
