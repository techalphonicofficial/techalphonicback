<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\RedirectRequest;
use App\Models\ActivityLog;
use App\Models\Redirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RedirectController extends ContentController
{
    protected string $modelClass = Redirect::class;

    protected string $resource = 'redirects';

    protected string $routeName = 'admin.redirects';

    protected string $viewPath = 'admin.redirects';

    protected string $label = 'Redirect';

    protected string $labelPlural = 'Redirects';

    protected array $searchColumns = ['old_url', 'new_url'];

    protected array $sortable = ['hits', 'last_hit_at'];

    protected array $columns = [
        ['From', 'old_url', 'title'],
        ['To', 'new_url', 'text'],
        ['Code', 'status_code', 'badge'],
        ['Source', 'source', 'badge'],
        ['Hits', 'hits', 'text'],
        ['Active', 'is_active', 'bool'],
    ];

    protected function requestClass(): string
    {
        return RedirectRequest::class;
    }

    protected function authorizePermission(string $action): void
    {
        abort_unless(request()->user()->can('redirects.manage'), 403);
    }

    protected function authorizeItem(string $ability, Model $item): void
    {
        $this->authorizePermission($ability);
    }

    protected function defaults(): array
    {
        return ['status_code' => 301, 'is_active' => true];
    }

    /**
     * CSV import: old_url,new_url[,status_code]. Header row optional. Existing old_url rows are updated.
     */
    public function import(Request $request)
    {
        $this->authorizePermission('create');
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $handle = fopen($request->file('csv')->getRealPath(), 'r');
        $created = $updated = $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($handle, &$created, &$updated, &$skipped, &$errors) {
            $line = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count($row) < 2 || ($line === 1 && str_contains(strtolower($row[0]), 'old'))) {
                    continue;
                }
                [$old, $new] = [trim($row[0]), trim($row[1])];
                $code = in_array((int) ($row[2] ?? 301), [301, 302, 410], true) ? (int) ($row[2] ?? 301) : 301;
                if ($old === '' || ($new === '' && $code !== 410)) {
                    $skipped++;

                    continue;
                }
                $oldPath = Redirect::normalizePath($old);
                if ($oldPath === Redirect::normalizePath($new)) {
                    $errors[] = "Line $line: source and target are identical";
                    $skipped++;

                    continue;
                }
                $r = Redirect::updateOrCreate(['old_url' => $oldPath], ['new_url' => $new ?: null, 'status_code' => $code, 'is_active' => true, 'source' => 'import']);
                $r->wasRecentlyCreated ? $created++ : $updated++;
            }
        });
        fclose($handle);

        ActivityLog::record('redirects_imported', null, 'CSV redirect import', compact('created', 'updated', 'skipped'));

        return back()->with('success', "Import complete: $created created, $updated updated, $skipped skipped.")
            ->with('import_errors', array_slice($errors, 0, 20));
    }

    public function export()
    {
        $this->authorizePermission('view');

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['old_url', 'new_url', 'status_code', 'is_active', 'hits']);
            Redirect::orderBy('old_url')->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [$r->old_url, $r->new_url, $r->status_code, $r->is_active ? 1 : 0, $r->hits]);
                }
            });
            fclose($out);
        }, 'redirects-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
