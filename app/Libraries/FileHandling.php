<?php

namespace App\Libraries;

use Illuminate\Support\Str;

class FileHandling {

    function fileSave($file, $destination_path) {
        $file_ext = $file->getClientOriginalExtension();
        $file_name = $file->getClientOriginalName();
        $file_full_name = date("Y-m-d") . '-' . Str::random(20) . '-' . $file_name;
        $full_destination_path = base_path() . $destination_path;
        $file->move($full_destination_path, $file_full_name);

        return $destination_path . $file_full_name;
    }

    // function uploadDirectory($path, $mode, $recursive, $force) {
    //     $asset_domain = AppConfiguration::assetDomain()->value;
    //     $url_create_directory = AppConfiguration::assetCreateDirectoryDomain()->value;
    //     $token = Auth::user()->app_token;

    //     $url = $asset_domain . "/" . $url_create_directory . "?app_token=" . $token;
    //     $post = array(
    //         'path' => $path,
    //         'mode' => $mode,
    //         'recursive' => $recursive,
    //         'force' => $force
    //     );

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    //     $response_json = curl_exec($ch);
    //     curl_close($ch);

    //     $response_decode = json_decode($response_json, TRUE);
    //     return $response_decode;
    // }

}
