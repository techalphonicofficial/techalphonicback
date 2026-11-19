<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request, LeadService $leads)
    {
        $lead = $leads->capture($request->validated(), $request);

        return response()->json(['ok' => true, 'id' => $lead->id, 'message' => setting('lead_success_message')], 201);
    }

    /** Private: requires a Sanctum token for a user with leads.view. */
    public function index(Request $request)
    {
        abort_unless($request->user()->can('leads.view') && $request->user()->tokenCan('leads:read'), 403);

        return LeadResource::collection(Lead::latest()->paginate(50));
    }
}
