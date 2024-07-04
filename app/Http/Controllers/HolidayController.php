<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResponse;
use App\Models\Holiday;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HolidayController extends BaseController
{
    public function getNationalHolidays($tahun)
    {
        $validator = Validator::make(['tahun' => $tahun], [
            'tahun' => 'required|digits:4|integer'
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.digits' => 'Tahun harus berupa angka 4 digit.',
            'tahun.integer' => 'Tahun harus berupa angka.'
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $response = Http::get("https://api-harilibur.vercel.app/api?year={$tahun}");
        $holidays = $response->json();
        foreach ($holidays as $holiday) {
            if ($holiday['is_national_holiday'] == true) {
                Holiday::updateOrCreate(
                    ['holidays_date' => $holiday['holiday_date']],
                    ['holidays_name' => $holiday['holiday_name']]
                );
            }
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', Holiday::all());
    }

    public function getAllHolidays()
    {
        return new ApiResponse($this->messageSuccess, 200, 'OK', Holiday::all());
    }

    public function getHolidayById($id)
    {
        $holiday = Holiday::find($id);
        if ($holiday) {
            return new ApiResponse($this->messageSuccess, 200, 'OK', $holiday);
        } else {
            return new ApiResponse($this->messageError, 404, 'Libur tidak ditemukan !');
        }
    }

    public function getHolidayByStatus($status)
    {
        $validator = Validator::make(['status' => $status], [
            'status' => 'required|boolean'
        ], [
            'status.required' => 'Status wajib diisi.',
            'status.boolean' => 'Status harus berupa boolean.'
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $holidays = Holiday::where('status', $status)->get();
        if ($holidays->isEmpty()) {
            return new ApiResponse($this->messageError, 200, 'Libur tidak ditemukan !');
        }
        return new ApiResponse($this->messageSuccess, 200, 'OK', $holidays);
    }

    public function storeOrUpdateHoliday(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holidays_date' => 'required|date_format:Y-m-d',
            'holidays_name' => 'required|string',
            'status' => 'required|boolean'
        ], [
            'holidays_date.required' => 'Tanggal wajib diisi.',
            'holidays_date.date' => 'Tanggal harus berupa tanggal, Contoh: 2021-12-31.',
            'holidays_name.required' => 'Nama hari libur wajib diisi.',
            'holidays_name.string' => 'Nama hari libur harus berupa string.',
            'status.required' => 'Status wajib diisi.',
            'status.boolean' => 'Status harus berupa boolean.'
        ]);

        if ($validator->fails()) {
            return new ApiResponse($this->messageError, 400, $validator->errors());
        }

        $holiday = Holiday::updateOrCreate(
            ['holidays_date' => $request->holidays_date],
            ['holidays_name' => $request->holidays_name, 'status' => $request->status]
        );

        return new ApiResponse($this->messageSuccess, 200, 'Libur berhasil disimpan !', $holiday);
    }

    public function deleteHoliday($id)
    {
        $holiday = Holiday::find($id);
        if ($holiday) {
            $holiday->delete();
            return new ApiResponse($this->messageSuccess, 200, 'Libur berhasil dihapus !');
        } else {
            return new ApiResponse($this->messageError, 404, 'Libur tidak ditemukan !');
        }
    }
}
