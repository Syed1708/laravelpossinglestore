<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


class KdsController extends Controller
{
    public function chefIndex()
    {
        return view('admin.kds.chef');
    }

    public function packerIndex()
    {
        return view('admin.kds.packer');
    }

}
