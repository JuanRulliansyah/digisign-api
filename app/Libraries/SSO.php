<?php

namespace App\Libraries;

use App\Models\User;
use Illuminate\Support\Str;

class SSO {

    public function SSO_INIT($username, $password) {
        $url = "https://my.unpam.ac.id/api/login";
        $post = array(
            'username' => $username,
            'password' => $password
        );
        $post = json_encode($post);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response_json = curl_exec($ch);
        curl_close($ch);
        
        $result = False;
        $response_decode = json_decode($response_json, TRUE);
        if($response_decode['access_token']) {
            $result = True;
        }
        return $result;
    }

    public function SSO_CHECK($username, $password) {
        $SSO_INIT = $this->SSO_INIT($username, $password);
        if($SSO_INIT) {
            $user = User::where('username', $username)->first();
            if(!$user) {
                $user_type = "regular";
                if(strlen($username) > 5) {
                    $user_type = "academic_staff";
                }

                // Saving a new User from SSO Unpam
                $user = new User;
                $user->email = $username . '@gmail.com';
                $user->name = "-";
                $user->username = $username;
                $user->password = app('hash')->make($password);
                $user->type = $user_type;
                $user->access_group_id = 1;
                $user->save();
            }
            return True;
        }
        return False;
    }

}
