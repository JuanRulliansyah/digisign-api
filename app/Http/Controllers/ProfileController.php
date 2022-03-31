<?php

namespace App\Http\Controllers;

use App;
use Storage;
use Illuminate\Support\Facades\Files;
use App\Models\User;
use App\Profile;
use App\Position_letter;
use App\Certificate;
use URL;
use App\Libraries\FileHandling;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class ProfileController extends Controller
{
    public function create(Request $request) {
        $profile = Profile::where('user_id', Auth()->user()->id)->first();
        if($profile) {
            return response()->json([
                'status' => 400,
                'message' => 'User profile has been registered before'
            ], 400);
        }

        $user = User::where('id', Auth()->user()->id)->first();
        if($user) {
            $user->email = $request->input('email');
            $user->name = $request->input('full_name');
            $user->phone_number = $request->input('phone_number');
            $user->save();
        }

        // Creating Profile
        $profile = new Profile;
        $profile->user_id = Auth()->user()->id;
        $profile->identity_number = $request->input('identity_number');
        $profile->gender = $request->input('gender');
        $profile->place_of_birth = $request->input('place_of_birth');
        $profile->date_of_birth = $request->input('date_of_birth');
        $profile->province = $request->input('province');
        $profile->city = $request->input('city');
        $profile->district = $request->input('district');
        $profile->sub_district = $request->input('sub_district');
        $profile->address = $request->input('address');
        $profile->postal_code = $request->input('postal_code');
        $profile->notes = "-";

        // Saving Identity File
        $file = $request->file('identity_file');
        $destination_path = '/public/uploads/profile/identity/';
        $process_file = new FileHandling;
        $identity_file_path = $process_file->fileSave($file, $destination_path);
        $profile->identity_file = $identity_file_path;

        // Saving Face File
        $file = $request->file('face_file');
        $destination_path = '/public/uploads/profile/face_file/';
        $process_file = new FileHandling;
        $face_file_path = $process_file->fileSave($file, $destination_path);
        $profile->face_file = $face_file_path;

        // Saving Selfie File
        $file = $request->file('selfie_file');
        $destination_path = '/public/uploads/profile/selfie_file/';
        $process_file = new FileHandling;
        $selfie_file_path = $process_file->fileSave($file, $destination_path);
        $profile->selfie_file = $selfie_file_path;

        // Saving Signature File
        $file = $request->file('signature_file');
        $destination_path = '/public/uploads/profile/signature_file/';
        $process_file = new FileHandling;
        $signature_file_path = $process_file->fileSave($file, $destination_path);
        $profile->signature_file = $signature_file_path;

        $profile->active = "T";
        
        if($profile->save()) {
            return response()->json($profile, 201);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Profile cannot be created'
        ], 400);

    }

    public function list() {
        $profile = Profile::where('user_id', Auth()->user()->id)->with('user')->get();
        return response()->json($profile, 200);
    }

    public function generalList() {
        $profile = Profile::with('user')->get();
        return response()->json($profile, 200);
    }

    public function detail($id) {
        $profile = Profile::with('user')->find($id);
        return response()->json($profile);
    }

    public function update(Request $request, $id) {
        $profile = Profile::find($id);
        if($request->input('status') == "cancel") {
            $profile->notes = $request->input('notes');
        }
        $profile->status = $request->input('status');
        $profile->active = $request->input('active');
        $profile->save();

        return response()->json($profile);
    }

    public function delete($id) {
        $profile = Profile::find($id);
        if($profile) {
            if($profile->delete()) {
                return response()->json([
                    'status' => 204,
                    'message' => 'Profile successfully deleted'
                ], 204);
            }
        }
        return response()->json([
            'status' => 404,
            'message' => 'Profile is not found'
        ], 404);
    }

    public function requirement() {
        $result = [
            'profile'=>False,
            'position_letter'=>False,
            'certificate'=>False,
        ];
        $user = User::find(Auth()->user()->id);

        // Check profile status
        $profile = Profile::where('user_id', $user->id)->where('status', 'confirmed')->first();
        if($profile) {
            $profile_data = [
                'profile'=> [
                    'stats'=>[
                        'statSubtitle'=> 'FULLFILMENT',
                        'statLink'=> '/app/kyc',
                        'statTitle'=>'KYC',
                        'statStatus'=>'Verified',
                        'statPercentColor'=>'text-emerald-500',
                        'statDescription'=>'congratulations',
                        'statIconName'=>'far fa-address-card',
                        'statIconColor'=>'bg-lightBlue-500'
                    ]
                ],
            ];
            $result['profile'] = $profile_data['profile'];
        } else {
            $profile_data = [
                'profile'=> [
                    'stats'=>[
                        'statSubtitle'=> 'FULLFILMENT',
                        'statLink'=> '/app/kyc',
                        'statTitle'=>'KYC',
                        'statStatus'=>'Not Verified',
                        'statStatusColor'=> 'text-red-500',
                        'statPercentColor'=>'text-red-500',
                        'statDescription'=>'please complete your document',
                        'statIconName'=>'far fa-address-card',
                        'statIconColor'=>'bg-lightBlue-500'
                    ]
                ],
            ];
            $result['profile'] = $profile_data['profile'];
        }

        $position_letter = Position_letter::where('user_id', $user->id)->where('status', 'usable')->first();
        if($position_letter) {
            $position_letter_data = [
                'position_letter'=> [
                    'stats'=>[
                        'statSubtitle'=> 'FULLFILMENT',
                        'statLink'=> '/app/position-letter',
                        'statTitle'=>'Position Letter',
                        'statStatus'=>'Verified',
                        'statPercentColor'=>'text-emerald-500',
                        'statDescription'=>'congratulations',
                        'statIconName'=>'far fa-id-badge',
                        'statIconColor'=>'bg-lightBlue-500'
                    ]
                ],
            ];        
            $result['position_letter'] = $position_letter_data['position_letter'];
        } else {
            $position_letter_data = [
                'position_letter'=> [
                    'stats'=>[
                        'statSubtitle'=> 'FULLFILMENT',
                        'statLink'=> '/app/position-letter',
                        'statTitle'=>'Position Letter',
                        'statStatus'=>'Not Verified',
                        'statStatusColor'=> 'text-red-500',
                        'statPercentColor'=>'text-red-500',
                        'statDescription'=>'please complete your document',
                        'statIconName'=>'far fa-id-badge',
                        'statIconColor'=>'bg-lightBlue-500'
                    ]
                ],
            ];        
            $result['position_letter'] = $position_letter_data['position_letter'];
        }

        $certificate = Certificate::where('user_id', $user->id)->where('status', 'usable')->first();
        if($certificate) {
            $certificate_data = [
                'certificate'=> [
                    'stats'=>[
                        'statSubtitle'=> 'FULLFILMENT',
                        'statLink'=> '/app/certificate',
                        'statTitle'=>'Certificate',
                        'statStatus'=>'Verified',
                        'statPercentColor'=>'text-emerald-500',
                        'statDescription'=>'congratulations',
                        'statIconName'=>'fas fa-certificate',
                        'statIconColor'=>'bg-lightBlue-500'
                    ]
                ],
            ];        
            $result['certificate'] = $certificate_data['certificate'];        
        } else {
            $certificate_data = [
                'certificate'=> [
                    'stats'=>[
                        'statSubtitle'=> 'FULLFILMENT',
                        'statLink'=> '/app/certificate',
                        'statTitle'=>'Certificate',
                        'statStatus'=>'Not Verified',
                        'statStatusColor'=> 'text-red-500',
                        'statPercentColor'=>'text-red-500',
                        'statDescription'=>'please complete your document',
                        'statIconName'=>'fas fa-certificate',
                        'statIconColor'=>'bg-lightBlue-500'
                    ]
                ],
            ];        
            $result['certificate'] = $certificate_data['certificate'];
        }

        return response()->json([
            'status' => 200,
            'data' => $result
        ], 200);   
    }
}
