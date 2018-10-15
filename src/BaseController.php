<?php

namespace KairosSystems\CRUD;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Validator;

class BaseController extends Controller
{

    protected $msg_field = 'message';
    protected $not_found_msg = 'No se encuentra el registro';
    protected $reglas = '';
    protected $nombre = '';
    protected $elemento = [];

    public function index()
    {
        $this->validatePermission();
        if (property_exists($this, 'showables')) {
            return response()->json($this->class::get($this->showables));
        } else {
            return response()->json($this->class::get());
        }
    }

    public function store(Request $request)
    {
        $this->validatePermission('create');
        $request = $this->preparedata($request);
        $data =[];
        if (property_exists($this, 'fillables')) {
            $data = $request->only($this->fillables);
        }else {
            $data = $request->all();
        }
        $valid = $this->validateData($data, 'Create');
        if ($valid->passes()) {
            $data = $request->all();
            $row = $this->class::create($data);
            return response()->json([$this->msg_field => 'Creado', 'data' => $row ]);
        } else {
            return response()->json([$this->msg_field => implode('', $valid->errors()->all()) ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validatePermission('update');
        $data =[];
        if (property_exists($this, 'updatables')) {
            $data = $request->only($this->updatables);
        } else {
            $data = $request->all();
        }
        $valid = $this->validateData($data, 'Update');
        if ($valid->passes()) {
            $row = $this->class::find($id);
            if (is_null($row)) {
                return response()->json([$this->msg_field => $this->not_found_msg], 404);
            }
            $row->update($data);
            return response()->json([$this->msg_field => 'Actualizado', 'data' => $row]);
        } else {
            return response()->json([$this->msg_field => implode('', $valid->errors()->all())], 400);
        }
    }

    public function show($id)
    {
        $this->validatePermission();
        $row = $this->class::find($id);
        if (is_null($row)) {
            return response()->json([$this->msg_field => $this->not_found_msg], 404);
        }
        return response()->json($row);
    }

    public function destroy($id)
    {
        $this->validatePermission('elimnar');
        $row = $this->class::find($id);
        if (is_null($row)) {
            return response()->json([$this->msg_field => $this->not_found_msg], 404);
        }
        $row->delete();
        return response()->json([$this->msg_field => 'Eliminado', 'data' => $row]);
    }

    public function validateData($data, $scope)
    {
        $rules = [];
        $field = 'rules'.$scope;
        if (property_exists($this, $field)) {
            $rules = $this->$field;
        }
        return Validator::make($data, $rules);
    }

    protected function validatePermission(string $sub = '')
    {
        $data = ($sub != '') ? $sub.' ' : '';
        if (property_exists($this, 'permission')) {
            if (Auth::check() && Auth::user()->can($sub.$this->permission)) {
                return;
            }
        } else {
            return;
        }
        abort(400, 'No tienes permiso al recurso.');
    }

    public function preparedata($request)
    {
        return $request;
    }
}
