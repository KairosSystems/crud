<?php

namespace KairosSystems\CRUD;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CRUDController extends Controller
{
    protected $field = 'message';
    protected $notFound = 'No se encuentra el registro.';
    protected $deleted = 'Eliminado con éxito.';
    protected $updated = 'Actualizado con éxito.';
    protected $invalidRequest = 'Petición inválida';
    protected $errorSeparator = "\n";
    protected $returnArrayError = false;
    protected $class = '';
    protected $paginate = false;
    protected $perPage = 25;

    public function index()
    {
        if (method_exists($this, 'validate')) {
            $this->validate(__METHOD__, __FUNCTION__);
        }
        $q = new $this->class;
        $data = [];
        $fields = ['*'];
        if (method_exists($this, 'preIndex')) {
            $this->preIndex($q);
        }
        if (property_exists($this, 'showables')) {
            $fields = $this->showables;
        }
        if ($this->paginate) {
            $data = $q->paginate($this->perPage, $fields);
        } else {
            $data = ($q)->get($fields);
        }
        if (method_exists($this, 'postIndex')) {
            $this->postIndex($data);
        }
        return response()->json($data);
    }

    public function store(Request $request)
    {
        if (method_exists($this, 'validate')) {
            $this->validate(__METHOD__, __FUNCTION__);
        }
        if (method_exists($this, 'preStore')) {
            $this->preStore($request);
        }
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
            return response()->json([$this->field => 'Creado', 'data' => $row ]);
        } else {
            $errors = $valid->errors()->all();
            $msg = implode($this->errorSeparator, $errors);
            if ($this->returnArrayError) {
                return response()->json([$this->field => $msg, 'errors' => $errors], 400);
            }
            return response()->json([$this->field => $msg], 400);
        }
    }

    public function update(Request $request, $id)
    {
        if (method_exists($this, 'validate')) {
            $this->validate(__METHOD__, __FUNCTION__);
        }

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
                return response()->json([$this->field => $this->notFound], 404);
            }
            $row->update($data);
            return response()->json([$this->field => $this->updated, 'data' => $row]);
        } else {
            $errors = $valid->errors()->all();
            $msg = implode($this->errorSeparator, $errors);
            if ($this->returnArrayError) {
                return response()->json([$this->field => $msg, 'errors' => $errors], 400);
            }
            return response()->json([$this->field => $msg], 400);
        }
    }

    public function show($id)
    {
        if (method_exists($this, 'validate')) {
            $this->validate(__METHOD__, __FUNCTION__);
        }
        $row = $this->class::find($id);
        if (is_null($row)) {
            return response()->json([$this->field => $this->notFound], 404);
        }
        if (method_exists($this, 'postShow')) {
            $this->postShow($row);
        }
        return response()->json($row);
    }

    public function destroy($id)
    {
        if (method_exists($this, 'validate')) {
            $this->validate(__METHOD__, __FUNCTION__);
        }
        $row = $this->class::find($id);
        if (is_null($row)) {
            return response()->json([$this->field => $this->notFound], 404);
        }
        if (method_exists($this, 'preDestroy')) {
            $this->preDestroy($row);
        }
        $row->delete();
        if (method_exists($this, 'postDestroy')) {
            $this->postDestroy($row);
        }
        return response()->json([$this->field => $this->deleted, 'data' => $row]);
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

}
