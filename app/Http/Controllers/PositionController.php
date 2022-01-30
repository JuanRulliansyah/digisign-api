<?php

namespace App\Http\Controllers;

use App;
use Storage;
use Illuminate\Support\Facades\Files;
use App\Position;
use URL;
use App\Libraries\FileHandling;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class PositionController extends Controller
{
    public function create(Request $request) {

        $position = new Position;
        $position->name = $request->input('name');
        if($position->save()) {
            return response()->json($position, 201);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Position cannot be created'
        ], 400);

    }

    public function list() {
        $position = Position::all();
        return response()->json($position);
    }

    public function detail($id) {
        $position = Position::find($id);
        return response()->json($position);
    }

    public function update(Request $request, $id) {
        return response()->json([]);
    }

    public function delete($id) {
        return response()->json([]);
    }
}
