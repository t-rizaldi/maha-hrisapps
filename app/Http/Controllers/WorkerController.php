<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\JobTitle;
use App\Models\Worker;
use App\Models\WorkerDocument;
use App\Models\WorkerWorkHour;
use App\Models\WorkHour;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WorkerController extends Controller
{
    public function getWorker(Request $request)
    {
        try {
            $workers = Worker::with(['jobTitle', 'branch', 'bank', 'document']);

            if($request->has('branch')) {
                $branchCode = $request->query('branch');
                if(!empty($branchCode)) $workers->where('branch_code', $branchCode);
            }

            if($request->has('bank')) {
                $bankId = $request->query('bank');
                if(!empty($bankId)) $workers->where('bank_id', $bankId);
            }

            if($request->has('status')) {
                $status = $request->query('status');
                if(!empty($status) || $status == 0) $workers->where('status', $status);
            }

            $workers = $workers->get();

            if(count($workers) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $workers
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getWorkerById($workerId)
    {
        try {
            $worker = Worker::with(['jobTitle', 'branch', 'bank', 'document'])
                            ->where('id', $workerId)
                            ->first();

            if(empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $worker
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function storeWorker(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nik'                   => 'required|unique:workers,nik',
                'fullname'              => 'required',
                'phone_number'          => 'required',
                'current_address'       => 'required',
                'job_title_id'          => 'required',
                'branch_code'           => 'required',
                'salary'                => 'required|numeric',
                'meal_cost'             => 'nullable|numeric',
                'bank_id'               => 'required',
                'bank_account_number'   => 'required',
                'bank_account_name'     => 'required',
                'photo'                 => 'required|image|file|max:2048',
                'ktp'                   => 'required|mimes:png,jpg,pdf|file|max:2048'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Job Title Check
            $jobTitle = JobTitle::find($request->job_title_id);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jabatan / Keahlian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($jobTitle->is_daily != 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jabatan / Keahlian bukan untuk pekerja harian',
                    'data'      => []
                ], 200);
            }

            // Branch Check
            $branch = Branch::where('branch_code', $request->branch_code)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Cabang tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Bank Check
            $bank = Bank::find($request->bank_id);

            if(empty($bank)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Bank tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Create
            $workerData = [
                'nik'                   => $request->nik,
                'fullname'              => $request->fullname,
                'phone_number'          => $request->phone_number,
                'current_address'       => $request->current_address,
                'job_title_id'          => $request->job_title_id,
                'branch_code'           => $request->branch_code,
                'salary'                => $request->salary,
                'meal_cost'             => $request->meal_cost,
                'bank_id'               => $request->bank_id,
                'bank_account_number'   => $request->bank_account_number,
                'bank_account_name'     => $request->bank_account_name,
                'status'                => 1
            ];

            $worker = Worker::create($workerData);

            // photo
            $photoPath = $request->file('photo')->store("uploads/worker/$worker->id/photo");

            $worker->photo = $photoPath;
            $worker->save();

            // ktp
            $ktpPath = $request->file('ktp')->store("uploads/worker/$worker->id/document/ktp");

            $documentData = [
                'worker_id'     => $worker->id,
                'document_type' => 'ktp',
                'document'      => $ktpPath
            ];

            $document = WorkerDocument::create($documentData);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => [
                    'worker'    => $worker,
                    'document'  => $document
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function updateWorker(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'worker_id'             => 'required',
                'nik'                   => 'required',
                'fullname'              => 'required',
                'phone_number'          => 'required',
                'current_address'       => 'required',
                'job_title_id'          => 'required',
                'branch_code'           => 'required',
                'salary'                => 'required|numeric',
                'meal_cost'             => 'nullable|numeric',
                'bank_id'               => 'required',
                'bank_account_number'   => 'required',
                'bank_account_name'     => 'required',
                'photo'                 => 'nullable|image|file|max:2048',
                'ktp'                   => 'nullable|mimes:png,jpg,pdf|file|max:2048'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Worker
            $worker = Worker::find($request->worker_id);

            if(empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // NIK Check
            if($request->nik != $worker->nik) {
                $validator = Validator::make($request->all(), [
                    'nik'   => 'required|unique:workers,nik'
                ]);

                if($validator->fails()) {
                    return response()->json([
                        'status'    => 'error',
                        'code'      => 400,
                        'message'   => $validator->errors(),
                        'data'      => []
                    ], 400);
                }
            }

            // Job Title Check
            $jobTitle = JobTitle::find($request->job_title_id);

            if(empty($jobTitle)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jabatan / Keahlian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            if($jobTitle->is_daily != 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jabatan / Keahlian bukan untuk pekerja harian',
                    'data'      => []
                ], 200);
            }

            // Branch Check
            $branch = Branch::where('branch_code', $request->branch_code)->first();

            if(empty($branch)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Cabang tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Bank Check
            $bank = Bank::find($request->bank_id);

            if(empty($bank)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Bank tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $workerData = [
                'nik'                   => $request->nik,
                'fullname'              => $request->fullname,
                'phone_number'          => $request->phone_number,
                'current_address'       => $request->current_address,
                'job_title_id'          => $request->job_title_id,
                'branch_code'           => $request->branch_code,
                'salary'                => $request->salary,
                'meal_cost'             => $request->meal_cost,
                'bank_id'               => $request->bank_id,
                'bank_account_number'   => $request->bank_account_number,
                'bank_account_name'     => $request->bank_account_name,
            ];

            if($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store("uploads/worker/$worker->id/photo");
                $workerData['photo'] = $photoPath;

                if(Storage::exists($worker->photo)) Storage::delete($worker->photo);
            }

            if($request->hasFile('ktp')) {
                $ktpPath = $request->file('ktp')->store("uploads/worker/$worker->id/document/ktp");

                $workerDocument = WorkerDocument::where('worker_id', $worker->id)
                                ->where('document_type', 'ktp')
                                ->first();

                if(Storage::exists($workerDocument->document)) Storage::delete($workerDocument->document);

                $workerDocument->document = $ktpPath;
                $workerDocument->save();
            }

            Worker::where('id', $worker->id)->update($workerData);

            return response()->json([
                'status'    => 'success',
                'code'      => 204,
                'message'   => 'OK',
                'data'      => Worker::with(['jobTitle', 'branch', 'bank', 'document'])
                                ->where('id', $worker->id)
                                ->first()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteWorker(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'worker_id' => 'required'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Worker
            $worker = Worker::find($request->worker_id);

            if(empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            $workerDocuments = WorkerDocument::where('worker_id', $worker->id)->get();

            // FILE DELETE
            $photo = $worker->photo;

            if(!empty($photo)) {
                if(Storage::exists($photo)) Storage::delete($photo);
            }

            // delete document
            foreach($workerDocuments as $doc) {
                if(!empty($doc->document)) {
                    if(Storage::exists($doc->document)) Storage::delete($doc->document);
                }
            }

            // DELETE
            WorkerDocument::where('worker_id', $worker->id)->delete();
            Worker::where('id', $worker->id)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => []
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function changeStatusWorker(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'worker_id' => 'required',
                'status'    => 'required|in:0,1'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors(),
                    'data'      => []
                ], 400);
            }

            // Get Worker
            $worker = Worker::with(['jobTitle', 'branch', 'bank', 'document'])->where('id', $request->worker_id)->first();

            if(empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // Update
            $worker->status = $request->status;
            $worker->save();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $worker
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    //WORK HOUR
    public function getWorkerWorkHour($workerId)
    {
        try {
            // Get worker
            $worker = Worker::find($workerId);
            if (empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan'
                ], 200);
            }

            // Get Work Hour
            $workHour = WorkerWorkHour::with([
                            'sundayCode',
                            'mondayCode',
                            'tuesdayCode',
                            'wednesdayCode',
                            'thursdayCode',
                            'fridayCode',
                            'saturdayCode',
                        ])->where('worker_id', $workerId)->first();


            if (empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja Pekerja harian tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $workHour
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function createWorkerWorkHour(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'worker_id'       => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // Get worker
            $worker = Worker::find($request->worker_id);

            if (empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja harian tidak ditemukan !'
                ], 200);
            }

            // Work Hour Check
            $workHourMsg = [];

            // sunday
            if (!empty($request->sunday)) {
                $workHour = WorkHour::where('work_hour_code', $request->sunday)->first();

                if (empty($workHour)) {
                    $workHourMsg['sunday'] = 'Jam kerja tidak ditemukan';
                }
            }

            // monday
            if (!empty($request->monday)) {
                $workHour = WorkHour::where('work_hour_code', $request->monday)->first();

                if (empty($workHour)) {
                    $workHourMsg['monday'] = 'Jam kerja tidak ditemukan';
                }
            }

            // tuesday
            if (!empty($request->tuesday)) {
                $workHour = WorkHour::where('work_hour_code', $request->tuesday)->first();

                if (empty($workHour)) {
                    $workHourMsg['tuesday'] = 'Jam kerja tidak ditemukan';
                }
            }

            // wednesday
            if (!empty($request->wednesday)) {
                $workHour = WorkHour::where('work_hour_code', $request->wednesday)->first();

                if (empty($workHour)) {
                    $workHourMsg['wednesday'] = 'Jam kerja tidak ditemukan';
                }
            }

            // thursday
            if (!empty($request->thursday)) {
                $workHour = WorkHour::where('work_hour_code', $request->thursday)->first();

                if (empty($workHour)) {
                    $workHourMsg['thursday'] = 'Jam kerja tidak ditemukan';
                }
            }

            // friday
            if (!empty($request->friday)) {
                $workHour = WorkHour::where('work_hour_code', $request->friday)->first();

                if (empty($workHour)) {
                    $workHourMsg['friday'] = 'Jam kerja tidak ditemukan';
                }
            }

            // saturday
            if (!empty($request->saturday)) {
                $workHour = WorkHour::where('work_hour_code', $request->saturday)->first();

                if (empty($workHour)) {
                    $workHourMsg['saturday'] = 'Jam kerja tidak ditemukan';
                }
            }

            if (!empty($workHourMsg)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => $workHourMsg
                ], 200);
            }

            // CREATE
            $data = [
                'worker_id'   => $request->worker_id,
                'sunday'        => $request->sunday,
                'monday'        => $request->monday,
                'tuesday'       => $request->tuesday,
                'wednesday'     => $request->wednesday,
                'thursday'      => $request->thursday,
                'friday'        => $request->friday,
                'saturday'      => $request->saturday,
            ];

            $workerWorkHour = WorkerWorkHour::updateOrCreate(['worker_id' => $request->worker_id], $data);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $workerWorkHour
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function deleteWorkerWorkHour($workerId)
    {
        try {
            // Get worker
            $worker = Worker::find($workerId);

            if (empty($worker)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Pekerja Harian tidak ditemukan'
                ], 200);
            }

            // Get Work Hour
            $workHour = WorkerWorkHour::where('worker_id', $workerId)->first();

            if (empty($workHour)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Jam kerja pekerja harian tidak ditemukan !',
                    'data'      => []
                ], 200);
            }
            workerWorkHour::where('worker_id', $workerId)->delete();
            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

}