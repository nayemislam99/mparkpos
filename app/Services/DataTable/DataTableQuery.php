<?php

namespace App\Services\DataTable;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

interface DataTableQuery
{
    public function build(Request $request): Builder;
}
