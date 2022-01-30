<?php

namespace App\Http\Controllers;

use App;
use Storage;
use Illuminate\Support\Facades\Files;
use App\Document;
use App\Certificate;
use App\Position_letter;
use App\Profile;
use URL;
use App\Libraries\FileHandling;
use App\Libraries\Certificate as CRT;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class CertificateController extends Controller
{
    public function createCertificate(Request $request) {
        $profile = Profile::where('user_id', Auth()->user()->id)->where('status', 'confirmed')->where('active', 'T')->first();
        if(empty($profile)) {
            return response()->json([
                'status' => 400,
                'message' => 'KYC is not verified, please complete your KYC form first'
            ], 400);
            return response()->json(['message' => 'KYC is not verified, please complete your KYC form first']);
        }

        $letter = Position_letter::where('user_id', Auth()->user()->id)->where('active', 'T')->where('status', 'usable')->first();
        if(empty($letter)) {
            return response()->json([
                'status' => 400,
                'message' => 'Position Letter is not verified, please complete your KYC form first'
            ], 400);
        }

        $certificate = new CRT;
        $generate_certificate = $certificate->generate_certificate(Auth()->user(), $request->input('password'));
        if($generate_certificate) {
            return response()->json(['status'=> 201, 'message' => 'Your certificate is sucessfully generated!'], 201);
        }
    }

    public function list() {
        $certificate = Certificate::where('user_id', Auth()->user()->id)->get();
        // $letter = Position_letter::leftJoin('users AS user', 'user.id', '=', 'position_letters.user_id')
        // ->where('user_id', Auth()->user()->id)->get();
        return response()->json($certificate, 200);
    }

    public function delete($id) {
        $certificate = Certificate::find($id);
        if($certificate) {
            if($certificate->delete()) {
                return response()->json([
                    'status' => 204,
                    'message' => 'Certificate successfully deleted'
                ], 204);
            }
        }
        return response()->json([
            'status' => 404,
            'message' => 'Certificate is not found'
        ], 404);
    }
}
