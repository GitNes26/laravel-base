<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ObjResponse;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    /**
     * Mostrar lista de departamentos.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = Department::orderBy('id', 'desc');
            if ($auth->role_id > 2) $list = $list->where("active", true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de departamentos.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            // $list = Department::where('active', true)
            //     ->select('id as id', DB::raw("CONCAT(COALESCE(letters, ''),' - ',department) as label"))
            //     ->orderBy('department', 'asc')->get();

            $auth = Auth::user();
            $userEmployee = VW_User::where('id', $auth->id)->first();

            $list = Department::where('active', true)
                ->select('id as id', DB::raw("CONCAT(COALESCE(letters, ''),' - ',department) as label"))
                ->orderBy('department', 'asc');
            if ($auth->role_id > 3 && $userEmployee && !\Str::contains($userEmployee->more_permissions, ['Ver Todas las Situaciones', 'todas'])) $list = $list->where("id", $userEmployee->department_id);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de departamentos.';
            $response->data["alert_text"] = "departamentos encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar departamentos.
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
            $duplicate = $this->validateAvailableData($request->letters, $request->department, $id);
            if ($duplicate["result"] == true) {
                $response->data = $duplicate;
                return response()->json($response);
            }

            $department = Department::find($id);
            if (!$department) $department = new Department();

            $department->fill($request->except(['img_sello']));
            $department->save();

            $this->ImageUp($request, 'img_sello', "departments", $id, 'Sello', $id == null ? true : false, "noImage.png", $department);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | departamento editada.' : 'peticion satisfactoria | departamento registrada.';
            $response->data["alert_text"] = $id > 0 ? "Departamento editado" : "Departamento registrado";
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar departamento.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $department = Department::find($id);
            if ($internal) return $department;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | departamento encontrado.';
            $response->data["result"] = $department;
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) departamento.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Department::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | departamento eliminado.";
            $response->data["alert_text"] = "Departamento eliminado";
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ delete ~ Hubo un error -> " . $ex->getMessage();
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
            Department::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "0" ? 'desactivado' : 'reactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | departamento $description.";
            $response->data["alert_text"] = "Departamento $description";
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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
            Department::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'peticion satisfactoria | registro eliminado.' : "peticion satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Registro eliminada' : "Registros eliminados  ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "DepartmentContrller ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
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
    private function validateAvailableData($letters, $department, $id)
    {
        // #VALIDACION DE DATOS REPETIDOS
        $duplicate = $this->checkAvailableData('departments', 'letters', $letters, 'El nombre del departamento', 'letters', $id, null);
        if ($duplicate["result"] == true) return $duplicate;
        $duplicate = $this->checkAvailableData('departments', 'department', $department, 'El nombre del departamento', 'department', $id, null);
        if ($duplicate["result"] == true) return $duplicate;
        return array("result" => false);
    }
}
