<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthCrontoller extends Controller
{
    public function actionTest()
    {
        return response()->json(["hola" => "mundo"], 201);
    }
}
