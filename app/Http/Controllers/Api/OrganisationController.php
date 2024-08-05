<?php

namespace App\Http\Controllers\Api;

use App\Enums\CoreRoles;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrganisationResource;
use App\Http\Resources\UserResource;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class OrganisationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'isapproved'])->except('index','store');
    }

    public function store(Request $request)
    {
        // if (!$request->user()->isAbleTo('create-organisations'))
        // {
        //     return response()->error('Unauthorized', 403); 
        // }
        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:organisations',
            'acronym' => 'required|string|max:12',
            'contact_person_name' => 'required|string|max:200',
            'contact_person_email' => 'required|email|max:200',
            'category_id' => 'required|exists:organisation_categories,id',
            'approved' => 'nullable|boolean',
            'address' => 'required',
            'description' => 'nullable|max:200',
            'iati_org_type' => 'required'
        ]);

        if ($validator->fails()) {
			
			return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }
        
        $organisation = new Organisation;
        $organisation->name = $request->name;
        $organisation->display_name = $request->name;
        $organisation->acronym = $request->acronym;
        $organisation->category_id = $request->category_id;
        $organisation->contact_person_name = $request->contact_person_name;
        $organisation->contact_person_email = $request->contact_person_email;
        $organisation->address = $request->address ?? 'none';
        $organisation->contact_person_phone = $request->contact_person_phone ?? null;
        $organisation->approved = $request->approved ?? false;
        $organisation->description = $request->description ?? null;
        $organisation->country = $request->country ?? 'SS';
        $organisation->iati_org_id = $request->iati_org_id ?? null;
        $organisation->iati_org_type = $request->iati_org_type;
        $organisation->save();

        return response()->success(new OrganisationResource($organisation));
        
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->isAbleTo('view-organisations'))
        {
            return response()->error('Unauthorized', 403); 
        }

        $organisation = Organisation::find($id);

        if (!$organisation) {
            return response()->error(__('messages.not_found'), 404);
        }

        return response()->success(new OrganisationResource($organisation));
    }

    public function update(Request $request,$id)
    {
        if (!$request->user()->isAbleTo('update-organisations'))
        {
            return response()->error('Unauthorized', 403); 
        }
        $organisation = Organisation::find($id);
        if (!$organisation) 
        {
            return response()->error(__('messages.not_found'), 404);
        }

        $validator = Validator::make($request->all(),[
            'name' => "required|unique:organisations,name,$id",
            'acronym' => 'required|string|max:12',
            'contact_person_name' => 'required|string|max:200',
            'contact_person_email' => 'required|email|max:200',
            'category_id' => 'required|exists:organisation_categories,id',
            'approved' => 'required|boolean',
            'description' => 'nullable|max:200',
            'iati_org_type' => 'required',
        ]);
        
        if ($validator->fails()) {
			
			return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }

        $organisation->name = $request->name;
        $organisation->display_name = $request->name;
        $organisation->acronym = $request->acronym;
        $organisation->category_id = $request->category_id;
        $organisation->contact_person_name = $request->contact_person_name;
        $organisation->contact_person_email = $request->contact_person_email;
        $organisation->address = $request->address;
        $organisation->approved = $request->approved;
        $organisation->description = $request->description ?? null;
        $organisation->country = $request->country ?? 'SS';
        $organisation->iati_org_id = $request->iati_org_id ?? null;
        $organisation->iati_org_type = $request->iati_org_type;
        $organisation->save();

        return response()->success(new OrganisationResource($organisation));

    }


    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAbleTo('delete-organisations'))
        {
            return response()->error('Unauthorized', 403); 
        }
        $organisation = Organisation::find($id);
        if (!$organisation) 
        {
            return response()->error(__('messages.not_found'), 404);
        }

        // if (!$organisation->users->isEmpty())
        // {
        //     return response()->error(__('messages.error_delete'), 400);
        // }
        $organisation->delete();
        return response()->success(['message' => __('messages.success_deleted')]);
    }

    public function index(Request $request)
    {
        //list all for now but enable search and pagination later and permission access
        // if ($request->user()->hasRole(CoreRoles::SuperAdministrator->value))
        // {
        //     return response()->success(OrganisationResource::collection(Organisation::all()));
        // }
        // return response()->success(OrganisationResource::collection(Organisation::where('id', $request->user()->current_organisation_id)));
        // return response()->success(OrganisationResource::collection(
        //     Cache::remember('organisation_listing_controller', 180 * 60, function () {
        //     return Organisation::all();
        //     }) 
        // ));

        return response()->success(OrganisationResource::collection(Organisation::all())); 
        
    }

    public function listOrganisationUsers(Request $request, $id)
    {
        if (!$request->user()->isAbleTo('view-organisations'))
        {
            return response()->error('Unauthorized', 403); 
        }

        $organisation = Organisation::find($id);

        if (!$organisation) {
            return response()->error(__('messages.not_found'), 404);
        }

        if (!$request->user()->hasRole(CoreRoles::SuperAdministrator->value) 
            && ($organisation->id != $request->user()->current_organisation_id)
        ) {
            return response()->error(__('messages.unauthorized'),403);
        }

        return response()->success(UserResource::collection($organisation->users));
    }
}