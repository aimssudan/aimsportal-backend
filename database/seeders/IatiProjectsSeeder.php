<?php

namespace Database\Seeders;

use Exception;
use Throwable;
use App\Models\State;
use SimpleXMLElement;
use App\Models\County;
use App\Models\Project;
use App\Models\Organisation;
use Illuminate\Support\Carbon;
use App\Models\ProjectLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ProjectResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IatiProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $xml = $this->readXmlFile();
        $activities = $xml->xpath('/iati-activities/iati-activity');
        
        //disable auditing for initial data import

        foreach ($activities as $activity) {
            $this->saveProject($activity);
        }
        
        Storage::disk('local')->put('ss_projects_imported.txt', 'true');

    }

    private function saveProject(SimpleXMLElement $activity)
    {

        //validation
        $activityAttributes = $activity->attributes();
        $humanitarian = $activityAttributes['humanitarian'] ?? 0;
        $defaultCurrency = $activityAttributes['default-currency'] ?? 'USD';
        $hierachy = $activityAttributes['hierachy'] ?? 0;
        $iati_identifier = $activity->{'iati-identifier'};
        $reportingOrg = $activity->{'reporting-org'};
        $reportingOrgAttributes = $reportingOrg->attributes();
        $iati_org_id = $reportingOrgAttributes['ref'] ?? 'n/a';
        
        //get company
        $organisation = Organisation::where('iati_org_id', $iati_org_id)->first();

        if ($organisation)
        {
            
            $projectTitles = $activity->title;
            $englishTitle = '';
            foreach($projectTitles->narrative as $narrative) {
                if (!$narrative->attributes('xml', true)->lang) {
                    $englishTitle = $narrative;
                }
            }
            $activityStatus = $activity->{'activity-status'};
            $activityStatusAttributes =$activityStatus->attributes();
            $activityStatusCode = $activityStatusAttributes['code'];

            $activityScope = $activity->{'activity-scope'};
            $activityScopeAttributes =$activityScope->attributes();
            $activityScopeCode = $activityScopeAttributes['code'] ?? 4;

            $capitalSpend = $activity->{'capital-spend'};
            $capitalSpendAttributes = $capitalSpend->attributes() ?? [];
            $capitalSpendPercentage = $capitalSpendAttributes['percentage'] ?? 0;

            $conditions = $activity->conditions;
            $conditionsAttributes = $conditions->attributes() ?? [];
            $conditionsAttached = $conditionsAttributes['attached'] ?? 0;

            if (!is_int($conditionsAttached)) {
                $conditionsAttached = 0;
            }

            
            
        DB::beginTransaction();
            // try {
            //     //code...
            // } catch (Throwable $e) {
            //      // Log and continue on error
            //      Log::error("Error inserting project description: ".$e->getMessage());
            //      continue;
            // }
            
            $project = Project::updateOrCreate(
                [
                    'organisation_id' => $organisation->id,
                    'iati_identifier' => $iati_identifier
                ],
                [
                'title' => $englishTitle,
                'status' => 'active',
                'capital_spend_percentage' => $capitalSpendPercentage,
                'conditions_attached' => $conditionsAttached,
                'activity_scope' => $activityScopeCode,
                'activity_status' => $activityStatusCode,
                'humanitarian' => $humanitarian,
                ]
            );

            try {
                foreach($projectTitles->narrative as $narrative) {
                    try {
                        $lang = 'en';
                        $name = $narrative;
                        if ($narrative->attributes('xml', true)->lang) {
                            $lang = $narrative->attributes('xml', true)->lang;
                        }
                        $project->title_translations()->updateOrCreate([
                            'narrative' => $name,
                            'lang' => $lang,
                        ]);
                    } catch (Throwable $e) {
                        // Log and continue on error
                        Log::error("Error inserting project title narative: ".$e->getMessage());
                        continue;
                    }
                    
                }
                
                foreach ($activity->description as $description) {
                    try {
                        $project_description = $project->project_descriptions()->create([
                            'type' => $description->attributes()['type'] ?? 1,
                        ]);
                        foreach ($description->narrative as $narrative) {
                            $lang = 'en';
                            $name = $narrative;
                            if ($narrative->attributes('xml', true)->lang) {
                                $lang = $narrative->attributes('xml', true)->lang;
                            }
                            $project_description->narratives()->updateOrCreate([
                                'narrative' => $name,
                                'lang' => $lang,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting project description: ".$e->getMessage());
                         continue;
                    }
                }
                
                foreach ($activity->{'activity-date'} as $activityDate) {
                    try {
                        $projectActivityDate = $project->activity_dates()->updateOrCreate([
                            'type' => $activityDate->attributes()['type'],
                        ],[
                            'iso_date' => $activityDate->attributes()['iso-date']
                        ]);
                        foreach ($activityDate->narrative as $narrative) {
                            $lang = 'en';
                            $name = $narrative;
                            if ($narrative->attributes('xml', true)->lang) {
                                $lang = $narrative->attributes('xml', true)->lang;
                            }
                            $projectActivityDate->narratives()->updateOrCreate([
                                'narrative' => $name,
                                'lang' => $lang,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting activity date: ".$e->getMessage());
                         continue;
                    }
                    
                }
                
                foreach ($activity->sector as $sector) {

                    try {
                        $project_sector = $project->sectors()->updateOrCreate([
                            'code' => $sector->attributes()['code'],
                            'vocabulary' => $sector->attributes()['vocabulary'] ?? 1,
                        ],[
                            'percentage' => $sector->attributes()['percentage'] ?? 100,
                            'vocabulary_uri' => $sector->attributes()['vocabulary-uri'] ?? null,
                        ]);
                        foreach ($sector->narrative as $narrative) {
                            $lang = 'en';
                            $name = $narrative;
                            if ($narrative->attributes('xml', true)->lang) {
                                $lang = $narrative->attributes('xml', true)->lang;
                            }
                            $project_sector->narratives()->updateOrCreate([
                                'narrative' => $name,
                                'lang' => $lang,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting sector: ".$e->getMessage());
                         continue;
                    }
                    
                    
                    
                }

               

                foreach($activity->{'recipient-country'} as $country) {

                    try {
                        $recipient_country = $project->recipient_countries()->updateOrCreate(
                            [
                            'code' => $country->attributes()['code'] ?? 'SS',
                            ],
                            [
                            'percentage' => $country->attributes()['percentage'] ?? 100
                            ]
                        );
    
                        foreach ($country->narrative as $narrative) {
                            $lang = 'en';
                            $name = $narrative;
                            if ($narrative->attributes('xml', true)->lang) {
                                $lang = $narrative->attributes('xml', true)->lang;
                            }
                            $recipient_country->narratives()->updateOrCreate([
                                'narrative' => $name,
                                'lang' => $lang,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting recipient country: ".$e->getMessage());
                         continue;
                    }
                    
                    
                }

                foreach ($activity->{'recipient-region'} as $region) {
                    try {
                        $recipient_region = $project->recipient_regions()->updateOrCreate(
                            [
                            'code' => $region->attributes()['code'],
                            'vocabulary' => $region->attributes()['vocabulary'] ?? 1
                            ],
                            [
                            'percentage' => $region->attributes()['percentage'] ?? 100,
                            'vocabulary_uri' => $region->attributes()['vocabulary-uri']?? null,
                            ]
                        );
                        foreach ($region->narrative as $narrative) {
                            $lang = 'en';
                            $name = $narrative;
                            if ($narrative->attributes('xml', true)->lang) {
                                $lang = $narrative->attributes('xml', true)->lang;
                            }
                            $recipient_region->narratives()->updateOrCreate([
                                'narrative' => $name,
                                'lang' => $lang,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting recipeint region: ".$e->getMessage());
                         continue;
                    }
                    
                    
                }
                
                foreach($activity->budget as $budget) {
                    //validate and throw error to stop creation
                    try {
                        $value_currency = 'USD';
                        $value_date = Carbon::now()->toDateString();
                        $valueElement = $budget->value;
                        if ($valueElement->attributes()) {
                            $value_currency = $valueElement->attributes()['currency'] ?? 'USD';
                            $value_date = $valueElement->attributes()['value-date'] ?? Carbon::now()->toDateString();
                        }
                        $project->budgets()->updateOrCreate(
                            [
                            'type' => $budget->attributes()['type'] ?? 1,
                            'status' => $budget->attributes()['status'] ?? 1,
                            'period_start' => $budget->{'period-start'}->attributes()['iso-date'],
                            'period_end' => $budget->{'period-end'}->attributes()['iso-date'],
                            ],
                            [
                            'value_currency' => $value_currency,
                            'value_date' => $value_date,
                            'value_amount' => $budget->value ?? 0
                            ]
                        );
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting project description: ".$e->getMessage());
                         continue;
                    }

                    
                }

                
                foreach($activity->{'participating-org'} as $org) {
                    try {
                        $dbOrg = Organisation::where('iati_org_id', $org->attributes()['ref'])->first();
                        if ($dbOrg)
                        {
                            $project->participating_organisations()->updateOrCreate([
                                'organisation_id' => $dbOrg->getKey(),
                            ],[
                                'type' => $org->attributes()['type'] ?? 90,
                                'role' => $org->attributes()['role'] ?? 4,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting participating Org: ".$e->getMessage());
                         continue;
                    }
                    
                    
                }

                
                foreach ($activity->transaction as $transaction) {

                    try {
                        $transactionType = $transaction->{'transaction-type'};
                        $transactionTypeCode = 1;
                        if ($transactionType->attributes())
                        {
                            $transactionTypeCode = $transactionType->attributes()['code'] ?? 1;
                        }
                        $transactionValueElement = $transaction->value;
                        $valueCurrency = 'USD';
                        $valueDate = Carbon::now()->toDateString();
                        if ($transactionValueElement->attributes())
                        {
                            $valueCurrency = $transactionValueElement->attributes()['currency'] ?? 'USD';
                            $valueDate = $transactionValueElement->attributes()['value-date'] ?? Carbon::now()->toDateString();
                        }
                        $tHumanitarian = $transaction->attributes()['humanitarian'] ?? 0;
                        if (!is_int($tHumanitarian)) {
                            $tHumanitarian = 0;
                        }
                        $thisTransaction = $project->transactions()->updateOrCreate(
                            [
                            "ref" => $transaction->attributes()['ref'] ?? '',
                            "value_amount" => abs((float)$transaction->value),
                            "disbursement_channel_code" => $transaction->{'disbursement-channel'}->attributes()['code'] ?? 1
                            ],
                            [
                            "humanitarian" => $tHumanitarian,
                            "transaction_type_code" => $transactionTypeCode,
                            "transaction_date" => $transaction->{'transaction-date'}->attributes()['iso-date'],
                            "value_currency" => $valueCurrency,
                            "value_date" => $valueDate,
                            "recipient_country_code" => 'SS',
                            //"recipient_region_code" => $transaction['recipient_region_code'],
                            //"recipient_region_vocabulary" => $transaction['recipient_region_vocabulary'],
                            "flow_type_code" => $transaction->{'flow-type'}->attributes()['code'] ?? null,
                            "finance_type_code" => $transaction->{'finance-type'}->attributes()['code'] ?? null,
                            "tied_status_code" => $transaction->{'tied-status'}->attributes()['code'] ?? 1,
                            ]
                        );

                        
                        foreach ($transaction->sector ?? [] as $sector) {
                            $transactionSector = $thisTransaction->sectors()->updateOrCreate(
                                [
                                'vocabulary' => $sector->attributes()['vocabulary'] ?? 1,
                                'code' => $sector->attributes()['code'],
                                ],
                                [
                                'vocabulary_uri' => $sector->attributes()['vocabulary-uri'] ?? null,                            
                                ]
                            );
                            
                        }

                        foreach ($transaction->{'aid-type'} ?? [] as $aidType)
                        {
                            if ($aidType->attributes())
                            {
                                $thisTransaction->aid_types()->updateOrcreate([
                                    'code' => $aidType->attributes()['code'],
                                    'vocabulary' => $aidType->attributes()['vocabulary'] ?? 1
                                ]);
                            }
                        }
                            
                        $providerOrg = $transaction->{'provider-org'};
                        if (!$providerOrg->attributes()) {
                            $providerOrg = $reportingOrg;
                        } 
                        $dbProviderOrg = Organisation::where('iati_org_id', $providerOrg->attributes()['ref'])->first();
                        if (!$dbProviderOrg) {
                            $dbProviderOrg = $organisation;
                        }
                        $thisProviderOrg = $thisTransaction->provider_org()->updateOrCreate([
                            'organisation_id' => $dbProviderOrg->getKey(),
                        ],[
                            'type' => $providerOrg->attributes()['type'],
                            'ref' => $providerOrg->attributes()['ref'] ?? null,
                            'provider_activity_id' => $providerOrg->attributes()['provider-activity-id'] ?? null
                        ]);

                        $receiverOrg = $transaction->{'receiver-org'};
                        if (!$receiverOrg->attributes()) {
                            $receiverOrg = $reportingOrg;
                        } 
                        $dbReceiverOrg = Organisation::where('iati_org_id', $receiverOrg->attributes()['ref'])->first();
                        if (!$dbReceiverOrg) {
                            $dbReceiverOrg = $organisation;
                        }
                        $thisReceiverOrg = $thisTransaction->receiver_org()->updateOrCreate([
                            'organisation_id' => $dbReceiverOrg->getKey()
                        ],[
                            'type' => $receiverOrg->attributes()['type'],
                            'ref' => $receiverOrg->attributes()['ref'] ?? null,
                            'receiver_activity_id' => $receiverOrg->attributes()['receiver-activity-id'] ?? null
                        ]);
                        

                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting Recieving Orgs, currencies or something from that blackbox: ".$e->getMessage());
                         continue;
                    }
                    
                    
                }

                foreach ($activity->{'policy-marker'} as $policyMarker) {

                    try {
                        $projectPolicyMarker = $project->policy_markers()->updateOrCreate(
                            [
                                'code' => $policyMarker->attributes()['code'] ?? 0,
                                'vocabulary' => $policyMarker->attributes()['vocabulary'] ?? 1
                            ],
                            [
                                'significance' => $policyMarker->attributes()['significance'] ?? 0
                            ]
    
                        );
                        foreach ($policyMarker->narrative as $narrative) {
                            $lang = 'en';
                            $name = $narrative;
                            if ($narrative->attributes('xml', true)->lang) {
                                $lang = $narrative->attributes('xml', true)->lang;
                            }
                            $projectPolicyMarker->narratives()->updateOrCreate([
                                'narrative' => $name,
                                'lang' => $lang,
                            ]);
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting policy maker: ".$e->getMessage());
                         continue;
                    }
                    
                }


                
                foreach($activity->location as $location) {

                    try {
                        $name = $location->name->narrative ??  $location->description->narrative ?? null;
                        if ($name) 
                        {
                            
                            //state first
                            $locationState = State::where('name', 'like', '%' . $name . '%')->first();
                            $locationCounty = County::where('name', 'like', '%' . $name . '%')->first();
    
                            if ($locationCounty)
                            {
                               
                                ProjectLocation::updateOrCreate(
                                    [
                                        'project_id' => $project->getKey(),
                                        'ref' => "county-id:".$locationCounty->getKey()
                                    ],
                                    [
                                        'state_id' => $locationCounty->state->getKey(),
                                        'county_id' => $locationCounty->getKey(),                                    
                                        
                                    ]
                                );
                                
                            }
                            else if ($locationState)
                            {
                               
                                ProjectLocation::updateOrCreate(
                                    [
                                        'project_id' => $project->getKey(),
                                        'ref' => "state-id:".$locationState->getKey()
                                    ],
                                    [
                                        'state_id' => $locationState->getKey(),
                                        
                                    ]
                                );
                            } 
                        }
                    } catch (Throwable $e) {
                         // Log and continue on error
                         Log::error("Error inserting location: ".$e->getMessage());
                         continue;
                    }


                    
                }
                
                DB::commit();
                //dd(new ProjectResource($project)); 
            } catch(Throwable $e) { 
                DB::rollBack();
                Log::error("Transaction failed: ".$e->getMessage());
                $this->command->info("ERROR : REPORTINGORGID: ".$organisation->iati_org_id." ".$e->getMessage());
                //return response()->error(" ".$e->getMessage(), 500);
            }
        }
        
    }

    private function validateXml($xml) : bool
    {
        dd(count($xml));
        return false;
    }

    private function readXmlFile()
    {
        if (file_exists(__DIR__.'/ss_projects.xml')) {
            $xml = simplexml_load_file(__DIR__.'/ss_projects.xml');
            return $xml;
        } else {
            throw  new Exception("xml file not found");
        }
    }
}
