<?php

namespace App\Libraries;

use App\Models\User;
use App\Profile;
use App\Certificate as CertificateModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class Certificate {

    public function generate_certificate($user, $password) {
        $configs = array(
            "config" => null,
            "digest_alg" => "sha1",
            "x509_extensions" => "v3_ca",
            "req_extensions" => "v3_req",
            "private_key_bits" => 1024,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            "encrypt_key" => true,
            "encrypt_key_cipher" => OPENSSL_CIPHER_3DES 
        );
    
        $cn = array(
            "countryName" => env('CRT_COUNTRY'),
            "stateOrProvinceName" => env('CRT_STATE'),
            "localityName" => env('CRT_LOCALITY'),
            "organizationName" => env('CRT_ORGANIZATION'),
            "organizationalUnitName" => env('CRT_ORGANIZATION_UNIT'),
            "commonName" => env('CRT_COMMON_NAME'),
            "emailAddress" => Auth()->user()->email,
        );

        $private_key = null;
        $unsigned_cert = openssl_csr_new($cn,$private_key,$configs);

        $signed_cart = openssl_csr_sign($unsigned_cert,null,$private_key,365,$configs);

        $destination_path = 'uploads/certificate/';
        $file_name = date("Y-m-d") . '-' . Str::random(20) . '-' . Auth()->user()->username . ".p12";
        $file_fullname = $destination_path . $file_name;
        $export_p12 = openssl_pkcs12_export_to_file($signed_cart,$file_fullname,$private_key, $password);
        

        if($export_p12) {
            // Reset all certificate status
            $certificate = CertificateModel::where('user_id', Auth()->user()->id)->update(['status'=>'expired']);
            
            $hashedPassword = Hash::make($password);
            
            // Saving Certificate
            $certificate = new CertificateModel();
            $certificate->user_id = Auth()->user()->id;
            $certificate->certificate_file = '/public/' . $file_fullname;
            $certificate->status = 'usable';
            $certificate->password = $hashedPassword;
            $certificate->save();
        }
        return True;
    }

}
