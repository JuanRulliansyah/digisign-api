<?php

namespace App\Http\Controllers;

use App;
use Storage;
use Illuminate\Support\Facades\Files;
use App\Document;
use App\Position_letter;
use URL;
use App\Libraries\FileHandling;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class LetterController extends Controller
{
    public function create(Request $request) {
        $letter = new Position_letter;
        $letter->user_id = Auth()->user()->id;
        $letter->position_id = $request->input('position_id');
        $letter->date_start = $request->input('date_start');
        $letter->date_end = $request->input('date_end');

        // Saving Position Letter File
        $file = $request->file('position_letter');
        $destination_path = '/public/uploads/letter/pdf/';
        $process_file = new FileHandling;
        $letter_path = $process_file->fileSave($file, $destination_path);
        $letter->position_letter = $letter_path;
        
        $letter->active = "T";
        
        if($letter->save()) {
            return response()->json($letter, 201);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Document cannot be created'
        ], 400);
    }

    public function list(Request $request) {
        $letter = Position_letter::where('user_id', Auth()->user()->id)->with('user')->get();
        return response()->json($letter);
    }

    public function listGeneral(Request $request) {
        $letter = Position_letter::with('user')->get();
        return response()->json($letter);
    }

    public function detail($id) {
        $letter = Position_Letter::with('user')->find($id);
        if($letter) {
            return response()->json($letter);
        }
        return response()->json([
            'status'=> 404,
            'message'=> 'Position Letter is not found',
        ], 404);
    }

    public function update(Request $request, $id) {
        $letter = Position_letter::find($id);
        $letter->status = $request->input('status');
        $letter->active = $request->input('active');
        $letter->save();

        return response()->json($letter);
    }

    public function delete($id) {
        $letter = Position_letter::find($id);
        if($letter) {
            if($letter->delete()) {
                return response()->json([
                    'status' => 204,
                    'message' => 'Position letter successfully deleted'
                ], 204);
            }
        }
        return response()->json([
            'status' => 404,
            'message' => 'Position letter is not found'
        ], 404);
    }
}
