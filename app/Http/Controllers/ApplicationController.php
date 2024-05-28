<?php

namespace App\Http\Controllers;

use App\Mail\MessageNotificationMail;
use App\Mail\SubmitApplicationMail;
use App\Models\Application;
use App\Models\ApplicationBusinessProposal;
use App\Models\ApplicationCompanyInfo;
use App\Models\ApplicationDecision;
use App\Models\ApplicationDocument;
use App\Models\ApplicationEligibility;
use App\Models\ApplicationLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function createInitial(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'program_id' => 'required',
                'lots' => 'required',
                'update' => 'nullable',
                'application_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if (count(Application::where(['applicant_id' => $request->user()->id, 'program_id' => $request->program_id])->get()) > 0) {
                $application = Application::where(['applicant_id' => $request->user()->id, 'program_id' => $request->program_id,])->get();
                $application = $application[0];

                DB::table("application_lot")->where("application_id", $application->id)->delete();
            }else{
                if ($request->update == "1") {
                    $application = Application::where("id", $request->application_id)->get();
                    $application = $application[0];

                    DB::table("application_lot")->where("application_id", $application->id)->delete();
                } else {
                    $application = Application::create([
                        'applicant_id' => $request->user()->id,
                        'program_id' => $request->program_id,
                    ]);
                }
            }


            // conditions
            if (count($request->lots) > 2) {
                return response()->json([
                    'status' => false,
                    'message' => "Can't choose more than 4 lots.",
                ], 422);
            } else {
                foreach ($request->lots as $key => $sub) {
                    DB::table('application_lot')->insert([
                        'application_id' => $application->id,
                        'lot_id' => $sub['id'],
                        'choice' => $sub['choice'],
                    ]);
                }
                return response()->json([
                    'status' => true,
                    'data' => [
                        'application' => Application::where('id', $application->id)->get()[0],
                    ],
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function createEligibility_criteria(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {
            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'application_id' => 'required',
                'nigerian_origin' => 'nullable',
                'incorporated_for_profit_clean_tech_company' => 'nullable',
                'years_of_existence' => 'nullable',
                'does_your_company_possess_an_innovative_idea' => 'required',
                'does_your_company_require_assistance_to_upscale' => 'required',
                'to_what_extent_are_your_challenges_financial_in_nature' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $app_el = ApplicationEligibility::create($request->all());

            return response()->json([
                'status' => true,
                'data' => [
                    'application_eligibility' => $app_el,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function updateEligibility_criteria(Request $request, ApplicationEligibility $applicationEligibility)
    {
        if ($request->user()->tokenCan('Applicant')) {
            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'nigerian_origin' => 'nullable',
                'incorporated_for_profit_clean_tech_company' => 'nullable',
                'years_of_existence' => 'nullable',
                'does_your_company_possess_an_innovative_idea' => 'required',
                'does_your_company_require_assistance_to_upscale' => 'required',
                'to_what_extent_are_your_challenges_financial_in_nature' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $applicationEligibility->update($request->all());

            $app_el = ApplicationEligibility::find($applicationEligibility->id);

            return response()->json([
                'status' => true,
                'data' => [
                    'application_eligibility' => $app_el,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => "failed to authenticate"
            ], 404);
        }
    }

    public function createDocument(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'update' => 'nullable',
                'application_id' => 'required',
                'documents' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $application = Application::find($request->application_id);

            if ($application->has('app_document')) {
                // $application->app_document->delete();
                DB::table("application_documents")->where("application_id", $application->id)->delete();
                // ApplicationDocument::where("application_id", "=", $application->id)->delete();
            }

            // if (count($request->documents) > 12) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => "Failed, You cannot upload more than 12 document."
            //     ], 422);
            // } else {
                foreach ($request->documents as $key => $doc) {
                    $docc = ApplicationDocument::create([
                        "application_id" => $request->application_id,
                        "name" => $doc['name'],
                        "url" => $doc['url'],
                    ]);
                }
                return response()->json([
                    'status' => true,
                    'message' => "Successful, Documents are added to application."

                ]);
            // }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadDocument(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'file' => 'required|max:10000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->hasFile("file")) {

                $fileNameWExt = $request->file("file")->getClientOriginalName();
                $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
                $fileExt = $request->file("file")->getClientOriginalExtension();
                $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;
                $request->file("file")->storeAs("public/documentFiles", $fileNameToStore);

                $url = url('/storage/documentFiles/' . $fileNameToStore);

                return response()->json([
                    'status' => true,
                    'message' => "File is successfully uploaded.",
                    'data' => [
                        'url' => $url,
                    ],
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Error! file upload invalid. Try again."
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function createCompanyInfo(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'application_id' => 'required',
                'profile' => 'required',
                'description_of_products' => 'nullable',
                'short_term_objectives' => 'required',
                'medium_term_objectives' => 'required',
                'long_term_objectives' => 'required',
                'number_of_staff' => 'required',
                'organizational_chart' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $app_el = ApplicationCompanyInfo::create($request->all());

            return response()->json([
                'status' => true,
                'data' => [
                    'application_eligibility' => $app_el,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function updateCompanyInfo(Request $request, ApplicationCompanyInfo $applicationCompanyInfo)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'profile' => 'required',
                'description_of_products' => 'nullable',
                'short_term_objectives' => 'required',
                'medium_term_objectives' => 'required',
                'long_term_objectives' => 'required',
                'number_of_staff' => 'required',
                'organizational_chart' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $applicationCompanyInfo->update($request->all());
            $app_co = ApplicationCompanyInfo::find($applicationCompanyInfo->id);

            return response()->json([
                'status' => true,
                'data' => [
                    'application_company_info' => $app_co,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadCompanyInfo(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'file' => 'required|max:10000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->hasFile("file")) {

                $fileNameWExt = $request->file("file")->getClientOriginalName();
                $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
                $fileExt = $request->file("file")->getClientOriginalExtension();
                $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;
                $request->file("file")->storeAs("public/companyFiles", $fileNameToStore);

                $url = url('/storage/companyFiles/' . $fileNameToStore);

                return response()->json([
                    'status' => true,
                    'message' => "File is successfully uploaded.",
                    'data' => [
                        'url' => $url,
                    ],
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Error! file upload invalid. Try again."
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function createBusinessProposal(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'application_id' => 'required',
                'the_critical_need_for_the_technology' => 'required',
                'the_critical_needs_for_the_grant' => 'nullable',
                'carried_out_market_survey' => 'required',
                'survey_doc' => 'nullable',
                'valuable_additions_that_makes_your_technology_stand_out' => 'nullable',
                'consideration_for_direct_and_indirect_carbon_emissions_in_design' => 'required',
                'acquired_authority_of_the_patent_owners' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $app_bp = ApplicationBusinessProposal::create($request->all());

            return response()->json([
                'status' => true,
                'data' => [
                    'application_business_proposal' => $app_bp,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function updateBusinessProposal(Request $request, ApplicationBusinessProposal $applicationBusinessProposal)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'the_critical_need_for_the_technology' => 'required',
                'the_critical_needs_for_the_grant' => 'nullable',
                'carried_out_market_survey' => 'required',
                'survey_doc' => 'nullable',
                'valuable_additions_that_makes_your_technology_stand_out' => 'nullable',
                'consideration_for_direct_and_indirect_carbon_emissions_in_design' => 'required',
                'acquired_authority_of_the_patent_owners' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $applicationBusinessProposal->update($request->all());
            $app_bp = ApplicationBusinessProposal::find($applicationBusinessProposal);

            return response()->json([
                'status' => true,
                'data' => [
                    'application_business_proposal' => $app_bp,
                ],
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadBusinessPro(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'file' => 'required|max:10000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->hasFile("file")) {

                $fileNameWExt = $request->file("file")->getClientOriginalName();
                $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
                $fileExt = $request->file("file")->getClientOriginalExtension();
                $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;
                $request->file("file")->storeAs("public/businessFiles", $fileNameToStore);

                $url = url('/storage/businessFiles/' . $fileNameToStore);

                return response()->json([
                    'status' => true,
                    'message' => "File is successfully uploaded.",
                    'data' => [
                        'url' => $url,
                    ],
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Error! file upload invalid. Try again."
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function submit(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'application_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $application = Application::where('id', $request->application_id)->get();

            $app_docs = ApplicationDocument::where(["application_id" => $request->application_id])->get();

            if (count($app_docs) > 0) {
                Application::where('id', $request->application_id)->update([
                    "status" => "1"
                ]);

                if (!$application[0]->status == 1) {
                    $mailData = [
                        'title' => 'REA - Application update',
                        'li' => [
                            "Dear " . $request->user()->name . ",",
                            '',
                            'Thank you for your interest in the Global Cleantech Innovation Programme.',
                            '',
                            'Your application in response to the Invitation for request for proposal has been successfully submitted.',
                            '',
                            'You have a window to modify your application before the deadline, as shown on the portal.',
                            '',
                            'For further enquiry, kindly drop a message on the platform or send an email to support.gcip@rea.gov.ng.',
                            '',
                            'Thank you!',
                        ],
                    ];

                    Mail::to($request->user()->email)->send(new SubmitApplicationMail($mailData));
                } else {
                    $mailData = [
                        'title' => 'REA - Application update',
                        'body' => "Dear " . $request->user()->name . " with " . $request->user()->email . ", \nYour application for " . $application[0]->program->name . " has been updated, And you still have a window to edit your application before the deadline. \nThank you.",

                    ];

                    Mail::to($request->user()->email)->send(new MessageNotificationMail($mailData));
                }
                $app = Application::find($request->application_id);

                return response()->json([
                    'status' => true,
                    'message' => "Successful, Application submitted.",
                    'data' => [
                        "application" => $app
                    ]
                ]);

                // if (count($app_docs) === 12) {

                // }else{
                //     return response()->json([
                //         'status' => false,
                //         'message' => "Failed, You have to upload exactly 12 files."
                //     ], 422);
                // }

            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'You must upload all "RELEVANT DOCUMENTS" in eligibility requirements to submit'
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function getApplication(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'program_id'=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $app = Application::where(['applicant_id'=> $request->user()->id, 'program_id'=>$request->program_id])->with("lots")->get();
            if (count($app)>0) {
                $app = $app[0];

                $app_eligibility = ApplicationEligibility::where(["application_id"=>$app->id])->get();
                $app_docs = ApplicationDocument::where(["application_id"=>$app->id])->get();
                $app_company_info = ApplicationCompanyInfo::where(["application_id"=>$app->id])->get();
                $app_business = ApplicationBusinessProposal::where(["application_id"=>$app->id])->get();

                $app_decisions = ApplicationDecision::where(["application_id"=>$app->id])->get();

                if (count($app_decisions)>0) {
                    foreach ($app_decisions as $key => $d) {
                        $exp_decision = explode("&", $d->concerns);
                        $d->concerns = $exp_decision;
                    }
                }

                $jvs = $request->user()->jvs;

                $app['application_eligibility'] = count($app_eligibility)>0 ? $app_eligibility[0] : null;
                $app['application_documents'] = $app_docs;
                $app['application_company_info'] = count($app_company_info)>0 ? $app_company_info[0] : null;
                $app['application_decisions'] = count($app_decisions)>0 ? $app_decisions : [];
                $app['application_business_proposal'] = count($app_business)>0 ? $app_business[0] : null;

                $app['jvs'] = $jvs;

                $lots = $app['lots'];

                foreach ($app['lots'] as $l) {

                    $apl = DB::table("application_lot")->where(['application_id'=> $app->id, 'lot_id'=>$l->id])->get()[0];
                    $l->choice = $apl->choice;
                }

                return response()->json([
                    'status' => true,
                    'message' => "Successful.",
                    'data' => [
                        "application"=>$app
                    ]
                ]);

            }else{
                return response()->json([
                    'status' => false,
                    'message' => "No Application found"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function make_decision(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'application_id'=>'required',
                'status'=>'required',
                'concerns'=>'nullable',
                'remark'=>'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            Application::where('id', $request->application_id)->update([
                "status"=>$request->status,
            ]);

            $concerns = "";

            if (isset($request->concerns)) {
                if (count($request->concerns)>0) {
                    foreach ($request->concerns as $key => $c) {
                        $concerns.= $c."&";
                    }
                }
            }

            $add_app_decision = ApplicationDecision::create([
                "application_id" => $request->application_id,
                "status" => $request->status,
                "concerns" => $concerns,
                "remark" => $request->remark,
            ]);

            $app = Application::find($request->application_id);

            if ($request->status == "2") {
                $mailData = [
                    'title' => 'GCIP - Your Application is Queried',
                    'li' => [
                        "Dear ".$app->applicant->name." ",
                        '',
                        "We would like to inform you that there is a query pertaining to your application for the Global Cleantech Innovation Programme. This query arises due to the following reasons:",
                        '',
                        $add_app_decision->remark,
                        '',
                        'To address these queries, we kindly request you to revisit the platform by clicking on the following link',
                        '',
                        'https://applicant.gcip.rea.gov.ng/',
                        '',
                        'Your prompt attention to resolving these queries is greatly appreciated.',
                        '',
                        'Best regards,',
                    ],
                ];
                Mail::to($app->applicant->email)->send(new SubmitApplicationMail($mailData));
            }
            if ($request->status == "3") {
                $mailData = [
                    'title' => 'GCIP - Congratulation, Your Application is Successful.',
                    'li' => [
                        "Dear ".$app->applicant->name." ",
                        '',
                        "We are pleased to inform you that your application for the Prequalification of the Global Cleantech Innovation Programme has been successful! On behalf of our team, we extend our warmest congratulations on this achievement",
                        '',
                        'You will receive notification regarding the subsequent steps in due course.',
                        '',
                        'Warm Regards,',
                    ],
                ];
                Mail::to($app->applicant->email)->send(new SubmitApplicationMail($mailData));
            }
            if ($request->status == "4") {
                $mailData = [
                    'title' => 'GCIP - Your Application is Unsuccessful.',
                    'li' => [
                        "Dear ".$app->applicant->name,
                        '',
                        "We appreciate the time and effort you put into applying for the Prequalification of the Global Cleantech Innovation Programme. After a thorough evaluation of your application, we regret to inform you that we are unable to proceed with your application due to the following reasons:",
                        '',
                        $add_app_decision->remark,
                        '',
                        'Thank you for your understanding and once again, we appreciate your interest.',
                        '',
                        'Warm Regards,',
                    ],
                ];
                Mail::to($app->applicant->email)->send(new SubmitApplicationMail($mailData));
            }

            if ($request->status == "5") {
                $mailData = [
                    'title' => 'GCIP - Your Application is Under Review.',
                    'li' => [
                        "Dear ".$app->applicant->name,
                        '',
                        "We would like to inform you that your application for the Global Cleantech Innovation Programme is currently undergoing review.",
                        '',
                        'You will receive notification regarding the outcome of your application and the subsequent steps in due course.',
                        "",
                        'Best regards,',
                    ],
                ];
                Mail::to($app->applicant->email)->send(new SubmitApplicationMail($mailData));
            }

            return response()->json([
                'status' => true,
                'message' => "You've made a decision successfully. An email has been sent to the applicant, Thank you.",
                'data' => [
                    "application"=>$add_app_decision
                ]
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }
}
