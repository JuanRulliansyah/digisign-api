<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\PDF;
use App;
use DB;
use Storage;
use Response;
use Illuminate\Support\Facades\Files;
use App\Document;
use App\Document_share;
use App\M_tema_surat;
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
        $document->ref_number = $request->input('ref_number');
        $document->kd_tema = $request->input('kd_tema');
        $document->document_date = $request->input('document_date');
        $document->subject = $request->input('subject');
        $document->message = $request->input('message');

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

    public function share(Request $request) {
        $user = User::where('username', $request->input('username'))->first();
        $share = new Document_share;
        $share->document_id = $request->input('document_id');
        $share->from_user_id = Auth()->user()->id;
        $share->to_user_id = $user->id;
        $share->status = "unsigned";
        $share->signed_date = NULL;
        if($share->save()) {
            return response()->json($share, 201);
        }
        return response()->json([
            'status' => 400,
            'message' => 'Cannot share the document'
        ], 400);
    }

    public function availableUser(Request $request) {
        $query_user = $request->query_user;
        if($query_user) {
            $user = DB::table('users')
            ->select(DB::raw("CONCAT(users.name, '-',  users.username) as label"), 'username AS value')
            ->where(function ($query) use ($query_user) {
                $query->where('name', 'like', '%' . $query_user . '%')
                    ->orWhere('username', 'like', '%' . $query_user . '%');
            })->get();
            return response()->json($user, 200);
        }
        return response()->json([['value'=>'Kosong', 'label'=>'Kosong']], 200);
    }

    public function list() {
        $document = Document::where('user_id', Auth()->user()->id)->get();
        return response()->json($document, 200);
    }

    public function inbox(Request $request) {
        $filter = $request->status;
        $inbox = Document_share::select('document_shares.*', 'documents.document', 'documents.id AS document_id', DB::raw("CONCAT(users.name, '-',  users.username) AS user_from_identity"))
            ->where('to_user_id', Auth()->user()->id)
            ->where(function($query) use ($filter) {
                if($filter) {
                    $query->where('status', $filter);
                }
            })
            ->leftJoin('users', 'users.id', '=', 'document_shares.from_user_id')
            ->leftJoin('documents', 'documents.id', '=', 'document_shares.document_id')
            ->get();
        return response()->json($inbox, 200);
    }

    public function outbox(Request $request) {
        $filter = $request->status;
        $outbox = Document_share::select('document_shares.*', 'documents.document', DB::raw("CONCAT(users.name, '-',  users.username) AS user_to_identity"))
            ->where('from_user_id', Auth()->user()->id)
            ->where(function($query) use ($filter) {
                if($filter) {
                    $query->where('status', $filter);
                }
            })
            ->leftJoin('users', 'users.id', '=', 'document_shares.to_user_id')
            ->leftJoin('documents', 'documents.id', '=', 'document_shares.document_id')
            ->get();
        return response()->json($outbox, 200);
    }

    public function purpose() {
        $purpose = M_tema_surat::select(DB::raw("CONCAT(kd_tema, ' | ',  nama_tema) AS label"), 'kd_tema AS value')->get();
        return response()->json($purpose, 200);
    }

    public function shareSignList($id) {
        $share = Document_share::select('document_shares.*', DB::raw("CONCAT(users.name, '-',  users.username) AS user_to_identity"))
        ->leftJoin('users', 'users.id', '=', 'document_shares.to_user_id')
        ->leftJoin('documents', 'documents.id', '=', 'document_shares.document_id')->where('document_id', $id)->get();
        if($share) {
            return response()->json($share, 200);
        }
        return response()->json([
            'status' => 400,
            'message' => "Document Sign is not found"
        ], 200);
    }

    public function detail($id) {
        $document = Document::select('documents.id AS id', 'documents.*', 'users.username', 'users.name')->leftJoin('users', 'users.id', '=', 'documents.user_id')->find($id);
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
                    'created'=>$document->created_at->format('d-m-Y H:i:s'),
                    'sign_date'=>$document->updated_at->format('d-m-Y H:i:s'),
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
