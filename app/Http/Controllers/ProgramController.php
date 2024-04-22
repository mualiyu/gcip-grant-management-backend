<?php

namespace App\Http\Controllers;

use App\Models\ApplicantProjectDocument;
use App\Models\Application;
use App\Models\ApplicationBusinessProposal;
use App\Models\ApplicationCompanyInfo;
use App\Models\ApplicationCv;
use App\Models\ApplicationDecision;
use App\Models\ApplicationDocument;
use App\Models\ApplicationEligibility;
use App\Models\ApplicationFinancialDebtInfo;
use App\Models\ApplicationFinancialInfo;
use App\Models\ApplicationProfile;
use App\Models\ApplicationProject;
use App\Models\Lot;
use App\Models\Program;
use App\Models\ProgramDocument;
use App\Models\ProgramRequirement;
use App\Models\ProgramStage;
use App\Models\ProgramStatus;
use App\Models\Project;
use App\Models\SubLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ProgramController extends Controller
{

    public function create(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'program' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $prog = $request->program;

            $program = Program::create([
                'name' => $prog['programName'],
                'description' => $prog['programDescription'],
            ]);

            // adding Lots and SubLots
            if (array_key_exists('lots', $prog)) {
                $lots = $prog['lots'];
                if (count($lots)>0) {
                    foreach ($lots as $key => $l) {
                        $lot = Lot::create([
                            'name' => $l['name'],
                            'program_id' => $program->id,
                        ]);
                    }
                }
            }

            // Adding Stages
            if (array_key_exists('stages', $prog)) {
                $stages = $prog['stages'];
                if (count($stages)>0) {
                    foreach ($stages as $ke => $st) {
                        $stages = ProgramStage::create([
                            'name' => $st['name'],
                            'start' => $st['startDate'],
                            'end' => $st['endDate'],
                            'description' => $st['description'],
                            'document' => $st['document'],
                            'program_id' => $program->id,
                            'isActive' => '1',
                        ]);
                    }
                }
            }


            // Adding Documents
            if (array_key_exists('uploads', $prog)) {
                $documents = $prog['uploads'];
                if (count($documents)>0) {
                    foreach ($documents as $doc) {
                        $d = ProgramDocument::create([
                            'name' => $doc['name'],
                            'url' => $doc['file'],
                            'type'=>"pdf",
                            'program_id' => $program->id,
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Successfully created Program.....",
                'data' => [
                    'Program' => Program::where('id', '=', $program->id)
                                    ->with('lots')
                                    // ->with('sublots')
                                    ->with('documents')
                                    ->with('stages')->get()[0],
                ],
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function showAll(Request $request)
    {
        // if ($request->user()->tokenCan('Admin')) {

            // $programs = Program::with('lots')
            //                 ->with('sublots')
            //                 ->with('requirements')
            //                 ->with('documents')
            //                 ->with('stages')
            //                 ->with('statuses')->all();
            $programs = Program::all();

            foreach ($programs as $key => $p) {
                # code...
                $stages = ProgramStage::where(['program_id'=>$p->id, 'isActive'=>'1'])->get();

                $num_applicatnt = 0;
                $s = count($stages)>0 ? $stages[0]:'0';
                $p->activeStage = $s;
                $p->noApplicants = $num_applicatnt;
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'programs' => $programs,
                ],
            ]);
        // }else{
        //     return response()->json([
        //         'status' => false,
        //         'message' => trans('auth.failed')
        //     ], 404);
        // }
    }

    public function show(Request $request)
    {
        // if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'programId' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'programs' => Program::where('id', '=', $request->programId)
                                    ->with('lots')
                                    ->with('documents')
                                    ->with('stages')->get()[0],
                ],
            ]);
        // }else{
        //     return response()->json([
        //         'status' => false,
        //         'message' => trans('auth.failed')
        //     ], 404);
        // }
    }

    public function showObj(Request $request)
    {

            $validator = Validator::make($request->all(), [
                'programId' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $prog = Program::where('id', '=', $request->programId)->get()[0];
            $arr  = [
                "program" => [
                    "programName"=>$prog->name,
                    "programDescription"=>$prog->description,
                    "lots"=>array(),
                    "stages"=>[],
                    "uploads"=>[],
                ],
            ];

            foreach ($prog->lots as $key => $l) {
                $al = [
                    "id"=>$l->id,
                    "name"=>$l->name,
                ];

                array_push($arr['program']['lots'],$al);
            }



            foreach ($prog->stages as $key => $s) {
                $ass = [
                    "name"=>$s->name,
                    "startDate"=>$s->start,
                    "endDate"=>$s->end,
                    "description"=>$s->description,
                    "document"=>$s->document,
                ];
                if ($s->id == "2") {
                    if (count($request->user()->projects)>0) {
                        # code...
                        $ass['isAsign'] = 1;
                    }else{
                        $ass['isAsign'] = 0;
                    }
                }
                array_push($arr['program']['stages'],$ass);
            }

            foreach ($prog->documents as $key => $dd) {
                $auu = [
                    "name"=>$dd->name,
                    "file"=>$dd->url,
                ];
                array_push($arr['program']['uploads'],$auu);
            }

            return response()->json([
                'status' => true,
                'data' => $arr,
            ]);

    }

    public function upload(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'file' => 'required|max:9000',
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
                $fileNameToStore = $fileName."_".time().".".$fileExt;
                $request->file("file")->storeAs("public/programFiles", $fileNameToStore);

                $url = url('/storage/programFiles/'.$fileNameToStore);

                return response()->json([
                    'status' => true,
                    'message' => "File is successfully uploaded.",
                    'data' => [
                        'url' => $url,
                    ],
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Error! file upload invalid. Try again."
                ], 404);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function getApplications(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {
            $validator = Validator::make($request->all(), [
                'program_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $sub_applications = Application::where(['program_id'=>$request->program_id, 'status'=>'1'])->with('applicant')->get();
            $q_applications = Application::where(['program_id'=>$request->program_id, 'status'=>'2'])->with('applicant')->get();
            $s_applications = Application::where(['program_id'=>$request->program_id, 'status'=>'3'])->with('applicant')->get();
            $uns_applications = Application::where(['program_id'=>$request->program_id, 'status'=>'4'])->with('applicant')->get();
            $under_review_applications = Application::where(['program_id'=>$request->program_id, 'status'=>'5'])->with('applicant')->get();

            $apps = [
                'submited_applications'=>$sub_applications,
                'queried_applications'=>$q_applications,
                'successful_applications'=>$s_applications,
                'unsuccessful_applications'=>$uns_applications,
                'under_review_applications'=>$under_review_applications
            ];

            return response()->json([
                'status' => true,
                'data' => [
                    'applications' => $apps,
                    'count' => [
                        'submited_applications'=>count($sub_applications),
                        'queried_applications'=>count($q_applications),
                        'successful_applications'=>count($s_applications),
                        'unsuccessful_applications'=>count($uns_applications),
                        'under_review_applications'=>count($under_review_applications)
                    ],
                ],
            ]);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function getSingleApplication(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'program_id'=>'required',
                'applicant_id'=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $app = Application::where(['applicant_id'=> $request->applicant_id, 'program_id'=>$request->program_id])->with("lots")->get();
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

                $app['application_eligibility'] = count($app_eligibility)>0 ? $app_eligibility[0] : [];
                $app['application_documents'] = $app_docs;
                $app['application_company_info'] = count($app_company_info)>0 ? $app_company_info[0] : [0];
                $app['application_business_proposal'] = $app_business;
                $app['application_decisions'] = count($app_decisions)>0 ? $app_decisions : [];

                $app['jvs'] = $jvs;

                foreach ($app['lots'] as $l) {

                    $apl = DB::table("application_lot")->where(['application_id'=> $app->id, 'lot_id'=>$l->id])->get()[0];
                    $l->choice = $apl->choice;
                }

                // this is for the second stage of the tender process
                // $projects = $app->applicant->projects;
                // if (count($projects) > 0) {
                //     $proj = [];
                //     foreach ($projects as $p) {
                //         $pp = Project::where(['id' => $p->id])->with("project_documents")->with("project_requirements")->get()[0];
                //         $pp["applicant_uploaded_documents"] = ApplicantProjectDocument::where(["applicant_id" => $app->applicant->id, "project_id" => $pp->id])->get();
                //         array_push($proj, $pp);
                //     }
                //     $app['projects_allocated'] = $proj;
                // } else {
                //     $app['projects_allocated'] = [];
                // }
                // $app['proposal_id'] = count($app->applicant->proposal)>0 ? $app->applicant->proposal[0]->id : null;

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


}
