<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class MailController extends Controller
{

    public function sendMailVerification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'             => 'required|email',
                'name'              => 'required',
                'url_verification'  => 'required|url'
            ]);

            if($validator->fails()) {
                return response()->json([
                    'status'    => 'error',
                    'code'      => 400,
                    'message'   => $validator->errors()
                ]);
            }

            $email = $request->email;
            $name = $request->name;
            $url = $request->url_verification;
            $subject = "Verifikasi Email - MAHA APPS";

            Mail::send('template.mail.register-verification', ['name' => $name, 'url' => $url], function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject);
            });

        } catch (Exception $e) {
            return response()->json([
                'status'    => 'error',
                'code'      => 400,
                'message'   => $e->getMessage()
            ]);
        }
    }
}
