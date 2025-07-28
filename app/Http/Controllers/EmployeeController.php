<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ObjResponse;
use App\Models\VW_Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Mostrar lista de empleados.
     *
     * @return \Illuminate\Http\Response $response
     */
    public function index(Response $response)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $auth = Auth::user();
            $list = VW_Employee::orderBy('id', 'desc');
            if ($auth->role_id > 2) $list = $list->where("active", true);
            $list = $list->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'Peticion satisfactoria | Lista de empleados.';
            $response->data["result"] = $list;
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ index ~ Hubo un error -> " . $ex->getMessage();
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
            $list = VW_Employee::where('active', true)
                ->select('id as id', DB::raw("CONCAT(payroll_number,' - ',full_name) as label"))
                ->orderBy('full_name', 'asc')->get();

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | lista de empleados.';
            $response->data["alert_text"] = "Empleados encontrados";
            $response->data["result"] = $list;
            $response->data["toast"] = false;
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ selectIndex ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Crear o Actualizar empleado.
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
            $duplicate = $this->validateAvailableData($request->payroll_number, $request->cellphone, $id);
            if ($duplicate["result"] == true) {
                $response->data = $duplicate;
                return response()->json($response);
            }

            $employee = Employee::find($id);
            if (!$employee) $employee = new Employee();

            $employee->fill($request->except(['avatar', 'img_firm']));
            $employee->save();

            $this->ImageUp($request, 'avatar', "employees", $employee->id, 'AVATAR', $id == null ? true : false, "noImage.png", $employee);
            $this->ImageUp($request, 'img_firm', "employees", $employee->id, 'FIRMA', $id == null ? true : false, "noImage.png", $employee);


            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $id > 0 ? 'peticion satisfactoria | empleado editado.' : 'peticion satisfactoria | empleado registrado.';
            $response->data["alert_text"] = $id > 0 ? "Empleado editado" : "Empleado registrado";
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ createOrUpdate ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * Mostrar empleado.
     *
     * @param   int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response $response
     */
    public function show(Request $request, Response $response, Int $id, bool $internal = false)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            $employee = VW_Employee::find($id);
            if ($internal) return $employee;

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = 'peticion satisfactoria | empleado encontrado.';
            $response->data["result"] = $employee;
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ show ~ Hubo un error -> " . $ex->getMessage();
            Log::error($msg);
            $response->data = ObjResponse::CatchResponse($msg);
        }
        return response()->json($response, $response->data["status_code"]);
    }

    /**
     * "Eliminar" (cambiar estado activo=0) empleado.
     *
     * @param  int $id
     * @param  int $active
     * @return \Illuminate\Http\Response $response
     */
    public function delete(Response $response, Int $id)
    {
        $response->data = ObjResponse::DefaultResponse();
        try {
            Employee::where('id', $id)
                ->update([
                    'active' => false,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);

            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | empleado eliminado.";
            $response->data["alert_text"] = "Empleado eliminado";
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ delete ~ Hubo un error -> " . $ex->getMessage();
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
            Employee::where('id', $id)
                ->update([
                    'active' => $active === "reactivar" ? 1 : 0
                ]);

            $description = $active == "0" ? 'desactivado' : 'reactivado';
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = "peticion satisfactoria | empleado $description.";
            $response->data["alert_text"] = "Empleado $description";
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ disEnable ~ Hubo un error -> " . $ex->getMessage();
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
            Employee::whereIn('id', $request->ids)->update([
                'active' => false,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);
            $response->data = ObjResponse::SuccessResponse();
            $response->data["message"] = $countDeleted == 1 ? 'peticion satisfactoria | registro eliminado.' : "peticion satisfactoria | registros eliminados ($countDeleted).";
            $response->data["alert_text"] = $countDeleted == 1 ? 'Registro eliminado' : "Registros eliminados  ($countDeleted)";
        } catch (\Exception $ex) {
            $msg = "EmployeeController ~ deleteMultiple ~ Hubo un error -> " . $ex->getMessage();
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
    private function validateAvailableData($payroll_number, $cellphone, $id)
    {
        // #VALIDACION DE DATOS REPETIDOS
        $duplicate = $this->checkAvailableData('employees', 'payroll_number', $payroll_number, 'El numero de nómina', 'payroll_number', $id, null);
        if ($duplicate["result"] == true) return $duplicate;
        $duplicate = $this->checkAvailableData('employees', 'cellphone', $cellphone, 'El número celular del empleado', 'cellphone', $id, null);
        if ($duplicate["result"] == true) return $duplicate;
        return array("result" => false);
    }
}
