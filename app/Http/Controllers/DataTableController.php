<?php

namespace App\Http\Controllers;

use App\Services\DataTable\DataTablePermissionMap;
use App\Services\DataTable\DataTableRegistry;
use App\Services\DataTable\DataTableRequestValidator;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataTableController extends Controller
{
    public function __construct(
        private DataTableRegistry $registry,
        private DataTablePermissionMap $permissions,
        private DataTableRequestValidator $validator,
        private DataTableService $service,
    ) {}

    public function handle(string $key, Request $request): JsonResponse
    {
        $definition = $this->registry->get($key);

        if (! $definition) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unknown table.',
            ], 404);
        }

        abort_unless($this->permissions->allows($request->user(), $definition), 403);

        $this->validator->validate($request);

        return $this->service->handle($request, $definition);
    }
}
