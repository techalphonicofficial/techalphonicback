<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search)
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']]);

        return response()->json(['data' => $search->search($request->string('q'))]);
    }
}
