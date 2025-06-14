<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    //

    public function index()
    {
        $messages = Message::all();
        return response()->json($messages);
    }
}
