<?php

namespace App\Http\Controllers;

use App;
use Storage;
use Illuminate\Support\Facades\Files;
use App\Document;
use App\Module;
use App\Access_group;
use URL;
use App\Libraries\FileHandling;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class ModuleController extends Controller
{
    public function create(Request $request) {
        $module = new Module;

        $module->name = $request->input('name');
        $module->icon = $request->input('icon');
        $module->active = $request->input('active');
        $module->path = $request->input('path');
        $module->sort_priority = $request->input('sort_priority');

        if($module->save()) {
            return response()->json($module, 201);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Module cannot be created'
        ], 400);
    }

    public function list() {
        $module = Module::get();
        // $module = Module::with('user')->get();
        // $letter = Position_letter::leftJoin('users AS user', 'user.id', '=', 'position_letters.user_id')
        // ->where('user_id', Auth()->user()->id)->get();
        return response()->json($module);
    }

    public function listUser() {
        $group_id = Auth()->user()->access_group_id;
        $group = Access_group::with('modules')->find($group_id);

        $modules = $group->modules;
        $module_id = [];
        foreach($modules as $module) {
            array_push($module_id, $module->module_id);
        }
        $module = Module::whereIn('id', $module_id)->get();
        return response()->json($module);

    }

    public function detail($id) {
        $module = Module::find($id);
        if($module) {
            return response()->json($module);
        }
        return response()->json([
            'status'=> 404,
            'message'=> 'Module is not found',
        ], 404);
    }

    public function update(Request $request, $id) {
        $module = Module::find($id);
        if($module) {
            $module->name = $request->input('name');
            $module->icon = $request->input('icon');
            $module->active = $request->input('active');
            $module->path = $request->input('path');
            $module->sort_priority = $request->input('sort_priority');
            if($module->save()) {
                return response()->json($module);
            }
            return response()->json([
                'status'=> 400,
                'message'=> 'Cannot process the module changes'
            ], 400);
        }
        return response()->json([
            'status'=>404,
            'message'=>'Module not found'
        ], 404);
    }

    public function delete($id) {
        $module = Module::find($id);
        if($module) {
            if($module->delete()) {
                return response()->json([
                    'status' => 204,
                    'message' => 'Module successfully deleted'
                ], 204);
            }
        }
        return response()->json([
            'status' => 404,
            'message' => 'Module is not found'
        ], 404);
    }
}
