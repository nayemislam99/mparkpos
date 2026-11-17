<?php

namespace App\Http\Controllers;

use App\Services\DataTable\DataTableExportService;
use App\Services\DataTable\DataTablePermissionMap;
use App\Services\DataTable\DataTableRegistry;
use App\Services\DataTable\DataTableRequestValidator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DataTableExportController extends Controller
{
    public function __construct(
        private DataTableRegistry $registry,
        private DataTablePermissionMap $permissions,
        private DataTableRequestValidator $validator,
        private DataTableExportService $exports,
    ) {}

    public function download(Request $request, string $key, string $format, string $language = 'en'): Response
    {
        $definition = $this->registry->get($key);

        abort_if($definition === null, 404, 'Unknown table.');
        abort_unless($this->permissions->allows($request->user(), $definition, 'export'), 403);
        abort_unless(in_array($format, $definition['exports'] ?? ['csv', 'xlsx', 'pdf'], true), 404);
        abort_unless(in_array($language, ['en', 'bn'], true), 404);

        $this->validator->validate($request);

        return $this->exports->download($request, $key, $format, $language, $definition);
    }
}
