<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\PDF;
use App;
use Storage;
use Response;
use Illuminate\Support\Facades\Files;
use App\Document;
use App\Models\User;
use URL;
use App\Libraries\FileHandling;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use setasign\Fpdf\Fpdf;



class DocumentController extends Controller
{

    public function create(Request $request) {
        $validate_id = bin2hex(openssl_random_pseudo_bytes(10));

        $document = new Document;
        $document->validate_id = $validate_id;
        $document->user_id = Auth()->user()->id;

        // Saving Document File
        $file = $request->file('document');
        $destination_path = '/public/uploads/pdf/';
        $process_file = new FileHandling;
        $document_path = $process_file->fileSave($file, $destination_path);
        $document->document = $document_path;
        
        if($document->save()) {
            return response()->json($document, 201);
        }

        return response()->json([
            'status' => 400,
            'message' => 'Document cannot be created'
        ], 400);

    }

    public function list() {
        $document = Document::where('user_id', Auth()->user()->id)->get();
        return response()->json($document, 200);
    }

    public function detail($id) {
        $document = Document::find($id);
        if($document) {
            $pdf_url = "http://localhost:8000/uploads/pdf/".substr($document->document, strrpos($document->document, '/') + 1);
            $document['pdf_url'] = $pdf_url;
            return response()->json($document);
        }
        return response()->json([
            'status'=> 404,
            'message'=> 'Document is not found',
        ], 404);
    }

    public function delete($id) {
        $document = Document::find($id);
        if($document) {
            unlink(base_path().$document->document);
            if($document->delete()) {
                return response()->json([
                    'status' => 204,
                    'message' => 'Document successfully deleted'
                ], 204);
            }
        }
        return response()->json([
            'status' => 404,
            'message' => 'Document is not found'
        ], 404);
    }

    public function verify($validate_id) {
        $document = Document::where('validate_id', $validate_id)->first();
        if($document) {
            $user = User::find($document->user_id);
            return response()->json([
                'status' => 200,
                'data' => [
                    'nim'=>$user->username,
                    'name'=>$user->name,
                    'created'=>$document->created_at,
                    'sign_date'=>$document->updated_at,
                    'status'=>$document->status
                ]
            ], 200);
        }
        return response()->json([
            'status' => 400,
            'message' => "Document is not found"
        ], 200);
    }

    public function documentPdf($id) {
        $document = Document::find($id);
        $filename = substr($document->document, strrpos($document->document, '/') + 1);
        $headers = array(
            'Content-Type: application/pdf',
          );
        $pdf = new PDF();
        return $pdf->stream();
    }
}
