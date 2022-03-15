<?php
namespace App\Http\Controllers;

use Storage;
use TCPDF;
use Illuminate\Http\Request;
use App\Document;
use App\Certificate;
use App\Position_letter;
use App\Profile;
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

    public function sign(Request $request)
    {

        // Checking Profile Availability
        $profile = Profile::where('user_id', Auth()->user()->id)->where('active', 'T')->where('status', 'confirmed')->first();
        if(!$profile) {
            return response()->json([
                "status"=> 401,
                "message"=> 'Profile (KYC) is required'
            ], 401);
        }

        // Checking Position Letter Availability
        $position_letter = Position_letter::where('user_id', Auth()->user()->id)->where('active', 'T')->where('status', 'usable')->first();
        if(!$position_letter) {
            return response()->json([
                "status"=> 401,
                "message"=> 'Position Letter is required'
            ], 401);
        }

        // Checking Certificate Availability
        $certificate = Certificate::where('user_id', Auth()->user()->id)->where('status', 'usable')->first();
        if(!Hash::check($request->input('password'), $certificate->password)) {
            return response()->json([
                "status"=> 401,
                "message"=> 'Certificate is required'
            ], 401);
        }

        if($certificate) { 
            $document = Document::find($request->input('document_id'));
            if($document->status == "signed") {
                return response()->json([
                    "status"=> 400,
                    "message"=> "Document already signed"
                ], 400);
            }

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

}
