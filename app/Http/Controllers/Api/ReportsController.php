<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganisationCategoryResource;
use App\Http\Resources\OrganisationResource;
use App\Http\Resources\ProjectResource;
use App\Models\Organisation;
use App\Models\OrganisationCategory;
use App\Models\Project;
use App\Models\ProjectHumanitarianScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum','isapproved'])->only('index');
    }

    public function reportOnFundingtrends(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'currency' => 'nullable|string',
            'number_of_years' => 'nullable|numeric'

        ]); 
        $currency = $request->currency ?? 'USD';
        $no_of_years = $request->number_of_years ?? 6;
        if ($validator->fails()) {
                
            return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }

       $report = DB::table('projects')
            ->join('project_transactions', 'projects.id', '=', 'project_transactions.project_id')
            ->selectRaw('YEAR(transaction_date) as year, sum(value_amount) as data')
            ->where('project_transactions.value_currency', $currency)
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->limit($no_of_years)
            ->get();

        return $report;
        
    }

    public function reportOnFundingBySector(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'currency' => 'nullable|string',
            'number_of_years' => 'nullable|numeric'

        ]); 
        $currency = $request->currency ?? 'USD';
        $no_of_years = $request->number_of_years ?? 6;
        if ($validator->fails()) {
                
            return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }

       $report = DB::table('projects')
            ->join('project_transactions', 'projects.id', '=', 'project_transactions.project_id')
            ->join('project_sectors', 'projects.id', '=', 'project_sectors.project_id')
            ->selectRaw('project_sectors.code as sector, project_sectors.vocabulary as vocabulary, sum(value_amount) as data')
            ->where('project_transactions.value_currency', $currency)
            ->groupBy('sector', 'vocabulary')
            ->orderBy('data', 'desc')
            //->limit($no_of_years)
            ->get();
        $filtered = $report->map(function ($item) {
            return [
                'sector' => $this->getSectorCode($item->vocabulary, $item->sector),
                'data' => $item->data
            ];
        });
        return $filtered;
        
    }

    public function reportOnFundingBySource(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'currency' => 'nullable|string',
            'number_of_years' => 'nullable|numeric'

        ]); 
        $currency = $request->currency ?? 'USD';
        $no_of_years = $request->number_of_years ?? 6;
        if ($validator->fails()) {
                
            return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }

       $report = DB::table('projects')
            ->join('project_transactions', 'projects.id', '=', 'project_transactions.project_id')
            ->join('project_transaction_provider_org', 'project_transactions.id', '=', 'project_transaction_provider_org.project_transaction_id')
            ->selectRaw('project_transaction_provider_org.organisation_id as organisation, sum(value_amount) as data')
            ->where('project_transactions.value_currency', $currency)
            ->groupBy('organisation')
            ->orderBy('data', 'desc')
            //->limit($no_of_years)
            ->get();
        $filtered = $report->map(function ($item) {
            return [
                'organisation' => Organisation::find($item->organisation)->name ?? 'unknown',
                'data' => $item->data
            ];
        });
        return $filtered;
        
    }

    public function reportOnTotalProjectstrends(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'currency' => 'nullable|string',
            'number_of_years' => 'nullable|numeric'

        ]); 
        $no_of_years = $request->number_of_years ?? 6;
        if ($validator->fails()) {
                
            return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }

       $report = DB::table('projects')
            ->join('project_activity_dates', 'projects.id', '=', 'project_activity_dates.project_id')
            ->selectRaw('YEAR(iso_date) as year, count(DISTINCT projects.id) as data')
            ->where('project_activity_dates.type', 1)
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->limit($no_of_years)
            ->get();

        return $report;
        
    }

    public function reportOnTotalFunding(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'currency' => 'nullable|string',
            'year' => 'nullable|numeric'

        ]); 
       
        if ($validator->fails()) {
                
            return response()->error(__('messages.invalid_request'), 422, $validator->messages()->toArray());
        }

        if(!$request->has('year')) {
            
            $result = DB::table('projects')
                ->join('project_transactions', 'projects.id', '=', 'project_transactions.project_id')
                ->where('project_transactions.transaction_type_code', 1)
                ->groupBy('project_transactions.id')
                ->get(['project_transactions.id',DB::raw('sum(project_transactions.value_amount) as value')])
                ->sum('value');
        }
        

        if ($request->has('year')) {

            $year = $request->year;

            $result = DB::table('projects')
                ->join('project_transactions', 'projects.id', '=', 'project_transactions.project_id')
                ->where('project_transactions.transaction_type_code', 1)
                ->whereYear('project_transactions.transaction_date', $year)
                ->groupBy('project_transactions.id')
                ->get(['project_transactions.id',DB::raw('sum(project_transactions.value_amount) as value')])
                ->sum('value');
        }
        

        return $result;
    }

    private function getSectorCode($vocabulary, $code)
    {
        $sectorVocabulary = iati_get_code_value('SectorVocabulary', $vocabulary);
        if ($sectorVocabulary && $sectorVocabulary->code == '2') {
            return iati_get_code_value('SectorCategory', $code)->name ?? 'unknown';
        }

        if ($sectorVocabulary && $sectorVocabulary->code == '7') {
            return iati_get_code_value('UNSDG-Goals', $code)->name ?? 'unknown';
        }

        if ($sectorVocabulary && $sectorVocabulary->code == '8') {
            return iati_get_code_value('UNSDG-Targets', $code)->name ?? 'unknown';
        }
       
       return iati_get_code_value('Sector', $code)->name ?? 'unknown';
    }

 

}