<?php

namespace App\Http\Controllers;

use App;
use DB;
use App\Region;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class RegionController extends Controller
{
    public function listProvince(Request $request) {
        $province = $request->q;
        if($province) {
            $region = DB::table('regions')->where('province_name', 'like', '%' . $request->q . '%')->distinct()->get(['province_name AS label', 'province_name AS value']);
            return response()->json($region, 200);
        }
        return response()->json([['value'=>'Kosong', 'label'=>'Kosong']], 200);
    }

    public function listCity(Request $request) {
        $city = $request->q;
        $province = $request->province;
        if($city) {
            $region = DB::table('regions')->where('province_name', $province)->where('city_name', 'like', '%' . $request->q . '%')->distinct()->get(['city_name AS label', 'city_name AS value']);
            return response()->json($region, 200);
        }
        return response()->json([['value'=>'Kosong', 'label'=>'Kosong']], 200);

    }

    public function listDistrict(Request $request) {
        $district = $request->q;
        $city = $request->city;
        if($district) {
            $region = DB::table('regions')->where('city_name', $city)->where('district_name', 'like', '%' . $request->q . '%')->distinct()->get(['district_name AS label', 'district_name AS value']);
            return response()->json($region, 200);
        }
        return response()->json([['value'=>'Kosong', 'label'=>'Kosong']], 200);
    }

    public function listSubDistrict(Request $request) {
        $subdistrict = $request->q;
        $district = $request->district;
        if($subdistrict) {
            $region = DB::table('regions')->where('district_name', $district)->where('subdistrict_name', 'like', '%' . $request->q . '%')->distinct()->get(['subdistrict_name AS label', 'subdistrict_name AS value']);
            return response()->json($region, 200);
        }
        return response()->json([['value'=>'Kosong', 'label'=>'Kosong']], 200);
    }
}
