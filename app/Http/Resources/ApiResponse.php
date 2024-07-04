<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse extends JsonResource
{
    protected $status;
    protected $code;
    protected $message;
    protected $data;

    public function __construct($status, $code, $message, $data = [])
    {
        parent::__construct(null);

        $this->status = $status;
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->code);
    }
}
