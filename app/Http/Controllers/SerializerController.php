<?php

namespace App\Http\Controllers;

use App\Serializer\ISerializer;
use Illuminate\Http\Request;

class SerializerController extends Controller
{
    public function view(ISerializer $serializer)
    {
        dd($serializer);
    }
}
