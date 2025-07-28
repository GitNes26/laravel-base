<?php

namespace App\Http\Controllers;

use App\Models\ObjResponse;
use App\Models\PersonalInfo;
use App\Models\VW_PersonalInfo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonalInfoController extends Controller
{
    /**
     * Mostrar lista de informacion personal.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = VW_PersonalInfo::orderBy('id', 'desc');
            if ($auth->role_id > 2) $list = $list->where("active", true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de informacion personal.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ index ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar listado para un selector.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function selectIndex(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $list = VW_PersonalInfo::where('active', true)
                ->select('id as id', 'full_name as label', 'img_ine', 'img_photo')
                ->orderBy('name', 'asc')->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de informacion personal.';
            $response->data["alert_text"] = "informacion personal encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar informacion personal.
     *
     * @param \Illuminate\Http\Request $request
     * @param Int $id
     *
     * @return \Illuminate\Http\Response $response
     */
    public function createOrUpdate(Request $request, Response $response, Int $id = null)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $duplicate = $this->validateAvailableData($request->email, $request->phone, $id);
            if ($duplicate["result"] == true) {
                $response->data = $duplicate;
                return response()->json($response);
            }

            $personal_info = PersonalInfo::find($id);
            if (!$personal_info) $personal_info = new PersonalInfo();

            $personal_info->fill($request->all());
            $personal_info->save();

            // $personal_info->img_ine = $request->img_ine;
            $this->ImageUp($request, 'img_ine', "personal-info", $personal_info->id, 'INE', $id == null ? true : false, "noImage.png", $personal_info);
            $this->ImageUp($request, 'img_photo', "personal-info", $personal_info->id, 'FOTO', $id == null ? true : false, "noImage.png", $personal_info);


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | informacion personal editada.' : 'peticion satisfactoria | informacion personal registrada.';
            $response->data["alert_text"] = $id > 0 ? "Información Personal editada" : "Información Personal registrada";
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar informacion personal.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $personal_info = VW_PersonalInfo::find($id);
            if ($internal) return $personal_info;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | informacion personal encontrado.';
            $response->data["result"] = $personal_info;
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) informacion personal.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            PersonalInfo::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | informacion personal eliminada.";
            $response->data["alert_text"] = "Información Personal eliminada";
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ delete ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Activar o Desactivar" (cambiar estado activo=1/0).
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function disEnable(Response $response, Int $id, string $active)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            PersonalInfo::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "0" ? 'desactivada' : 'reactivada';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | informacion personal $description.";
            $response->data["alert_text"] = "Información Personal $description";
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Eliminar uno o varios registros.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function deleteMultiple(Request $request, Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            // echo "$request->ids";
            // $deleteIds = explode(',', $ids);
            $countDeleted = sizeof($request->ids);
            PersonalInfo::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'peticion satisfactoria | registro eliminado.' : "peticion satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Registro eliminada' : "Registros eliminados  ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "PersonalInfoController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }


    /**
     * Funcion para validar que campos no deben de duplicarse sus valores.
     *
     * @return ObjRespnse|false
     */
    private function validateAvailableData($email, $phone, $id)
    {
        // #VALIDACION DE DATOS REPETIDOS
        $duplicate = $this->checkAvailableData('personal_info', 'email', $email, 'El correo electrónico', 'email', $id, null);
        if ($duplicate["result"] == true) return $duplicate;
        $duplicate = $this->checkAvailableData('personal_info', 'phone', $phone, 'El número telefonico', 'phone', $id, null);
        if ($duplicate["result"] == true) return $duplicate;
        return array("result" => false);
    }
}
