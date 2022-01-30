<?php

namespace App\Http\Controllers;

use App;
use Storage;
use Illuminate\Support\Facades\Files;
use App\Document;
use App\Access_group;
use App\Access_group_module;
use URL;
use App\Libraries\FileHandling;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class GroupController extends Controller
{
    public function create(Request $request) {
        $group = new Access_group;
        $group->name = $request->input('name');
        $group->active = $request->input('active');

        if($group->save()) {
            $modules = $request->input('modules');
            if($modules) {
                foreach($modules as $module) {
                    $group_module = new Access_group_module;
                    $group_module->module_id = $module['module_id'];
                    $group_module->group_id = $group->id;
                    $group_module->save();
                }
            }

            return response()->json($group, 201);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Group cannot be created'
        ], 400);
    }

    public function list() {
        $group = Access_group::with('modules')->get();
        return response()->json($group);
    }

    public function detail($id) {
        $group = Access_group::with('modules')->find($id);
        if($group) {
            return response()->json($group);
        }
        return response()->json([
            'status'=> 404,
            'message'=> 'Access Group is not found',
        ], 404);
    }

    public function update(Request $request, $id) {
        $group = Access_group::find($id);
        if($group) {
            $group->name = $request->input('name');
            $group->active = $request->input('active');
            if($group->save()) {
                $modules = $request->input('modules');
                if($modules) {
                    Access_group_module::where('group_id', $group->id)->delete();
                    foreach($modules as $module) {
                        $group_module = new Access_group_module;
                        $group_module->module_id = $module['module_id'];
                        $group_module->group_id = $group->id;
                        $group_module->save();
                    }
                }
                return response()->json($group);
            }
            return response()->json([
                'status'=> 400,
                'message'=> 'Cannot process the Access Group changes'
            ], 400);
        }
        return response()->json([
            'status'=>404,
            'message'=>'Access Group not found'
        ], 404);
    }

    public function delete($id) {
        $group = Access_group::find($id);
        if($group) {
            if($group->delete()) {
                return response()->json([
                    'status' => 204,
                    'message' => 'Access Group successfully deleted'
                ], 204);
            }
        }
        return response()->json([
            'status' => 404,
            'message' => 'Access Group is not found'
        ], 404);
    }
}
