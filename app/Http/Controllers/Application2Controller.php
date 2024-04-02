<?php

namespace App\Http\Controllers;

use App\Mail\MessageNotificationMail;
use App\Mail\SubmitApplicationMail;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationCurrentPosition;
use App\Models\ApplicationCv;
use App\Models\ApplicationDecision;
use App\Models\ApplicationDocument;
use App\Models\ApplicationEducation;
use App\Models\ApplicationEmployer;
use App\Models\ApplicationFinancialDebtInfo;
use App\Models\ApplicationFinancialDebtInfoBorrower;
use App\Models\ApplicationFinancialInfo;
use App\Models\ApplicationMembership;
use App\Models\ApplicationProfile;
use App\Models\ApplicationProject;
use App\Models\ApplicationProjectReferee;
use App\Models\ApplicationProjectSubContractor;
use App\Models\ApplicationSubLot;
use App\Models\ApplicationTraining;
use App\Models\ContactPerson;
use App\Models\ProgramStage;
use App\Models\ShareHolder;
use App\Models\SubLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

// zip
use Illuminate\Support\Facades\File;
use ZipArchive;

class ApplicationController extends Controller
{
    public function createInitial(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            return response()->json([
                'status' => false,
                'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            ], 422);

            $validator = Validator::make($request->all(), [
                'program_id' => 'required',
                'sublots'=> 'required',
                'update' => 'nullable',
                'application_id' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }


            if ($request->update == "1") {
                $application = Application::where("id", $request->application_id)->get();
                $application = $application[0];

                DB::table("application_sub_lot")->where("application_id", $application->id)->delete();
            }else{
                $application = Application::create([
                    'applicant_id'=>$request->user()->id,
                    'program_id'=>$request->program_id,
                ]);
            }

            // conditions
            if (count($request->sublots)>4) {
                return response()->json([
                    'status' => false,
                    'message' => "Can't choose more than 4 sublots.",
                ], 422);
            }else {
                $lots_id = [];
                $sublots_id = [];
                foreach ($request->sublots as $key => $s) {
                    array_push($sublots_id, $s['id']);
                    $sub = SubLot::find($s['id']);
                    if ($sub) {
                        array_push($lots_id, $sub->lot_id);
                    }
                }

                foreach ($lots_id as $l) {
                    if (count(array_keys($lots_id, $l)) > 2) {
                        return response()->json([
                            'status' => false,
                            'message' => "You are not allowed to choose more than two sublots per lot.",
                        ], 422);
                    }
                }


                foreach ($request->sublots as $key => $sub) {
                    DB::table('application_sub_lot')->insert([
                        'application_id'=>$application->id,
                        'sub_lot_id'=>$sub['id'],
                        'choice'=>$sub['choice'],
                    ]);
                }
                return response()->json([
                    'status' => true,
                    'data' => [
                        'application' => Application::where('id', $application->id)->get()[0],
                    ],
                ]);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function createProfile(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {
            return response()->json([
                'status' => false,
                'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            ], 422);

            $validator = Validator::make($request->all(), [
                'update'=>'nullable',
                'application_id'=>'required',
                'applicant_name' => 'required',
                'date_of_incorporation'=> 'required',
                // 'brief_description'=> 'nullable',
                // 'website'=> 'nullable',
                'share_holders'=> 'nullable',
                'ultimate_owner'=> 'nullable',
                'contact_person'=> 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->update == "1") {
                $applicationP = ApplicationProfile::where("application_id", $request->application_id)->get();
                $applicationP = $applicationP[0];

                ApplicationProfile::where("id", $applicationP->id)->update([
                    'name' => $request->applicant_name,
                    'registration_date' => $request->date_of_incorporation,
                    // 'description' => $request->brief_description,
                    // 'website' => $request->website,
                    'cac_number'=>$request->user()->rc_number,
                    'address'=>$request->user()->address,
                    'owner'=>$request->ultimate_owner,
                ]);

                ContactPerson::where("app_prof_id", $applicationP->id)->delete();
                ShareHolder::where("app_prof_id", $applicationP->id)->delete();
            }else{
                $applicationP = ApplicationProfile::create([
                    'applicant_id' => $request->user()->id,
                    'application_id' => $request->application_id,
                    'name' => $request->applicant_name,
                    'registration_date' => $request->date_of_incorporation,
                    // 'description' => $request->brief_description,
                    // 'website' => $request->website,
                    'cac_number'=>$request->user()->rc_number,
                    'address'=>$request->user()->address,
                    'owner'=>$request->ultimate_owner,
                ]);
            }

            if (count($request->contact_person) > 0) {

                foreach ($request->contact_person as $key => $cp) {
                    $contact = ContactPerson::create([
                        "app_prof_id"=>$applicationP->id,
                        "name"=>$cp['name'],
                        "phone"=>$cp['phone'],
                        "email"=>$cp['email'],
                        "address"=>$cp['address'],
                        "designation"=>$cp["designation"]
                    ]);
                }
            }

            if (count($request->share_holders) > 0) {
                # code...
                foreach ($request->share_holders as $key => $sh) {
                    $contact = ShareHolder::create([
                        "app_prof_id"=>$applicationP->id,
                        "name"=>$sh['name'],
                        "phone"=>$sh['phone'],
                    ]);
                }
            }

            $appP = ApplicationProfile::where('id', $applicationP->id)->with('share_holders')->with('contact_persons')->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'application_profile' => $appP,
                ],
            ]);


        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function createProfileUpdate(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {
            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'application_id'=>'required',
                'application_profile_id'=>'required',
                'brief_description'=> 'nullable',
                'website'=> 'nullable',
                'evidence_of_equipment_ownership'=> 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $applicationP = ApplicationProfile::where('id', $request->application_profile_id)->update([
                'description' => $request->brief_description,
                'website' => $request->website,
                'evidence_of_equipment_ownership' => $request->evidence_of_equipment_ownership,
            ]);

            $appP = ApplicationProfile::where('id', $request->application_profile_id)->with('share_holders')->with('contact_persons')->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'application_profile' => $appP,
                ],
            ]);


        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function createStaff(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'update'=>'nullable',
                'application_id'=>'required',
                'staff' => 'nullable',
                // 'choice' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if (count($request->staff) > 0) {

                if ($request->update == "1") {
                    $staff = ApplicationCv::where('application_id', $request->application_id)->get();
                    if (count($staff)>0) {
                        foreach ($staff as $s) {
                            ApplicationEmployer::where("application_cv_id", $s->id)->delete();
                            // ApplicationEducation::where("application_cv_id", $s->id)->delete();
                            ApplicationMembership::where("application_cv_id", $s->id)->delete();
                            ApplicationCurrentPosition::where("application_cv_id", $s->id)->delete();
                        }
                        ApplicationCv::where('application_id', $request->application_id)->delete();
                    }
                }

                foreach ($request->staff as $key => $staff) {
                    $staff_create = ApplicationCv::create([
                        'application_id'=>$request->application_id,
                        'name'=>$staff['name'],
                        // 'dob'=>$staff['dob'],
                        'language'=>$staff['language'],
                        'coren_license_number'=>$staff['coren_license_number'],
                        'coren_license_document'=>$staff['coren_license_document'],
                        // 'countries_experience'=>$staff['countries_experience'],
                        // 'work_undertaken'=>$staff['work_undertaken'],
                        'education_certificate'=>$staff['education_certificate'],
                        'professional_certificate'=>$staff['professional_certificate'],
                        'cv'=>$staff['cv'],
                        'gender'=>$staff['gender'],
                    ]);
                    // Employer
                    if (count($staff['employer'])>0) {
                        foreach ($staff['employer'] as $emp) {
                            ApplicationEmployer::create([
                                'application_cv_id'=>$staff_create->id,
                                'name'=>$emp['name'],
                                'position'=>$emp['position'],
                                'start'=>$emp['start_date'],
                                'end'=>$emp['end_date'],
                                'description'=>$emp['description'],
                            ]);
                        }
                    }

                    // Education
                    // if (count($staff['education'])>0) {
                    //     foreach ($staff['education'] as $edu) {
                    //         ApplicationEducation::create([
                    //             'application_cv_id'=>$staff_create->id,
                    //             'qualification'=>$edu['qualification'],
                    //             'course'=>$edu['course'],
                    //             'school'=> $edu['school'],
                    //             'start'=>$edu['start_date'],
                    //             'end'=>$edu['end_date'],
                    //         ]);
                    //     }
                    // }

                    // Curent position
                    if ($staff['current_position']) {
                        // foreach ($staff['current_position'] as $edu) {
                            $cp = $staff['current_position'];

                            ApplicationCurrentPosition::create([
                                'application_cv_id'=>$staff_create->id,
                                'position'=>$cp['position'],
                                'start'=>$cp['start_date'],
                                'description'=>$cp['description'],
                            ]);
                        // }
                    }

                    // membership
                    // if (count($staff['membership'])>0) {
                    //     foreach ($staff['membership'] as $mem) {
                    //         ApplicationMembership::create([
                    //             'application_cv_id'=>$staff_create->id,
                    //             'rank'=>$mem['rank'],
                    //             'state'=>$mem['state'],
                    //             'date'=>$mem['date'],
                    //         ]);
                    //     }
                    // }

                    // training
                    // if (count($staff['training'])>0) {
                    //     foreach ($staff['training'] as $tr) {
                    //         ApplicationTraining::create([
                    //             'application_cv_id'=>$staff_create->id,
                    //             'course'=>$tr['course'],
                    //             'date'=>$tr['date'],
                    //         ]);
                    //     }
                    // }

                }

                return response()->json([
                    'status' => true,
                    'message' => "Successful, Staff's are added to system."
                    // 'data' => [
                    //     'application_profile' => $staff_create,
                    // ],
                ]);

            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed due to no staff added. try again!"
                ], 422);
            }



        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function createProject(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'update'=>'nullable',
                'application_id'=>'required',
                'projects' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if (count($request->projects) > 0) {

                if ($request->update == "1") {
                    $projects = ApplicationProject::where('application_id', $request->application_id)->get();
                    if (count($projects)>0) {
                        foreach ($projects as $p) {
                            ApplicationProjectReferee::where("application_project_id", $p->id)->delete();
                            ApplicationProjectSubContractor::where("application_project_id", $p->id)->delete();
                        }
                        ApplicationProject::where('application_id', $request->application_id)->delete();
                    }
                }

                foreach ($request->projects as $key => $project) {
                    $project_create = ApplicationProject::create([
                        'application_id'=>$request->application_id,
                        'name'=>$project['name'],
                        'address'=>$project['address'],
                        'date_of_contract'=>$project['date_of_contract'],
                        'employer'=>$project['employer'],
                        'location'=>$project['location'],
                        'description'=>$project['description'],
                        'date_of_completion'=>$project['date_of_completion'],
                        'project_cost'=>$project['project_cost'],
                        'role_of_applicant'=>$project['role_of_applicant'],
                        // 'equity'=>$project['equity'],
                        // 'implemented'=>$project['implemented'],
                        'geocoordinate'=>$project['geocoordinate'],
                        'subcontactor_role'=>$project['subcontractor_role'],
                        'award_letter'=>$project['award_letter'],
                        'interim_valuation_cert'=>$project['interim_valuation_cert'],
                        'certificate_of_completion'=>$project['certificate_of_completion'],
                        'evidence_of_completion'=>$project['evidence_of_completion'],
                    ]);

                    // Referees
                    if (count($project['referee'])>0) {
                        foreach ($project['referee'] as $ref) {
                            ApplicationProjectReferee::create([
                                'application_project_id'=>$project_create->id,
                                'name'=>$ref['name'],
                                'phone'=>$ref['phone'],
                            ]);
                        }
                    }

                    // subcontractor
                    if (count($project['subcontractor'])>0) {
                        foreach ($project['subcontractor'] as $sub) {
                            ApplicationProjectSubContractor::create([
                                'application_project_id'=>$project_create->id,
                                'name'=>$sub['name'],
                                'address'=>$sub['address'],
                            ]);
                        }
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => "Successful, project's are added to the application."

                ]);

            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed, Due to no projects were added. try again!"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function createFinancial(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            // return response()->json([
            //     'status' => false,
            //     'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            // ], 422);

            $validator = Validator::make($request->all(), [
                'update'=>'nullable',
                'application_id'=>'required',
                'financial_info' => 'required',
                'financial_dept_info' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->update == "1") {
                ApplicationFinancialInfo::where("application_id", $request->application_id)->delete();
                ApplicationFinancialDebtInfo::where("application_id", $request->application_id)->delete();
            }

                ApplicationFinancialInfo::where("application_id", "=", $request->application_id)->delete();
                ApplicationFinancialDebtInfo::where("application_id",  "=", $request->application_id)->delete();


            if (isset($request->financial_info['fy1'])) {

                $fy1 = $request->financial_info['fy1'];

                $fy1['application_id'] = $request->application_id;
                $fy1['type'] = "fy1";

                $output = array_map(function($item) { return $item ?: 0; }, $fy1);

                ApplicationFinancialInfo::create($output);
            }

            if (isset($request->financial_info['fy2'])) {

                $fy2 = $request->financial_info['fy2'];

                $fy2['application_id'] = $request->application_id;
                $fy2['type'] = "fy2";

                $output = array_map(function($item) { return $item ?: 0; }, $fy2);

                ApplicationFinancialInfo::create($output);
            }

            if (isset($request->financial_info['fy3'])) {

                $fy3 = $request->financial_info['fy3'];
                $fy3['application_id'] = $request->application_id;
                $fy3['type'] = "fy3";

                $output = array_map(function($item) { return $item ?: 0; }, $fy3);

                ApplicationFinancialInfo::create($output);
            }
            // $dept = $request->financial_dept_info;

            if (isset($request->financial_dept_info)) {
                if (count($request->financial_dept_info) > 0) {
                    foreach ($request->financial_dept_info as $dept) {
                        $dept_create = ApplicationFinancialDebtInfo::create([
                            'application_id'=>$request->application_id,
                            'project_name'=> $dept['project_name'],
                            'location'=> $dept['location'],
                            'sector'=> $dept['sector'],
                            'aggregate_amount'=> $dept['aggregate_amount'],
                            'date_of_financial_close'=> $dept['date_of_financial_close'],
                            // 'date_of_first_drawdown'=> $dept['date_of_first_drawdown'],
                            // 'date_of_final_drawdown'=> $dept['date_of_final_drawdown'],
                            // 'tenor_of_financing'=> $dept['tenor_of_financing'],
                            'evidence_of_support'=> $dept['evidence_of_support'],
                        ]);

                        $borrower = ApplicationFinancialDebtInfoBorrower::create([
                            'application_financial_debt_id'=>$dept_create->id,
                            'name'=> $dept['borrower']['name'],
                            // 'rc_number'=> $dept['borrower']['rc_number'],
                            'address'=> $dept['borrower']['address'],
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Successful, Financial info are added to the application."

            ]);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
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
                'update'=>'nullable',
                'application_id'=>'required',
                'documents' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $application = Application::find($request->application_id);

            if (count($application->app_document)>0) {
                // $application->app_document->delete();
                DB::table("application_documents")->where("application_id", $application->id)->delete();
                // ApplicationDocument::where("application_id", "=", $application->id)->delete();
            }

            // $dd = ApplicationDocument::where("application_id", $request->application_id)->get();
            // if (count($dd)>0) {
            //     ApplicationDocument::where("application_id", $request->application_id)->delete();
            // }
            // if ($request->update == "1") {
            // }

            if(count($request->documents)>12){
                return response()->json([
                    'status' => false,
                    'message' => "Failed, You cannot upload more than 12 document."
                ], 422);
            }else{
                foreach ($request->documents as $key => $doc) {
                    $docc = ApplicationDocument::create([
                        "application_id"=>$request->application_id,
                        "name"=>$doc['name'],
                        "url"=>$doc['url'],
                    ]);
                }
                return response()->json([
                    'status' => true,
                    'message' => "Successful, Documents are added to application."

                ]);
            }


        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function uploadStaff(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

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
                $request->file("file")->storeAs("public/staffFiles", $fileNameToStore);

                $url = url('/storage/staffFiles/'.$fileNameToStore);

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
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadProject(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

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
                $request->file("file")->storeAs("public/projectFiles", $fileNameToStore);

                $url = url('/storage/projectFiles/'.$fileNameToStore);

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
                ], 422);
            }

        }else{
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
                $request->file("file")->storeAs("public/documentFiles", $fileNameToStore);

                $url = url('/storage/documentFiles/'.$fileNameToStore);

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
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadFinancial(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

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
                $request->file("file")->storeAs("public/financialFiles", $fileNameToStore);

                $url = url('/storage/financialFiles/'.$fileNameToStore);

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
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadProfile(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

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
                $request->file("file")->storeAs("public/profileFiles", $fileNameToStore);

                $url = url('/storage/profileFiles/'.$fileNameToStore);

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
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function submit(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            return response()->json([
                'status' => false,
                'message' => "Sorry, you are unable to start a new application or edit an existing one because applications have been closed.",
            ], 422);

            $validator = Validator::make($request->all(), [
                'application_id'=>'required',
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
                if (count($app_docs) < 12) {
                    return response()->json([
                        'status' => false,
                        'message' => 'You must upload all "RELEVANT DOCUMENTS" in eligibility requirements to submit.'
                    ], 422);
                } else {
                    $sublots = DB::table('application_sub_lot')->where('application_id', $application[0]->id)->get();
                    if (count($sublots)>4) {
                        return response()->json([
                            'status' => false,
                            'message' => 'You MUST select ONLY Four (4) Sub-Lots, (2) sub-Lots under each Lot category before you can submit.'
                        ], 422);
                    }else {

                        Application::where('id', $request->application_id)->update([
                            "status"=>"1"
                        ]);

                        if (!$application[0]->status == 1) {
                            $mailData = [
                                'title' => 'REA - Application update',
                                'li' => [
                                    "Dear ".$request->user()->name.",",
                                    '',
                                    'Thank you for your interest in the GEF-UNDP-REA Africa Minigrids Program (AMP).',
                                    '',
                                    'Your application in response to the Invitation for Prequalification has been successfully submitted.',
                                    '',
                                    'You have a window to modify your application before the deadline, as shown on the portal.',
                                    '',
                                    'Your application will be opened in a hybrid physical-virtual ceremony at 1.00pm (WAT) on Thursday 24th August 2023 (link available below). We kindly request your organisation to send its authorised representative to attend the ceremony virtually. Alternatively, the organisation may send its contact person or someone whose details are included in the submitted application.',
                                    '',
                                    'Join Zoom Meeting',
                                    'https://rea-gov-ng.zoom.us/j/89996450329?pwd=elR2WURybFhjTGY5OGp0bFFWMGtIQT09',
                                    '',
                                    'Meeting ID: 899 9645 0329',
                                    'Passcode: 215694',
                                    '',
                                    'For further enquiry, kindly drop a message on the platform or send an email to amp@rea.gov.ng.',
                                    '',
                                    'Thank you!',
                                ],
                            ];

                            Mail::to($request->user()->email)->send(new SubmitApplicationMail($mailData));
                        }else{
                            $mailData = [
                                'title' => 'REA - Application update',
                                'body' => "Dear ".$request->user()->name." with ".$request->user()->email.", \nYour application for ".$application[0]->program->name." has been updated, And you still have a window to edit your application before the deadline. \nThank you.",

                            ];

                            Mail::to($request->user()->email)->send(new MessageNotificationMail($mailData));
                        }
                        $app = Application::find($request->application_id);

                        return response()->json([
                            'status' => true,
                            'message' => "Successful,  Application added.",
                            'data' => [
                                "application" => $app
                            ]
                        ]);
                    }
                    // if (count($app_docs) === 12) {

                    // }else{
                    //     return response()->json([
                    //         'status' => false,
                    //         'message' => "Failed, You have to upload exactly 12 files."
                    //     ], 422);
                    // }

                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'You must upload all "RELEVANT DOCUMENTS" in eligibility requirements to submit'
                ], 422);
            }

        }else{
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

            $app = Application::where(['applicant_id'=> $request->user()->id, 'program_id'=>$request->program_id])->with("sublots")->get();
            if (count($app)>0) {
                $app = $app[0];

                $app_profile = ApplicationProfile::where(["application_id"=>$app->id])->with('contact_persons')->with('share_holders')->get();
                $app_staff = ApplicationCv::where(["application_id"=>$app->id])->with('employers')->get();

                $app_projects = ApplicationProject::where(["application_id"=>$app->id])->with('referees')->with('sub_contractors')->get();

                $app_fin = ApplicationFinancialInfo::where(["application_id"=>$app->id])->get();
                $app_fin_dept = ApplicationFinancialDebtInfo::where(["application_id"=>$app->id])->with('borrowers')->get();
                $fin = [
                    "financial_info" => $app_fin,
                    "financial_dept_info" => $app_fin_dept
                ];

                $sublots = DB::table('application_sub_lot')->where('application_id', $app->id)->get();
                if (count($sublots)>0) {
                    $subs = [];

                    foreach ($sublots as $sl) {

                        $s_sublot = SubLot::where('id', $sl->sub_lot_id)->get();
                        if (count($s_sublot)>0) {
                            $s_s = $s_sublot[0];
                            // return $s_s->name;
                            $arr = [
                                "sublot_id"=>$sl->sub_lot_id,
                                "choice"=>$sl->choice,
                                "sublot_name" => $s_s->name,
                                // "sublot_category" => $s_s->category->name,
                                "lot_name" => $s_s->lot->name,
                                "lot_region" => $s_s->lot->region->name,
                            ];

                            array_push($subs, $arr);
                        }
                    }
                }else{
                    $subs = [];
                }

                $app_docs = ApplicationDocument::where(["application_id"=>$app->id])->get();
                $app_decisions = ApplicationDecision::where(["application_id"=>$app->id])->get();

                if (count($app_decisions)>0) {
                    foreach ($app_decisions as $key => $d) {
                        $exp_decision = explode("&", $d->concerns);
                        $d->concerns = $exp_decision;
                    }
                }

                if (count($app_profile)>0) {
                    $app_profile[0]->authorised_personel = $request->user()->person_incharge;
                }

                $jvs = $request->user()->jvs;

                $app['application_profile'] = count($app_profile)>0 ? $app_profile: [];
                $app['application_staff'] = $app_staff;
                $app['application_projects'] = $app_projects;

                $app['application_financials'] = $fin;
                $app['application_documents'] = $app_docs;
                $app['application_decisions'] = count($app_decisions)>0 ? $app_decisions : [];

                $app['application_sublots'] = $subs;

                $app['jvs'] = $jvs;

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


    public function getApplicationProgress(Request $request)
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

            $app = Application::where(['applicant_id'=> $request->user()->id, 'program_id'=>$request->program_id])->with("sublots")->get();
            if (count($app)>0) {
                $app = $app[0];

                $data = [
                    "pre_qualification"=> ['status'=> 0, 'msg'=>''],
                    "lots"=> ['status'=> 0, 'msg'=>''],
                    "sublots"=>['status'=> 0, 'msg'=>''],
                    "eligibility_requirement"=> ['status'=> 0, 'msg'=>''],
                    "technical_requirement"=> ['status'=> 0, 'msg'=>''],
                    "financial_info"=> ['status'=> 0, 'msg'=>''],
                ];

                $app_profile = ApplicationProfile::where(["application_id"=>$app->id])->with('contact_persons')->with('share_holders')->get();
                $app_staff = ApplicationCv::where(["application_id"=>$app->id])->with('employers')->with('current_position')->get();

                $app_projects = ApplicationProject::where(["application_id"=>$app->id])->with('referees')->with('sub_contractors')->get();

                $app_fin = ApplicationFinancialInfo::where(["application_id"=>$app->id])->get();
                $app_fin_dept = ApplicationFinancialDebtInfo::where(["application_id"=>$app->id])->with('borrowers')->get();

                $fin = [
                    "financial_info" => $app_fin,
                    "financial_dept_info" => $app_fin_dept
                ];

                $app_docs = ApplicationDocument::where(["application_id"=>$app->id])->get();

                $sublots = DB::table('application_sub_lot')->where('application_id', $app->id)->get();

                // progress for pre_qualification
                if ($app->pre_qualification_status == 1) {
                    $data['pre_qualification']['status'] = 1;
                    $data['pre_qualification']['msg'] = "Completed";
                }else{
                    $data['pre_qualification']['status'] = 0;
                    $data['pre_qualification']['msg'] = "you have to agree to the PreQualification document.";
                }
                // End of  progress for pre_qualification


                // progress for sub lots
                if (count($sublots)>0) {
                    $data['lots']['status'] = 1;
                    $data['sublots']['status'] = 1;
                    $num = count($sublots);
                    if ($num < 4) {
                        $data['lots']['msg'] = "";
                        $data['sublots']['msg'] = "You have added $num Sub Lots only, you can add up-to 4.";
                    }else{
                        $data['lots']['msg'] = "Completed";
                        $data['sublots']['msg'] = "Completed";
                    }
                }else{
                    $data['lots']['status'] = 0;
                    $data['sublots']['status'] = 0;
                    $data['lots']['msg'] = "You can add not more than 2 Lots & 4 Sublots.";
                    $data['sublots']['msg'] = "You can add not more than 2 Lots & 4 Sublots.";
                }
                // end of progress for sub lots

                // progress for eligibility requirement
                $s_ap = 0;
                if (count($app_profile) > 0) {
                    $app_p = $app_profile[0];
                    if ((!$app_p->name==null) && (!$app_p->registration_date==null) && (count($app_p->contact_persons)>0) && (count($app_p->share_holders)>0)) {
                        $s_ap = 1;
                        $data['eligibility_requirement']['msg'] .= " Your Profile is completed";
                    }else {
                        $s_ap = 0;
                        $data['eligibility_requirement']['msg'] .= " You're still about to commplete the requirements";
                    }
                }else {
                    $s_ap = 0;
                    $data['eligibility_requirement']['msg'] .= " You need to add APPLICANT NAME & DATE OF INCORPORATION/REGISTRATION";
                }
                $s_ad = 0;
                if (count($app_docs) > 0) {
                    if (count($app_docs)<12) {
                        $s_ad = 0;
                        $data['eligibility_requirement']['msg'] .= " and the document uploades are not complete.";
                    }else{
                        $s_ad = 1;
                        $data['eligibility_requirement']['msg'] .= ".";
                    }
                }else {
                    $s_ad = 0;
                    $data['eligibility_requirement']['msg'] .= " and you have not uploaded documents yet.";
                }

                    // checking if its completed
                if (($s_ad == 1) && ($s_ap ==1)) {
                    $data['eligibility_requirement']['status'] = 1;
                    $data['eligibility_requirement']['msg'] = "Completed";
                }else{
                    $data['eligibility_requirement']['status'] = 0;
                }
                // end of progress for eligibility requirement

                // progress for Technical requirement
                $s_apf = 0;
                if (count($app_profile) > 0) {
                    $app_p = $app_profile[0];
                    if ((!$app_p->description==null) && (!$app_p->website==null)) {  //&& (!$app_p->evidence_of_equipment_ownership==null)
                        $s_apf = 1;
                        $data['technical_requirement']['msg'] .= " Your Profile technical requirement is complete";
                    }else {
                        $s_apf = 0;
                        $data['technical_requirement']['msg'] .= " You're still about to commplete your profile technical requirements";
                    }
                }else {
                    $s_apf = 0;
                    $data['technical_requirement']['msg'] .= " You need to go back to 'ELIGIBILITY REQUIREMENTS' tab and add APPLICANT NAME & DATE OF INCORPORATION/REGISTRATION";
                }

                $s_as = 0;
                if (count($app_staff) > 0) {
                    $s_as = 1;
                    $data['technical_requirement']['msg'] .= "";
                }else {
                    $s_as = 0;
                    $data['technical_requirement']['msg'] .= ", You need to add atleast one employer";
                }

                $s_apr = 0;
                if (count($app_projects) > 0) {
                    $s_apr = 1;
                    $data['technical_requirement']['msg'] .= "";
                }else {
                    $s_apr = 0;
                    $data['technical_requirement']['msg'] .= ", You need to add atleast one project";
                }

                // checking if its completed
                if (($s_apf == 1) && ($s_as == 1) && ($s_apr == 1)) {
                    $data['technical_requirement']['status'] = 1;
                    $data['technical_requirement']['msg'] = "Completed";
                }else{
                    $data['technical_requirement']['status'] = 0;
                }
                // End of tech requirement


                // progress for Financial Info
                $s_asf = 0;
                if (count($app_fin) > 0) {
                    if (count($app_fin)<3) {
                        $s_asf = 0;
                    $data['financial_info']['msg'] .= "You to add all the fields";
                    }else {
                        $s_asf = 1;
                        $data['financial_info']['msg'] .= "";
                    }
                }else {
                    $s_asf = 0;
                    $data['financial_info']['msg'] .= "You need to add your financial info.";
                }

                $s_aprf = 0;
                if (count($app_fin_dept) > 0) {
                    $s_aprf = 1;
                    $data['financial_info']['msg'] .= "";
                }else {
                    $s_aprf = 0;
                    $data['financial_info']['msg'] .= "You need to add atleast financial dept";
                }

                // checking if its completed
                if (($s_asf == 1) && ($s_aprf == 1)) {
                    $data['financial_info']['status'] = 1;
                    $data['financial_info']['msg'] = "Completed";
                }else{
                    $data['financial_info']['status'] = 0;
                }
                // End of Financial Info

                return response()->json([
                    'status' => true,
                    'message' => "Successful.",
                    'data' => $data,
                ]);

            }else{
                $data = [
                    "pre_qualification"=> ['status'=> 0, 'msg'=>''],
                    "lots"=> ['status'=> 0, 'msg'=>''],
                    "sublots"=>['status'=> 0, 'msg'=>''],
                    "eligibility_requirement"=> ['status'=> 0, 'msg'=>''],
                    "technical_requirement"=> ['status'=> 0, 'msg'=>''],
                    "financial_info"=> ['status'=> 0, 'msg'=>''],
                ];
                return response()->json([
                    'status' => false,
                    'data'=> $data,
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

    public function pre_qualification(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'application_id'=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            Application::where('id', $request->application_id)->update([
                "pre_qualification_status"=>"1"
            ]);

            $app = Application::find($request->application_id);

            return response()->json([
                'status' => true,
                'message' => "Successful, You've accepted the pre-qualification document.",
                'data' => [
                    "application"=>$app
                ]
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function downloadApplicationDocuments(Request $request)
    {
        // if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'application'=>'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $application = Application::find($request->application);
            $applicant = $application->applicant;

            $docs = $application->app_document;

            $zip = new ZipArchive;

            $fileName = 'applicants_documents_zip/'.$applicant->name.'-Application_Docs.zip';

            if (true === ($zip->open(storage_path('app/public/'.$fileName), ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
                $docs = $application->app_document;
                if (count($docs)>0) {
                    foreach ($docs as $file){
                        $doc = explode("/", $file->url);
                        $d = end($doc);

                        $path =  storage_path('app/public/documentFiles/'.$d);
                        $base = basename($path);
                        $base = explode('.', $base);
                        $base = end($base);

                        if (strpos($file->name, "/") !== false) {
                            $f =explode('/', $file->name);
                            $rr = "";
                            foreach ($f as $g) {
                                $rr.=$g." or ";
                            }
                            $file->name = $rr;
                        }
                        $relativeName =  substr($file->name, 0, 55).".".$base;//basename($path);

                        $zip->addFile($path, 'Eligibility_Requirements/'.$relativeName);
                    }
                }

                $staffs = $application->app_staffs;
                if (count($staffs)>0) {
                    foreach ($staffs as $s){
                        if ($s->education_certificate !== null) {
                            $docP = explode("/", $s->education_certificate);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/staffFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $s->name."-(EDUCATIONAL CERTIFICATE).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Employees_Documents/'.$s->name.'/'.$relativeNameP);
                        }
                        if ($s->professional_certificate !== null) {
                            $docP = explode("/", $s->professional_certificate);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/staffFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $s->name."-(PROFESSIONAL CERTIFICATE).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Employees_Documents/'.$s->name.'/'.$relativeNameP);
                        }
                        if ($s->cv !== null) {
                            $docP = explode("/", $s->cv);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/staffFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $s->name."-(CV).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Employees_Documents/'.$s->name.'/'.$relativeNameP);
                        }
                        if ($s->coren_license_document !== null) {
                            $docP = explode("/", $s->coren_license_document);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/staffFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $s->name."-(COREN LICENSE DOCUMENT).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Employees_Documents/'.$s->name.'/'.$relativeNameP);
                        }
                    }
                }

                $projects = $application->app_projects;
                if (count($projects)>0) {

                    foreach ($projects as $p){
                        if ($p->award_letter !== null) {
                            $docP = explode("/", $p->award_letter);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $p->name."-(EVIDENCE OF AWARD).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Project_Documents/'.$p->name.'/'.$relativeNameP);
                        }
                        if ($p->interim_valuation_cert !== null) {
                            $docP = explode("/", $p->interim_valuation_cert);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $p->name."-(EVIDENCE OF EQUITY OR DEBT RAISED FOR THE PROJECT).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Project_Documents/'.$p->name.'/'.$relativeNameP);
                        }
                        if ($p->certificate_of_completion !== null) {
                            $docP = explode("/", $p->certificate_of_completion);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $p->name."-CERTIFICATE OF COMPLETION.".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Project_Documents/'.$p->name.'/'.$relativeNameP);
                        }
                        if ($p->evidence_of_completion !== null) {
                            $docP = explode("/", $p->evidence_of_completion);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $p->name."-PHOTO EVIDENCE OF COMPLETED PROJECT.".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Project_Documents/'.$p->name.'/'.$relativeNameP);
                        }
                    }
                }

                $fin_depts = $application->app_financial_depts;
                if (count($fin_depts)>0) {

                    foreach ($fin_depts as $p){
                        if ($p->evidence_of_support !== null) {
                            $docP = explode("/", $p->evidence_of_support);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP =  $p->name."-(SUPPORTING DOCUMENT).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'Project_Documents/'.$p->name.'/'.$relativeNameP);
                        }
                    }
                }

                // Add cac certificate
                if ($applicant->cac_certificate !== null) {
                    $docP = explode("/", $applicant->cac_certificate);
                    $dP = end($docP);

                    $pathP =  storage_path('app/public/profileFiles/'.$dP);
                    $baseP = basename($pathP);
                    $baseP = explode('.', $baseP);
                    $baseP = end($baseP);
                    $relativeNameP =  $applicant->name."-CAC_certificate.".$baseP;//basename($path);

                    $zip->addFile($pathP, 'profileFiles/'.$relativeNameP);
                }
                //add tax clearance
                if ($applicant->tax_clearance_certificate !== null) {
                    $docPp = explode("/", $applicant->tax_clearance_certificate);
                    $dPp = end($docPp);

                    $pathPp =  storage_path('app/public/profileFiles/'.$dPp);
                    $basePp = basename($pathPp);
                    $basePp = explode('.', $basePp);
                    $basePp = end($basePp);
                    $relativeNamePp =  $applicant->name."-TAX_clearance.".$basePp;//basename($path);

                    $zip->addFile($pathPp, 'profileFiles/'.$relativeNamePp);
                }


                // jvs
                $jvs = $applicant->jvs;
                if (count($jvs)>0) {
                    foreach ($jvs as $s){
                        if (($s->evidence_of_cac !== null) || !empty($s->evidence_of_cac)) {
                            $docP = explode("/", $s->evidence_of_cac);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP = "(evidence_of_cac).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'JV/'.$s->name.'/'.$relativeNameP);
                        }

                        if (($s->company_income_tax !== null) || !empty($s->company_income_tax)) {
                            $docP = explode("/", $s->company_income_tax);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP = "(company_income_tax).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'JV/'.$s->name.'/'.$relativeNameP);
                        }
                        if (($s->audited_account !== null) || !empty($s->audited_account)) {
                            $docP = explode("/", $s->audited_account);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP = "(audited_account).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'JV/'.$s->name.'/'.$relativeNameP);
                        }

                        if (($s->letter_of_authorization !== null) || !empty($s->letter_of_authorization)) {
                            $docP = explode("/", $s->letter_of_authorization);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP = "(letter_of_authorization).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'JV/'.$s->name.'/'.$relativeNameP);
                        }

                        if (($s->sworn_affidavits !== null) || !empty($s->sworn_affidavits)) {
                            $docP = explode("/", $s->sworn_affidavits);
                            $dP = end($docP);

                            $pathP =  storage_path('app/public/projectFiles/'.$dP);
                            $baseP = basename($pathP);
                            $baseP = explode('.', $baseP);
                            $baseP = end($baseP);
                            $relativeNameP = "(sworn_affidavits).".$baseP;//basename($path);

                            $zip->addFile($pathP, 'JV/'.$s->name.'/'.$relativeNameP);
                        }
                    }
                }

                $zip->close();
            }

            return response()->download(storage_path('app/public/'.$fileName));

        // } else {
        //     return response()->json([
        //         'status' => false,
        //         'message' => trans('auth.failed')
        //     ], 404);
        // }
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
                    'title' => 'REA - Application Queried',
                    'li' => [
                        "Dear ".$app->applicant->name." ",
                        '',
                        "We would like to inform you that there is a query pertaining to your application for the Grant for Pilot Mini Grids in Rural Communities and Agricultural Value Chains through a Public Private Partnership (PPP) Model. This query arises due to the following reasons:",
                        '',
                        $add_app_decision->remark,
                        '',
                        'To address these queries, we kindly request you to revisit the platform by clicking on the following link',
                        '',
                        'https://applicant.grants.amp.gefundp.rea.gov.ng/',
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
                    'title' => 'REA - Congratulation, Your Application is Successful.',
                    'li' => [
                        "Dear ".$app->applicant->name." ",
                        '',
                        "We are pleased to inform you that your application for the Prequalification of the Grant for Pilot Mini Grids in Rural Communities and Agricultural Value Chains through a Public Private Partnership (PPP) Model has been successful! On behalf of our team, we extend our warmest congratulations on this achievement",
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
                    'title' => 'REA - Your Application is Unsuccessful.',
                    'li' => [
                        "Dear ".$app->applicant->name,
                        '',
                        "We appreciate the time and effort you put into applying for the Prequalification of the Grant for Pilot Mini Grids in Rural Communities and Agricultural Value Chains through a Public Private Partnership (PPP) Model. After a thorough evaluation of your application, we regret to inform you that we are unable to proceed with your application due to the following reasons:",
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
                    'title' => 'REA - Your Application is Under Review.',
                    'li' => [
                        "Dear ".$app->applicant->name,
                        '',
                        "We would like to inform you that your application for the Grant for Pilot Mini Grids in Rural Communities and Agricultural Value Chains through a Public Private Partnership (PPP) Model is currently undergoing review.",
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
                'message' => "You've made a decision successfully. An email has been sent to the applicant.",
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



    public function send_email_to_applicants($dd)
    {
        // ddd
        if ($dd == 0) {
            $app = Application::where("status", "=", null)->get();
            if (count($app)>0) {
                foreach ($app as $key => $a) {
                    $mailData = [
                        'title' => 'Application Update: GEF-UNDP Africa Minigrids Program (AMP) - NOT SUBMITTED',
                        'li' => [
                            "Dear ".$a->applicant->name." ",
                            '',
                            "Thank you for your interest in the Prequalification of the GEF-UNDP Africa Minigrids Program (AMP). We noticed you have started an application on the Grant Management Platform but not submitted it.",
                            '',
                            "We acknowledge the difficulties some applicants faced in uploading documents and in viewing the status of some sections. Kindly note that these issues have all been resolved, and the application process can thus be completed. However, if you are currently facing difficulties with your application, please contact us at amp@rea.gov.ng.",
                            "",
                            "Additionally, we encourage you to go to the “Review and Submit'' page to view the records pertaining to your application and ensure everything is in order before the deadline.",
                            '',
                            "We would like to remind you that the closing date for the application is August 24th, 2023, at 1:00 PM. Please ensure you complete and submit the application before this deadline.",
                            "",
                            "NOTE: For optimal performance,refresh the application by pressing: Ctrl+F5 or Shift+F5 on Windows/Linux, or Command+R on Mac",
                            "",
                            "Wishing you all the best",
                            '',
                            'Best regards,',
                            "Project Manager",
                        ],
                    ];
                    Mail::to($a->applicant->email)->queue(new SubmitApplicationMail($mailData));
                }
                return response()->json([
                    'status' => true,
                    'message' => "An email has been sent to all Applicant with the following status: NOT SUBMITTED",
                ]);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to send email ( NOT SUBMITTED). Try Again!!!",
                ]);
            }
        }

        // submited
        if ($dd == 1) {
            $app = Application::where("status", "=", "1")->get();
            if (count($app)>0) {
                foreach ($app as $key => $a) {

                    // previos mails
                    // $mailData = [
                    //     'title' => 'Application Update: GEF-UNDP Africa Minigrids Program (AMP) - SUBMITTED',
                    //     'li' => [
                    //         "Dear ".$a->applicant->name." ",
                    //         '',
                    //         "We appreciate your submission to the Prequalification of the GEF-UNDP Africa Mini Grids Program (AMP). We would like to gently remind you that the application's closing date is set for 24th August 2023 at 1:00 PM. We encourage you to go to the “Review and Submit'' page to view all the records pertaining to your application, ensuring everything is in order ahead of the deadline.",
                    //         '',
                    //         "NOTE: You have the ability to modify and submit your application again before the deadline.",
                    //         '',
                    //         "Wishing you all the best",
                    //         '',
                    //         'Best regards,',
                    //         "Project Manager",
                    //     ],
                    // ];

                    // ahmed mails
                    // $mailData = [
                    //     'title' => 'Updated Zoom Link for Africa Minigrids Program (AMP) Prequalification Submission  Opening on August 24th, 2023 @ 1:00PM',
                    //     'li' => [
                    //         "Dear ".$a->applicant->name." ",
                    //         '',
                    //         "We hope this message finds you well. We would to bring to your attention an important matter regarding the information you have submitted through our platform for your application.",
                    //         '',
                    //         "Due to a technical issue, we have identified that there might be discrepancies with the staff details you have previously uploaded. We sincerely apologize for any inconvenience this may have caused.",
                    //         '',
                    //         "To ensure the accuracy of your submission and expedite the review process, we kindly request you to log back into our platform as soon as possible. Once logged in, please navigate to the Review and Submit section and carefully review the information you have provided. If you find any discrepancies in the staff details, please take a moment to update or re-add all the staff information accordingly before clicking the save button.",
                    //         '',
                    //         "Please note that for those applicants whose staff details match their previous submission, no further action is required on your part. You can simply disregard this message.",
                    //         "",
                    //         "We understand the importance of your time and effort, and we deeply appreciate your cooperation in resolving this matter promptly. Your accurate and up-to-date information is crucial to ensure a smooth application process.",
                    //         "",
                    //         "Should you encounter any challenges or have any questions while revisiting your submission, please feel free to reach out to our support team at amp@rea.gov.ng for assistance.",
                    //         "",
                    //         'Thank you!',
                    //     ],
                    // ];

                    // ahmed mails
                    $mailData = [
                        'title' => 'Updated Zoom Link for Africa Minigrids Program (AMP) Prequalification Submission  Opening on August 24th, 2023 @ 1:00PM',
                        'li' => [
                            "Dear ".$a->applicant->name." ",
                            '',
                            "We would like to extend our gratitude for your enthusiastic participation in our application process. The application submission phase has now come to a close, and we are thrilled with the overwhelming response we've received.",
                            '',
                            "As we move forward, we are excited to invite you to the Virtual Opening Ceremony. During this event, we will review all the submitted applications and share important insights about the next steps in the selection process.",
                            '',
                            "You can Join the Zoom Meeting Now at: ",
                            '',
                            "https://rea-gov-ng.zoom.us/j/89996450329?pwd=elR2WURybFhjTGY5OGp0bFFWMGtIQT09",
                            "",
                            "Meeting ID: 899 9645 0329",
                            "Passcode: 215694",
                            "",
                            "Please mark your calendars and join us at the provided link. This is an excellent opportunity to gain insights into the selection process, understand our evaluation criteria, and get a glimpse of the exciting journey that lies ahead.",
                            "",
                            "Note: Applications will be closed Now (@ 1:30PM WAT)",
                            "",
                            "We appreciate your interest and effort in being a part of this process. Your enthusiasm and dedication contribute significantly to the success of our initiative. If you have any questions or need assistance accessing the virtual event, please don't hesitate to contact us at amp.rea.gov.ng",
                            "",
                            "Thank you once again for your interest, and we look forward to connecting with you virtually during the Opening Ceremony.",
                            "",
                            'Thank you!',
                        ],
                    ];

                    // $mailData = [
                    //     'title' => 'Updated Zoom Link for Africa Minigrids Program (AMP) Prequalification Submission  Opening on August 24th, 2023 @ 1:00PM',
                    //     'li' => [
                    //         "Dear ".$a->applicant->name." ",
                    //         '',
                    //         "Thank you for your interest in the GEF-UNDP-REA Africa Minigrids Program (AMP).",
                    //         '',
                    //         "Your application in response to the Invitation for Prequalification has been successfully submitted.",
                    //         '',
                    //         "Please be advised of a change to the Zoom link for the hybrid physical-virtual ceremony of your application scheduled for 1.00pm (WAT) on Thursday, August 24th, 2023.",
                    //         '',
                    //         "We respectfully request that your organization nominate an authorized representative to virtually attend the ceremony. If this is not possible, the organization may delegate its contact person or an individual whose information has been included within the application.",
                    //         "",
                    //         "Join Zoom Meeting",
                    //         "https://rea-gov-ng.zoom.us/j/89996450329?pwd=elR2WURybFhjTGY5OGp0bFFWMGtIQT09",
                    //         "",
                    //         "Meeting ID: 899 9645 0329",
                    //         "Passcode: 215694",
                    //         "",
                    //         "For further enquiry, kindly drop a message on the platform or send an email to amp@rea.gov.ng.",
                    //         "",
                    //         'Thank you!',
                    //     ],
                    // ];
                    Mail::to($a->applicant->email)->queue(new SubmitApplicationMail($mailData));
                }
                return response()->json([
                    'status' => true,
                    'message' => "An email has been sent to all Applicant with the following status: SUBMITTED",
                ]);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to send email (SUBMITTED). Try Again!!!",
                ]);
            }
        }

        // queried
        if ($dd == 2) {
            $app = Application::where("status", "=", "2")->get();
            if (count($app)>0) {
                foreach ($app as $key => $a) {
                    $mailData = [
                        'title' => 'Application Update: GEF-UNDP Africa Minigrids Program (AMP) - QUERIED',
                        'li' => [
                            "Dear ".$a->applicant->name." ",
                            '',
                            "We want to bring to your attention that your application is currently under a query that needs your response. Please refer to our previous email concerning the details of the query and the issue it pertains to.",
                            '',
                            "Furthermore, we kindly remind you that the deadline for the application is 24th August 2023 at 1:00 PM. We urge you to visit the “Review and Submit” page to inspect all the records related to your application, ensuring that all is in place ahead of the deadline.",
                            '',
                            "NOTE: You have the ability to modify and submit your application again before the deadline.",
                            '',
                            "Wishing you all the best",
                            '',
                            'Best regards,',
                            "Project Manager",
                        ],
                    ];
                    Mail::to($a->applicant->email)->queue(new SubmitApplicationMail($mailData));
                }
                return response()->json([
                    'status' => true,
                    'message' => "An email has been sent to all Applicant with the following status: QUERIED",
                ]);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to send email (QUERIED). Try Again!!!",
                ]);
            }
        }



        // fghsjhkfj
        if ($dd == 556) {
            $app = Application::where("status", "=", "1")->get();
            if (count($app)>0) {

                Application::where("status", "=", "1")->update([
                    "status"=>"5",
                ]);

                // $app[0]->program->stages[0]->id;
                ProgramStage::where('id', '=', $app[0]->program->stages[0]->id)->update([
                    "isActive"=>"0",
                ]);

                foreach ($app as $key => $a) {
                    $mailData = [
                        'title' => 'REA - Your Application is Undergoing Review.',
                        'li' => [
                            "Dear " . $a->applicant->name . ",",
                            '',
                            "We would like to inform you that your application for the Grant for Pilot Mini Grids in Rural Communities and Agricultural Value Chains through a Public Private Partnership (PPP) Model is currently undergoing review.",
                            '',
                            'You will receive notification regarding the outcome of your application and the subsequent steps in due course.',
                            "",
                            'Best regards,',
                        ],
                    ];
                    Mail::to($a->applicant->email)->queue(new SubmitApplicationMail($mailData));
                }

                return response()->json([
                    'status' => true,
                    'message' => "An email has been sent to all Applicant with the status UNDER REVIEW.",
                ]);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to Submited app found. Try Again!!!",
                ]);
            }
        }
    }

    public function send_email_rfg(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'=>'required',
            'name'=>'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $mailData = [
            'title' => 'Application Update: GEF-UNDP Africa Minigrids Program (AMP)',
            'li' => [
                "Dear ".$request->name." ",
                '',
                "I hope this email finds you well.",
                '',
                "I am pleased to inform you that your application has been selected to proceed to the document submission stage for the Request for Grant process. Congratulations on this achievement!",
                "",
                "Please find attached detailed instructions regarding the documents required for submission on the portal. Kindly ensure that all necessary documents are prepared and submitted according to the provided templates. Should you have any questions or require clarification on any aspect of the submission process, please do not hesitate to reach out to us.",
                '',
                "We would like to remind you that the closing date for the submission is March 10th, 2023, at 1:00 PM. Please ensure you complete and submit before this deadline.",
                "",
                "Once again, congratulations on being selected for this stage. We look forward to reviewing your submission and wish you the best of luck in the next steps of the process.",
                "",
                "Wishing you all the best",
                '',
                'Best regards,',
                "Project Manager",
            ],
        ];
        Mail::to($request->email)->queue(new SubmitApplicationMail($mailData));

    }

}
