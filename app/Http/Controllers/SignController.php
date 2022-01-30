<?php
namespace App\Http\Controllers;

use Storage;
use TCPDF;
use Illuminate\Http\Request;
use App\Document;
use App\Certificate;
use App\Libraries\ManageCert;
use App\Libraries\SignaturePdf;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Hash;
// use Barryvdh\DomPDF\Facade as PDF;
// use LSNepomuceno\LaravelA1PdfSign\{ManageCert, SignaturePdf};

class SignController extends Controller
{
    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    // public function sign(Request $request) {
        // $image = QrCode::format('png')
        // ->merge(base_path().'/public/assets/signatures/unpam.png', 0.1, true)
        // ->size(200)->errorCorrection('H')
        // ->generate('A simple example of QR code!');

    //     $qr_filename = bin2hex(openssl_random_pseudo_bytes(10));
    //     $output_file = base_path() . '/public/uploads/tmp/qr/'.$qr_filename.'.eps';
    //     QrCode::format('eps')->generate('Make me into a QrCode!', $output_file);
    //     // Storage::disk('local')->put($output_file, $image);
    // }

    public function sign(Request $request)
    {
        $certificate = Certificate::where('user_id', Auth()->user()->id)->where('status', 'usable')->first();
        if(!Hash::check($request->input('password'), $certificate->password)) {
            return response()->json([
                "status"=> 401,
                "message"=> $request->input('document_id')
            ], 401);
        }

        if($certificate) { 
            $document = Document::find($request->input('document_id'));
            // if($document->status == "signed") {
            //     return response()->json([
            //         "status"=> 400,
            //         "message"=> "Document already signed"
            //     ], 400);
            // }

            $info = array(
                'Name' => 'Universitas Pamulang',
                'Location' => 'Tangerang Selatan',
                'Reason' => 'To Verify',
                'ContactInfo' => 'https://unpam.ac.id/',
            );

            // Setup Certificates
            $p12 = file_get_contents(base_path() . $certificate->certificate_file);
            $cert = openssl_pkcs12_read($p12, $crt, $request->input('password'));

            $document_full_identity = $document->document;
            $pdftext = file_get_contents(base_path() . $document->document);
            $num = preg_match_all("/\/Page\W/", $pdftext, $dummy);

            $qr_filename = bin2hex(openssl_random_pseudo_bytes(10));
            $qr_file = base_path() . '/public/uploads/tmp/qr/'.$qr_filename.'.png';

            QrCode::format('png')
            ->merge(base_path().'/public/assets/signatures/unpam.png', 0.50, true)
            ->size(200)->errorCorrection('H')
            ->generate(env('WEB_URL').'verify?identity='.$document->validate_id, $qr_file);

            $pdf = new Fpdi();  
            $pdf->setSourceFile(base_path() . $document->document);  
            for($i=0; $i<$num; $i++) {
                $tplIdx = $pdf->importPage($i+1);
                $pdf->AddPage();
                $pdf->useTemplate($tplIdx, null, null, null);
            }
            $pdf->AddPage();
            $pdf->Image(base_path().'/public/assets/signatures/signed.png',72,90,70);
            $pdf->SetFont('Arial');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(67, 200);
            $pdf->Write(0, 'Dokumen ini telah ditandatangani melalui');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(60, 210);
            $pdf->Write(0, 'Aplikasi Digital Signature Universitas Pamulang');
            $pdf->Image($qr_file, 82, 130, -100);
            unlink(base_path().$document_full_identity);
            $pdf->Output(base_path().$document_full_identity, "F");
            unlink($qr_file);
            try {
                $cert = new ManageCert;
                $cert->fromPfx(base_path().$certificate->certificate_file, $request->input('password'));
            } catch (\Throwable $th) {
                return response()->json([
                    "status"=>404,
                    "message"=> "Document is not found"
                ], 404);
            }

            try {
                $pdf = new SignaturePdf(base_path() . $document->document, $cert, SignaturePdf::MODE_RESOURCE);
                $pdf->signature(base_path() . $document->document);
            } catch (\Throwable $th) {
                return response()->json([
                    "status"=> 422,
                    "message"=> $request->input('document_id')
                ], 422);
            }
                     
            $document->status = "signed";
            $document->save();

            return response()->json([
                "status"=>200,
                "message"=>"Document has been successfully signed."
            ], 200);
        }

        return response()->json([
            "status"=> 400,
            "message"=> "Certificate is not ready"
        ], 400);
    }

    public function signGet($id, $password)
    {
        $certificate = Certificate::where('user_id', 5)->where('status', 'usable')->first();
        if($certificate) { 
            $info = array(
                'Name' => 'Universitas Pamulang',
                'Location' => 'Tangerang Selatan',
                'Reason' => 'To Verify',
                'ContactInfo' => 'https://unpam.ac.id/',
            );

            // Setup Certificates
            $p12 = file_get_contents(base_path() . $certificate->certificate_file);
            $cert = openssl_pkcs12_read($p12, $crt, $password);

            $document = Document::find($id);
            try {
                $cert = new ManageCert;
                $cert->fromPfx(base_path().$certificate->certificate_file, '2AjAozs4WLxA');
            } catch (\Throwable $th) {
                // TODO necessary
            }
            // $filename = substr($document->document, strrpos($document->document, '/') + 1);

            try {
                $pdf = new SignaturePdf(base_path() . $document->document, $cert, SignaturePdf::MODE_RESOURCE);
                return $pdf->signature(base_path() . $document->document); // The file will be downloaded
            } catch (\Throwable $th) {
                return response()->json([
                    "status"=> 422,
                    "message"=> "Signature cannot be proccessed"
                ], 422);
            }
            return response()->json([
                "status"=>200,
                "message"=>"Document has been successfully signed."
            ], 200);
        }

        return response()->json([
            "status"=> 400,
            "message"=> "Certificate is not ready"
        ], 400);
    }

    public function createPDF(Request $request)
    {
        // set certificate file
        $certificate = 'file://'.base_path().'/public/tcpdf.crt';

        // set additional information in the signature
        $info = array(
            'Name' => 'TCPDF',
            'Location' => 'Office',
            'Reason' => 'Testing TCPDF',
            'ContactInfo' => 'http://www.tcpdf.org',
        );

        // Setup Certificates
        $p12 = file_get_contents(base_path() . '/public/test.p12');
        $read = openssl_pkcs12_read($p12, $crt, "123456");

        $tcpdf = new TCPDF();
        // set document signature
        // $tcpdf->setSignature($certificate, $certificate, 'tcpdfdemo', '', 2, $info);
        $tcpdf->setSignature($crt['cert'], $crt['pkey'], 'tcpdfdemo', '', 2, $info);
        
        $tcpdf->SetFont('helvetica', '', 12);
        $tcpdf->SetTitle('Hello World');
        $tcpdf->AddPage();

        // print a line of text
        $text = view('tcpdf');

        // add view content
        $tcpdf->writeHTML($text, true, 0, true, 0);

        // add image for signature
        $tcpdf->Image('tcpdf.png', 180, 60, 15, 15, 'PNG');
        
        // define active area for signature appearance
        $tcpdf->setSignatureAppearance(180, 60, 15, 15);
        
        // save pdf file
        // $tcpdf->Output(base_path('hello_world.pdf'), 'F');
        $tcpdf->Output('hello_world.pdf', 'D');

        dd('pdf created');
    }

    public function createCertificate() {

        $Configs = array(
        "config" => null,
        "digest_alg" => "sha1",
        "x509_extensions" => "v3_ca",
        "req_extensions" => "v3_req",
        "private_key_bits" => 1024,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "encrypt_key" => true,
        "encrypt_key_cipher" => OPENSSL_CIPHER_3DES 
        );

        $Info = array(
        "countryName" => "VN",
        "stateOrProvinceName" => "Hanoi",
        "localityName" => "Long Bien",
        "organizationName" => "Test Company",
        "organizationalUnitName" => "Test Department",
        "commonName" => "Tester",
        "emailAddress" => "test@gmail.com"
        );

        $Private_Key = null;
        $Unsigned_Cert = openssl_csr_new($Info,$Private_Key,$Configs);

        $Signed_Cert = openssl_csr_sign($Unsigned_Cert,null,$Private_Key,365,$Configs);

        openssl_pkcs12_export_to_file($Signed_Cert,"test.p12",$Private_Key,"123456");
        $p12 = 'file://'.base_path().'/public/test.p12';

        
        // $p12 = file_get_contents(base_path() . '/public/test.p12');

        // $read = openssl_pkcs12_read($p12, $crt, "123456");
        // dd($crt);
    }

    // public function createCertificate() {
    //     $dn = [
    //         'commonName' => 'example.com',
    //         'organizationName' => 'ACME Inc',
    //         'organizationalUnitName' => 'IT',
    //         'localityName' => 'Seattle',
    //         'stateOrProvinceName' => 'Washington',
    //         'countryName' => 'US',
    //         'emailAddress' => 'foo@example.com',
    //       ];

    //     // Generates a new private key
    //     $privateKey = openssl_pkey_new([
    //         'private_key_type' => OPENSSL_KEYTYPE_RSA,
    //         'private_key_bits' => 4096
    //     ]);

    //     // $csrResource = openssl_csr_new($dn, $privateKey, [
    //     //     'digest_alg' => 'sha256',
    //     //     'config' => '/tmp/openssl.cnf',
    //     //   ]);

    //     $privateKeyPass = 'dummyPassword';

    //     $csrResource = openssl_csr_new($dn, $privateKey);
          
    //     openssl_csr_export($csrResource, $csrString);
    //     openssl_pkey_export($privateKey, $privateKeyString);
        
    //     file_put_contents('/'. base_path() . '/private.key', $privateKeyString);
    //     file_put_contents('/'. base_path() . '/public.csr', $csrString);

    //     $numberOfDays = 108;
    //     $sscert = openssl_csr_sign($csrResource, 
    //         null, $privateKey, $numberOfDays);

    //     openssl_x509_export($sscert, $publicKey);
    //     $test = openssl_pkey_export($privateKey, 
    //         $privateKey, $privateKeyPass);

    //     dd($test);
    // }

    // public function createCertificate() {
    //     $dn = array(
    //         "countryName" => 'xx',
    //         "stateOrProvinceName" => 'uttar prradesh',
    //         "localityName" => 'varanasi',
    //         "organizationName" => 'geeksforgeeks',
    //         "organizationalUnitName" => 'geeks team',
    //         "commonName" => 'people',
    //         "emailAddress" => 'user@geeks.com'
    //     );
        
    //     $privateKeyPass = 'dummyPassword';
    //     $numberOfDays = 108;
        
    //     $privateKey = openssl_pkey_new();
    //     $csr = openssl_csr_new($dn, $privateKey);

    //     // Create a csr file, change null
    //     // to a filename to save
        // $sscert = openssl_csr_sign($csr, 
        //     null, $privateKey, $numberOfDays);

    //     // On success $publicKey will 
    //     // hold the PEM content 
    //     openssl_x509_export($sscert, $publicKey);
        
    //     // Export the privateKey as a PEM content
        // openssl_pkey_export($privateKey, 
        //     $privateKey, $privateKeyPass);
        
    //     // Parses the $privateKey and used 
    //     // by openssl_pkcs12_export_to_file.
    //     $key = openssl_pkey_get_private(
    //         $privateKey, $privateKeyPass);
        
    //     $certificateOutput = null;
        
    //     // Save the pfx file to $certificateOutput
    //     openssl_pkcs12_export($sscert, 
    //         $certificateOutput, $key, $privateKeyPass);
        
    //     // openssl_pkcs12_read to read the pkcs12
    //     // certificate and store into array
    //     openssl_pkcs12_read ($certificateOutput, 
    //         $readableOutput,  $privateKeyPass );
            
    //     var_dump(($readableOutput));
    // }

}
