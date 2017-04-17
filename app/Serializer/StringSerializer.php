<?php

namespace App\Serializer;

/**
 * String serializer implementation
 * @package App\Serializer
 */
class StringSerializer implements ISerializer
{
    const BAD_TYPES = [
        '/^class@anonymous/',
        '/^Closure/',
    ];

    const CHAR_TO_TYPE = [
        'N' => 'NULL',
        'b' => 'boolean',
        'i' => 'integer',
        'd' => 'double',
        's' => 'string',
        'a' => 'array',
        'O' => 'object',
    ];

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     * @throws BadSerializedValueException
     */
    public function unserialize($data)
    {
        $typesMap = self::CHAR_TO_TYPE;
        if (is_string($data) && strlen($data) && isset($typesMap[$data[0]])) {
            $type = $typesMap[$data[0]];
            $method = "hydrate" . ucfirst(strtolower($type));

            return call_user_func([$this, $method], $data);
        }

        throw BadSerializedValueException(sprintf("Cannot unserialize data: `%s`", $data));
    }

    /**
     * @return string
     */
    private function compressNull()
    {
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
     * TODO: protected and private methods markings
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

        $reflProperties = $reflect->getProperties();
        $propsCount = count($reflProperties);

        $result = "O:{$valNameLen}:\"{$valName}\":{$propsCount}:{";

        foreach ($reflProperties as $prop) {
            $propName = $prop->getName();
            $propVal = $prop->getValue($val);
            $result .= $this->serialize($propName) . $this->serialize($propVal);
        }

        $result .= "}";

        return $result;
    }

    /**
     * @param string $val
     * @return null
     */
    private function hydrateNull($val)
    {
        return null;
    }

    /**
     * @param string $val
     * @return bool
     */
    private function hydrateBoolean($val)
    {
        return (bool) substr($val, 2, -1);
    }

    /**
     * @param string $val
     * @return int
     */
    private function hydrateInteger($val)
    {
        return (int) substr($val, 2, -1);
    }

    /**
     * @param string $val
     * @return float
     */
    private function hydrateDouble($val)
    {
        return (double) substr($val, 2, -1);
    }

    /**
     * @param $val
     * @return string
     */
    private function hydrateString($val)
    {
        $valStartPos = mb_strpos($val, ":", 2) + 2;
        return (string) mb_substr($val, $valStartPos, -2);
    }

    /**
     * @param $val
     * @return array|mixed
     */
    private function hydrateArray($val)
    {
        $resultArr = [];
        $valStartPos = mb_strpos($val, ":", 2) + 2;
        $arrDefinitionString = mb_substr($val, $valStartPos, -1);

        if ($arrDefinitionString) {
            // TODO: recursive string parser
            $resultArr = unserialize($val);
        }

        return $resultArr;
    }

    /**
     * @param $val
     * @return mixed
     * @throws BadSerializedValueException
     */
    private function hydrateObject($val)
    {
        $valStartPos = mb_strpos($val, ":", 2) + 1;
        $objString = mb_substr($val, $valStartPos, -1);
        list($objFQCN, $propCount, $objDefinitionString) = explode(":", $objString, 3);
        $objFQCN = str_replace('"', '', $objFQCN);

        if (class_exists($objFQCN) && $objDefinitionString) {
            // TODO: recursive string parser
            return unserialize($val);
        }

        throw new BadSerializedValueException(
            sprintf(
                "Couldn't recreate serialized object: `%s` from the value %s",
                $objFQCN,
                $val
            )
        );
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