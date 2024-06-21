<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SkillController extends Controller
{
    private $api;
    private $client;
    private $createCount;

    public function __construct()
    {
        $this->api = "https://emsiservices.com/skills/versions/latest/skills";
        $this->client = new Client();
        $this->createCount = 0;
    }

    public function getAllFromLightcast()
    {
        try {
            $responseData = $this->client->get("$this->api", [
                'headers'   => [
                    'Authorization' => 'Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6IjNDNjZCRjIzMjBGNkY4RDQ2QzJERDhCMjI0MEVGMTFENTZEQkY3MUYiLCJ0eXAiOiJKV1QiLCJ4NXQiOiJQR2FfSXlEMi1OUnNMZGl5SkE3eEhWYmI5eDgifQ.eyJuYmYiOjE3MTcwMzk3NDYsImV4cCI6MTcxNzA0MzM0NiwiaXNzIjoiaHR0cHM6Ly9hdXRoLmVtc2ljbG91ZC5jb20iLCJhdWQiOlsiZW1zaV9vcGVuIiwiaHR0cHM6Ly9hdXRoLmVtc2ljbG91ZC5jb20vcmVzb3VyY2VzIl0sImNsaWVudF9pZCI6InBkODZ3YjJ0Ymkza2ozcjMiLCJuYW1lIjoiVCBSaXphbGRpIEZhZGxpIiwiY29tcGFueSI6IlBUIE1haGEgQWtiYXIgU2VqYWh0ZXJhIiwiZW1haWwiOiJ0ZXVrdXJpemFsZGlmYWRsaUBnbWFpbC5jb20iLCJpYXQiOjE3MTcwMzk3NDYsInNjb3BlIjpbImVtc2lfb3BlbiJdfQ.xo21sfcrIJspq_cRUsxIBB5BXJbdyCJFCgcKA1hVcSuK4__WB0wYDEK39GzFzcr6D4L2ztU35VN_hPCOlmEVpDePkTVv84Xh8KnvQTn-HBSBHcVbB3Ikaaq3tK81gnMMN5xgRDAEmiOfffgDz6F45JjwRsPLnEz70uwZ5Yv2EF8jkwIMVqCiXQwP_QHoNmC5cSN081HCnSWbWw1IG4_dQwmesMzlR-oZL09_vSvS10RBP6Hx5NAn2ibuga-JCtBtP4tNfYGg6kTcSlpjYZor14r8rN63Fag5oXHa5pcn4ShxRbgB1x4ytJJSDdrIgLxW7ARRCVFkOmQwyDjn78cT8g'
                ]
            ]);

            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();

            $response = json_decode($body, true);

            $skillData = $response['data'];

            foreach($skillData as $item) {
                $data = [
                    'name'      => $item['name'],
                    'info_url'  => $item['infoUrl'],
                    'type'      => $item['type']['id']
                ];

                Skill::create($data);
                $this->createCount++;
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => [
                    'create_count'  => $this->createCount
                ]
            ], 200);

        } catch (ClientException $e) {
            $responseData = $e->getResponse();
            $statusCode = $responseData->getStatusCode();
            $body = $responseData->getBody()->getContents();
            $response = json_decode($body);

            return response()->json([$response], $statusCode);
        }
    }

    public function index(Request $request)
    {
        try {
            $skills = Skill::query();

            if($request->has('q')) {
                $q = $request->query('q');
                $skills->where('name', 'LIKE', "%$q%");
            }

            if($request->has('type')) {
                $type = $request->query('type');
                $skills->where('type', $type);
            }

            $skills = $skills->get();

            if(count($skills) < 1) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Data skill tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $skills
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function getById($id)
    {
        try {
            $skill = Skill::find($id);

            if(empty($skill)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Skill tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $skill
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validation Check
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'info_url'  => 'nullable|url',
                'type'      => 'required|in:ST1,ST2,ST3'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // CREATE
            $skillData = [
                'name'      => $request->name,
                'info_url'  => $request->info_url,
                'type'      => $request->type
            ];

            $skill = Skill::create($skillData);

            return response()->json([
                'status'    => 'success',
                'code'      => 201,
                'message'   => 'OK',
                'data'      => $skill
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function update($skillId, Request $request)
    {
        try {
            // GET SKILL
            $skill = Skill::find($skillId);

            if(empty($skill)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Skill tidak ditemukan',
                    'data'      => []
                ], 200);
            }
            
            // Validation Check
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'info_url'  => 'nullable|url',
                'type'      => 'required|in:ST1,ST2,ST3'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ], 400);
            }

            // UPDATE
            $skillData = [
                'name'      => $request->name,
                'info_url'  => $request->info_url,
                'type'      => $request->type
            ];

            Skill::where('id', $skillId)->update($skillData);

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'OK',
                'data'      => $skillData
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ], 400);
        }
    }

    public function delete($skillId)
    {
        try {
            // GET SKILL
            $skill = Skill::find($skillId);

            if(empty($skill)) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 204,
                    'message'   => 'Skill tidak ditemukan',
                    'data'      => []
                ], 200);
            }

            // DELETE
            Skill::where('id', $skillId)->delete();

            return response()->json([
                'status'    => 'success',
                'code'      => 200,
                'message'   => 'Skill berhasil dihapus'
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
