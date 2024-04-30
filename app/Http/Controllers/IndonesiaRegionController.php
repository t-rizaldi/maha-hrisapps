<?php

namespace App\Http\Controllers;

use App\Models\IndonesiaProvince;
use App\Models\IndonesiaRegency;
use App\Models\IndonesiaDistrict;
use App\Models\IndonesiaVillage;

class IndonesiaRegionController extends Controller
{
    public function getAllProvince()
    {
        try {
            $province = IndonesiaProvince::all();
            return response()->json([
                'status' => 'success',
                'code'  => 200,
                'message' => 'OK',
                'data'   => $province,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getAllRegencyByIDProvince($id)
    {
        try {
            $regency = IndonesiaRegency::where('province_id', $id)->get();
            if ($regency->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'code' => 204,
                    'message' => 'Data Not Found !',
                    'data' => []
                ], 200);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $regency,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getAllDistrictByIDRegency($id)
    {
        try {
            $district = IndonesiaDistrict::where('regency_id', $id)->get();
            if ($district->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'code' => 204,
                    'message' => 'Data Not Found !',
                    'data' => [],
                ], 200);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $district,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getAllVillageByIDDistrict($id)
    {
        try {
            $village = IndonesiaVillage::where('district_id', $id)->get();
            if ($village->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'code' => 204,
                    'message' => 'Data Not Found !',
                    'data' => [],
                ], 200);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $village,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getProvinceByID($id)
    {
        try {
            $province = IndonesiaProvince::where('id', $id)->first();
            if (!$province) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Data Not Found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $province,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    public function getRegencyByID($id)
    {
        try {
            $regency = IndonesiaRegency::where('id', $id)->first();
            if (!$regency) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Data Not Found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $regency,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getDistrictByID($id)
    {
        try {
            $district = IndonesiaDistrict::where('id', $id)->first();
            if (!$district) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Data Not Found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $district,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    public function getVillageByID($id)
    {
        try {
            $village = IndonesiaVillage::where('id', $id)->first();
            if (!$village) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Data Not Found',
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'OK',
                'data'   => $village,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'      => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
