<?php

namespace KairosSystems\CRUD;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CRUDController extends Controller
{
    protected $idField = 'id';
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
    protected $deleteFiles = false;

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
        $this->processSingleFiles($request, $data);
        if ($valid->passes()) {
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
        $valid = $this->validateData($data, 'Update', $id);
        $this->processSingleFiles($request, $data);
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
        if ($this->deleteFiles) {
            $this->deleteFiles($row);
        }
        $row->delete();
        if (method_exists($this, 'postDestroy')) {
            $this->postDestroy($row);
        }
        return response()->json([$this->field => $this->deleted, 'data' => $row]);
    }

    public function validateData($data, $scope, $id = null)
    {
        $rules = [];
        $field = 'rules'.$scope;
        if (property_exists($this, $field)) {
            $rules = $this->$field;
            if ($id !=  null) 
            {
                foreach ($rules as $index => $rule) {
                    if (preg_match('/unique/', $rule)) 
                    {
                        $rules[$index] = $rule.','.$index.','.$id.','.$this->idField;
                    }
                }
            }
        }
        return Validator::make($data, $rules);
    }

    protected function deleteFiles($data)
    {
        if (property_exists($this, 'singleFiles')) {
            $default = ['type' => 'path', 'path' => 'files', 'rule' => 'file'];
            foreach ($this->singleFiles as $field => $config) {
                if (is_string($config)) {
                    $config = ['rule' => $config];
                }
                $config = array_merge($default, $config);
                if ($config['type'] === 'path') {
                    Storage::disk('local')->delete($data->$field);
                }
            }
        }
    }

    protected function processSingleFiles(&$request, &$data)
    {
        if (property_exists($this, 'singleFiles')) {
            $default = ['type' => 'path', 'path' => 'files', 'rule' => 'file'];
            $rules = [];
            $configs = [];
            foreach ($this->singleFiles as $field => $config) {
                if ($request->hasFile($field)) {
                    if (is_string($config)) {
                        $config = ['rule' => $config];
                    }
                    $config = array_merge($default, $config);
                    $rules[$field] = $config['rule'];
                    $configs[$field] = $config;
                }
            }
            $validate = Validator::make($data, $rules);
            if ($validate->passes()) {
                foreach ($configs as $field => $value) {
                    $current = $configs[$field];
                    if ($current['type'] === 'base64') {
                        $data[$field] = base64_encode(file_get_contents($request->$field->path()));
                    } else {
                        $path = $request->$field->store($configs[$field]['path']);
                        $data[$field] = $path;
                    }
                }
            } else {
                $errors = $validate->errors()->all();
                $msg = implode($this->errorSeparator, $errors);
                if ($this->returnArrayError) {
                    abort(400, json_encode([$this->field => $msg, 'errors' => $errors]));
                }
                abort(400, json_encode([$this->field => $msg]));
            }
        }
    }

}
