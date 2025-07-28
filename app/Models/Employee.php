<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * Especificar la conexion si no es la por default
     * @var string
     */
    //protected $connection = "db_mysql";

    /**
     * Los atributos que se solicitan y se guardan con la funcion fillable() en el controlador.
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'payroll_number',
        'avatar',
        'name',
        'plast_name',
        'mlast_name',
        'cellphone',
        'office_phone',
        'ext',
        'img_firm',
        'position_id',
        'department_id',
        // 'user_id',
        'active'
    ];

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'employees';

    /**
     * LlavePrimaria asociada a la tabla.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Obtener departamento al que pertenece el empleado.
     */
    public function position()
    {   //primero se declara FK y despues la PK del modelo asociado
        return $this->hasOne(Position::class, 'position_id', 'id');
    }

    /**
     * Obtener departamento al que pertenece el empleado.
     */
    public function department()
    {   //primero se declara FK y despues la PK del modelo asociado
        return $this->hasOne(Department::class, 'department_id', 'id');
    }

    // /**
    //  * Obtener puesto asociado con el empleado.
    //  */
    // public function workstation()
    // {   //primero se declara FK y despues la PK del modelo asociado
    //     return $this->belongsTo(Workstation::class, 'workstation_id', 'id');
    // }

    // /**
    //  * Obtener usuario asociado con el empleado.
    //  */
    // public function user()
    // {   //primero se declara FK y despues la PK del modelo asociado
    //     return $this->belongsTo(User::class, 'user_id', 'id');
    // }

    /**
     * Valores defualt para los campos especificados.
     * @var array
     */
    // protected $attributes = [
    //     'active' => true,
    // ];
}