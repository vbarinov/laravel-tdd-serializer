<?php

namespace App\Serializer;


class StringSerializer implements ISerializer
{
    const BAD_TYPES = [
        '/^class@anonymous/',
        '/^Closure/'
    ];

    /**
     * @inheritdoc
     * @throws BadSerializedValueException
     */
    public function serialize($value)
    {
        $type = gettype($value);

        switch ($type) {
            case 'NULL':
                return $this->compressNull();
                break;
            case 'boolean':
                return $this->compressBool($value);
                break;
            case 'integer':
                return $this->compressInt($value);
                break;
            case 'double':
                return $this->compressDouble($value);
                break;
            case 'string':
                return $this->compressString($value);
                break;
            case 'array':
                return $this->compressArray($value);
                break;
            case 'object':
                return $this->compressObject($value);
                break;
            default:
                throw new BadSerializedValueException(sprintf("Unknown type: %s", $type));
        }
    }

    /**
     * @inheritdoc
     */
    public function unserialize($data)
    {
        // TODO: Implement unserialize() method.
    }

    /**
     * @return string
     */
    private function compressNull() {
        return 'N;';
    }

    /**
     * @param boolean $val
     * @return string
     */
    private function compressBool($val)
    {
        return $val ? 'b:1;' : 'b:0;';
    }

    /**
     * @param int $val
     * @return string
     */
    private function compressInt($val)
    {
        return "i:{$val};";
    }

    /**
     * @param double $val
     * @return string
     */
    private function compressDouble($val)
    {
        return "d:{$val};";
    }

    /**
     * @param string $val
     * @return string
     */
    private function compressString($val)
    {
        $len = mb_strlen($val);
        return "s:{$len}:\"{$val}\";";
    }

    /**
     * @param array $val
     * @return string
     */
    private function compressArray($val)
    {
        $len = count($val);
        $result = "a:{$len}:{";

        foreach ($val as $k => $v) {
            $result .= $this->serialize($k) . $this->serialize($v);
        }

        $result .= "}";

        return $result;
    }

    /**
     * @param object $val
     * @return string
     * @throws BadSerializedValueException
     */
    private function compressObject($val)
    {
        $reflect = new \ReflectionObject($val);
        $valName = $reflect->getName();
        $valNameLen = strlen($valName);

        if ($this->isBadType($valName)) {
            throw new BadSerializedValueException(sprintf("Bad type: %s", $valName));
        }

        $result = "O:{$valNameLen}:\"{$valName}\":{";

        foreach ($reflect->getProperties() as $prop) {
            $propName = $prop->getName();
            $propVal = $prop->getValue($val);
            $result .= $this->serialize($propName) . $this->serialize($propVal);
        }

        $result .= "}";

        return $result;
    }

    /**
     * @param $objectName
     * @return bool
     */
    private function isBadType($objectName)
    {
        foreach (self::BAD_TYPES as $bad) {
            if (preg_match($bad, $objectName)) {
                return true;
            }
        }

        return false;
    }
}