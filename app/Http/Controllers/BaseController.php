<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Exceptions\HttpResponseException;
// use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    //
    /**
     * success response method.
     *
     * @return JsonResponse
     */
    public function sendResponse($result, $message): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];
  
        return response()->json($response, 200);
    }
    /**
     * return error response.
     *
     * @return JsonResponse
     */
    public function sendError($error, $errorMessages = [], $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
  
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
  
        return response()->json($response, $code);
    }

    public function rollback($e, $message ="Quelque chose s'est mal passé. Contacter le support technique"){
        DB::rollBack();
        self::throw($e, $message);
    }

    public function throw($e, $message = "Quelque chose s'est mal passé. Contacter le support technique"): never
    {
        \Log::info($e);
        throw new HttpResponseException(response()->json(["message" => $message], 500));
    }

}
