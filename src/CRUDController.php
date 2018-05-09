<?php

namespace KairosSystems\CRUD;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Validator;

class CRUDController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}