<?php


namespace App\Serializer;


interface ISerializer
{
    /**
     * Serializes allowed value
     * @param mixed $value
     * @return mixed
     */
    public function serialize($value);

    /**
     * Hydrates a value from stored data
     * @param mixed $data
     * @return mixed
     */
    public function unserialize($data);
}