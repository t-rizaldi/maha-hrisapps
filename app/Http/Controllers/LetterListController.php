<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LetterList;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LetterListController extends Controller
{
    public function index()
    {
        try {
            $letters = LetterList::all();

            if(count($letters) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Letters not found',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $letters
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // NEW LETTER NUMBER
    public function getNewCompanyLetterNumber($categoryCode)
    {
        try {
            // CATEGORY CHECK
            $category = Category::where('code', $categoryCode)->first();

            if(empty($category)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Letter code invalid',
                    'data'      => []
                ], 200);
            }

            // GET NEW LETTER NUMBER
            $lastLetter = LetterList::where('category_code', $categoryCode)
                            ->where('letter_number', 'LIKE', '%/MAHA/%')
                            ->where('letter_number', 'LIKE', '%/' . date('Y'))
                            ->orderBy('id', 'desc')
                            ->first();

            if(!empty($lastLetter)) {
                $lastLetterNumber = $lastLetter->letter_number;
                $explodeLetterNumber = explode('/', $lastLetterNumber);
                $lastNumber = $explodeLetterNumber[0];
                $nextNumber = $lastNumber + 1;
                $newNumber = sprintf('%03s', $nextNumber);

                $newLetterNumber = "$newNumber/$categoryCode/MAHA/" . romanMonth(date('m')) . '/' . date('Y');
            } else {
                $newLetterNumber = "001/$categoryCode/MAHA/" . romanMonth(date('m')) . '/' . date('Y');
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $newLetterNumber
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    // STORE SURAT
    public function storeSurat(Request $request)
    {
        try {
            // Cek Validation
            $validator = Validator::make($request->all(), [
                'category_code'         => 'required',
                'letter_number'         => 'required|unique:letter_lists,letter_number',
                'subject'               => 'required',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // cek category
            $category = Category::where('code', $request->category_code)->first();

            if(empty($category)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Letter code invalid'
                ], 200);
            }

            // store data
            $data = [
                'employee_receiving_id' => $request->employee_receiving_id,
                'employee_creator_id'   => $request->employee_creator_id,
                'category_code'         => $request->category_code,
                'letter_number'         => $request->letter_number,
                'subject'               => $request->subject,
                'description'           => $request->description,
            ];

            $letter = LetterList::create($data);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $letter
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }
}
