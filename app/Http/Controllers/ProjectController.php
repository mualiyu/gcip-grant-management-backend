<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantProjectDocument;
use App\Models\ApplicantProposal;
use App\Models\Program;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class ProjectController extends Controller
{
    public function getAll(Request $request, Program $program)
    {
         if ($request->user()->tokenCan('Admin')) {
             $projects = Project::where(['program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get();
             if (count($projects)>0) {
                 return response()->json([
                     'status' => true,
                     'data' => [
                         'projects' => $projects,
                     ],
                 ]);
             }else {
                return response()->json([
                    'status' => false,
                    'message' => "No project found for this program..."
                ], 401);
             }
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function getOne(Request $request, Program $program, Project $project)
    {
         if ($request->user()->tokenCan('Admin')) {
             $project = Project::where(['program_id'=>$program->id, 'id'=>$project->id])->with("project_documents")->with("project_requirements")->with("assigned_applicants")->get();
             if (count($project)>0) {
                 return response()->json([
                     'status' => true,
                     'data' => [
                         'projects' => $project[0],
                     ],
                 ]);
             }else {
                return response()->json([
                    'status' => false,
                    'message' => "No project found for this program..."
                ], 401);
             }
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
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

    public function create(Request $request, Program $program)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'lot_name' => 'required',
                'name_of_community' => 'required',
                'description' => 'required',
                'state' => 'required',
                'lga' => 'required',
                'coordinate' => 'required',
                // array
                // 'project_documents' => 'nullable',
                // 'project_requirements' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $project = Project::create([
                'program_id'=> $program->id,
                'lot_name' => $request->lot_name,
                'name_of_community' => $request->name_of_community,
                'description' => $request->description,
                'state' => $request->state,
                'lga' => $request->lga,
                'coordinate' => $request->coordinate,
            ]);

            if ($project) {
                if (array_key_exists('project_documents', $request->all())) {
                    foreach ($request->project_documents as $key => $doc) {
                        $docc = ProjectDocument::create([
                            "project_id"=>$project->id,
                            "name"=>$doc['name'],
                            "url"=>$doc['url'],
                        ]);
                    }
                }

                if (array_key_exists('project_requirements', $request->all())) {
                    foreach ($request->project_requirements as $key => $req) {
                        $r = ProjectRequirement::create([
                            "project_id"=>$project->id,
                            "name"=>$req['name'],
                        ]);
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => "Successful, a new project has been created",
                    'data' => [
                        'project' =>Project::where(['id'=>$project->id, 'program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get()[0]
                    ]
                ]);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function update(Request $request, Program $program, Project $project)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'lot_name' => 'required',
                'name_of_community' => 'required',
                'description' => 'required',
                'state' => 'required',
                'lga' => 'required',
                'coordinate' => 'required',
                // array
                // 'project_documents' => 'nullable',
                // 'project_requirements' => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $project_update = Project::where(['id'=>$project->id, 'program_id'=>$program->id])->update([
                'lot_name' => $request->lot_name,
                'name_of_community' => $request->name_of_community,
                'description' => $request->description,
                'state' => $request->state,
                'lga' => $request->lga,
                'coordinate' => $request->coordinate,
            ]);

            if ($project_update) {

                return response()->json([
                    'status' => true,
                    'message' => "Successful, Project is updated",
                    'data' => [
                        'project' =>Project::where(['id'=>$project->id, 'program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get()[0]
                    ]
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed to update"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function update_d(Request $request, Program $program, Project $project)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'project_document_id' => 'required',
                "name"=>'required',
                "url"=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $doc_update = ProjectDocument::where(['id'=>$request->project_document_id, 'project_id'=>$project->id])->update([
                'name' => $request->name,
                'url' => $request->url,
            ]);

            if ($doc_update) {

                return response()->json([
                    'status' => true,
                    'message' => "Successful, Project document is updated",
                    'data' => [
                        'project' =>Project::where(['id'=>$project->id, 'program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get()[0]
                    ]
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed to update"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function update_r(Request $request, Program $program, Project $project)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'project_requirement_id' => 'required',
                "name"=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $req_update = ProjectRequirement::where(['id'=>$request->project_requirement_id, 'project_id'=>$project->id])->update([
                'name' => $request->name,
            ]);

            if ($req_update) {

                return response()->json([
                    'status' => true,
                    'message' => "Successful, Project requirement is updated",
                    'data' => [
                        'project' =>Project::where(['id'=>$project->id, 'program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get()[0]
                    ]
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed to update"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function delete(Request $request, Program $program, Project $project)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'delete' => 'required',
                "project_requirement_id"=>'nullable',
                "project_document_id"=>'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->delete == "project") {
                ProjectRequirement::where(['project_id'=>$project->id])->delete();
                ProjectDocument::where(['project_id'=>$project->id])->delete();

                $project->delete();

                return response()->json([
                    'status' => true,
                    'message' => "Successful, you have deleted one project",
                ]);

            }elseif ($request->delete == "project_requirement") {
                ProjectRequirement::where(['project_id'=>$project->id, 'id'=>$request->project_requirement_id])->delete();
                return response()->json([
                    'status' => true,
                    'message' => "Successful, you have deleted one project requirement",
                ]);
            }elseif ($request->delete == "project_document") {
                ProjectDocument::where(['project_id'=>$project->id, 'id'=>$request->project_document_id])->delete();
                return response()->json([
                    'status' => true,
                    'message' => "Successful, you have deleted one project document",
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed to delete at the moment"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function allocate(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'project_id' => 'required',
                'applicant_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $project = Project::where(['id'=>$request->project_id])->get();

            $a_p = DB::table('applicant_project')->where([
                'applicant_id'=>$request->applicant_id,
                'project_id'=>$request->project_id,
            ])->get();

            if (!count($a_p)>0) {
                DB::table('applicant_project')->insert([
                    'applicant_id'=>$request->applicant_id,
                    'project_id'=>$request->project_id,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => "You have successfully allocated ".$project[0]->lot_name." to this user.",
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => $project[0]->lot_name." is already allocated to this user"
                ], 404);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function misallocate(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {

            $validator = Validator::make($request->all(), [
                'project_id' => 'required',
                'applicant_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $project = Project::where(['id'=>$request->project_id])->get();

            DB::table('applicant_project')->where([
                'applicant_id'=>$request->applicant_id,
                'project_id'=>$request->project_id,
            ])->delete();

            return response()->json([
                'status' => true,
                'message' => "You removed ".$project[0]->lot_name." from this user.",
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    public function appGetAll(Request $request)
    {
         if ($request->user()->tokenCan('Applicant')) {
             $projects = $request->user()->projects;
            //  Project::where(['program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get();
             if (count($projects)>0) {
                foreach ($projects as $p) {
                    $pp = Project::where(['id'=>$p->id])->with("project_documents")->with("project_requirements")->get()[0];
                    $app_up_docs = ApplicantProjectDocument::where(["applicant_id"=>$request->user()->id, "project_id"=>$pp->id])->get();

                    if (count($pp->project_requirements) == count($app_up_docs)) {
                        $p['completed_status'] = "1";
                    }else{
                        $p['completed_status'] = "0";
                    }
                }
                 return response()->json([
                     'status' => true,
                     'data' => [
                         'projects' => $projects,
                     ],
                 ]);
             }else {
                return response()->json([
                    'status' => false,
                    'message' => "No project found for this Applicant..."
                ], 401);
             }
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function appGetOne(Request $request, $id)
    {
         if ($request->user()->tokenCan('Applicant')) {
             $projects = $request->user()->projects;
            //  Project::where(['program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get();
            $proj = "";
             if (count($projects)>0) {
                foreach ($projects as $p) {
                   if ($p->id == $id) {
                    $pp = Project::where(['id'=>$id])->with("project_documents")->with("project_requirements")->get()[0];
                    $proj = $pp;
                   }
                }

                if ($proj) {
                    $proj["applicant_uploaded_documents"] = $request->user()->applicant_uploaded_documents;
                    return response()->json([
                        'status' => true,
                        'data' => [
                            'project' => $proj,
                        ],
                    ]);
                }else{
                    return response()->json([
                        'status' => false,
                        'message' => "Project is not allocated to this user."
                    ], 422);
                }

             }else {
                return response()->json([
                    'status' => false,
                    'message' => "No project found for this Applicant..."
                ], 401);
             }
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function appUpload(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'file' => 'required|max:20000',
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
                $request->file("file")->storeAs("public/applicantProjectFiles", $fileNameToStore);

                $url = url('/storage/applicantProjectFiles/'.$fileNameToStore);

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

    public function appSubmit(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'project_requirement_id' => 'required',
                "name"=>'required',
                "url"=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            $pr = ProjectRequirement::find($request->project_requirement_id);

            $check = ApplicantProjectDocument::where(['applicant_id' => $request->user()->id, 'project_requirement_id' => $request->project_requirement_id, 'project_id' => $pr->project->id])->get();
            if (count($check)>0) {
                $app_proj = ApplicantProjectDocument::where(['applicant_id' => $request->user()->id, 'project_requirement_id' => $request->project_requirement_id,'project_id' => $pr->project->id])->update([
                    'name' => $request->name,
                    'url' => $request->url,
                ]);
            }else{
                $app_proj = ApplicantProjectDocument::create([
                    'applicant_id' => $request->user()->id,
                    'project_id' => $pr->project->id,
                    'project_requirement_id' => $request->project_requirement_id,
                    'name' => $request->name,
                    'url' => $request->url,
                ]);
            }

            if ($app_proj) {
                return response()->json([
                    'status' => true,
                    'message' => "Successful, One document is uploaded",
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed to update"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function appDeleteDocument(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'delete' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if ($request->delete == "all") {
                ApplicantProjectDocument::where(['applicant_id'=>$request->user()->id])->delete();

                return response()->json([
                    'status' => true,
                    'message' => "Successful, you have deleted all documents from this project",
                ]);
            }else{
                ApplicantProjectDocument::where(['applicant_id'=>$request->user()->id, 'name'=>$request->delete])->delete();

                return response()->json([
                    'status' => true,
                    'message' => "Successful, you have deleted one document"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function appReview(Request $request)
    {
         if ($request->user()->tokenCan('Applicant')) {
             $projects = $request->user()->projects;
            //  Project::where(['program_id'=>$program->id])->with("project_documents")->with("project_requirements")->get();
             if (count($projects)>0) {
                $proj = [];
                foreach ($projects as $p) {
                    $pp = Project::where(['id'=>$p->id])->with("project_documents")->with("project_requirements")->get()[0];
                    $pp["applicant_uploaded_documents"] = ApplicantProjectDocument::where(["applicant_id"=>$request->user()->id, "project_id"=>$pp->id])->get();
                    array_push($proj, $pp);
                }
                 return response()->json([
                     'status' => true,
                     'data' => [
                         'projects' => $proj,
                     ],
                 ]);
             }else {
                return response()->json([
                    'status' => false,
                    'message' => "No project found for this Applicant..."
                ], 401);
             }
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function appSubmitProposal(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'satisfied' => 'required',
                'program_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            if ($request->satisfied == "1") {

                $check = ApplicantProposal::where(['program_id'=>$request->program_id, 'applicant_id'=>$request->user()->id])->get();

                if (count($check)>0) {
                    $prop = ApplicantProposal::where(['program_id'=>$request->program_id, 'applicant_id'=>$request->user()->id])->update([
                        "status" => "1",
                    ]);
                    return response()->json([
                        'status' => true,
                        'message' => "Successful, Your proposal has been updated. You will be notified via email when your proposal has been reviewed.",
                    ]);
                }else{
                    $prop = ApplicantProposal::create([
                        "program_id"=>$request->program_id,
                        "applicant_id" => $request->user()->id,
                        "status" => "1",
                    ]);

                    if ($prop) {
                        return response()->json([
                            'status' => true,
                            'message' => "Successful, Your proposal has been submited. You will be notified via email when your proposal has been reviewed.",
                        ]);
                    }else{
                        return response()->json([
                            'status' => false,
                            'message' => "Failed to submit your proposal, Try again later!!!"
                        ], 422);
                    }
                }
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "Failed to submit your proposal, Make sure to tick the 'I AM SATISFIED WITH MY SUBMISSIONS SO FAR' checkbox, Thank you."
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function getAllProposals(Request $request, Program $program)
    {
         if ($request->user()->tokenCan('Admin')) {

            $sub_proposals = ApplicantProposal::where(['program_id'=>$program->id, "status"=>'1'])->with("applicant")->get();
            $s_proposals = ApplicantProposal::where(['program_id'=>$program->id, "status"=>'2'])->with("applicant")->get();
            $uns_proposals = ApplicantProposal::where(['program_id'=>$program->id, "status"=>'3'])->with("applicant")->get();
            $und_proposals = ApplicantProposal::where(['program_id'=>$program->id, "status"=>'4'])->with("applicant")->get();

             $prop = [
                'submited_proposals'=>$sub_proposals,
                'successful_proposals'=>$s_proposals,
                'unsuccessful_proposals'=>$uns_proposals,
                'under_review_proposals'=>$und_proposals
            ];
            return response()->json([
                'status' => true,
                'data' => [
                    'proposals' => $prop,
                ],
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function getOneProposal(Request $request, Program $program, ApplicantProposal $applicantproposal)
    {
         if ($request->user()->tokenCan('Admin')) {
            $app = $applicantproposal->applicant;

            $applicant = Applicant::find($app->id);
            $projects = $applicant->projects;

            $proj = [];

            foreach ($projects as $p) {
                $pp = Project::where(['id'=>$p->id])->with("project_documents")->with("project_requirements")->get()[0];
                $pp["applicant_uploaded_documents"] = ApplicantProjectDocument::where(["applicant_id"=>$applicant->id, "project_id"=>$pp->id])->get();
                array_push($proj, $pp);
            }

            $a = Applicant::find($app->id);
            $a['proposal_status'] = $applicantproposal->status;
            $a['proposal_id'] = $applicantproposal->id;
            $a['projects'] = $proj;

            return response()->json([
                'status' => true,
                'data' => [
                    'applicant_proposal' => $a,
                ],
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 401);
        }
    }

    public function downloadProposalDocs(Request $request, Program $program, ApplicantProposal $applicantproposal)
    {
        //  if ($request->user()->tokenCan('Admin')) {

            $app = $applicantproposal->applicant;

            $applicant = Applicant::find($app->id);
            $projects = $applicant->projects;

            $zip = new ZipArchive;
            $fileName = 'applicants_documents_zip/'.$applicant->name.'-Application_for_request_for_grant.zip';
            $proj = [];

            if (true === ($zip->open(storage_path('app/public/'.$fileName), ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
                if (count($projects)>0) {

                    foreach ($projects as $p) {
                        $pp = Project::where(['id'=>$p->id])->with("project_documents")->with("project_requirements")->get()[0];

                        $pp["applicant_uploaded_documents"] = ApplicantProjectDocument::where(["applicant_id"=>$applicant->id, "project_id"=>$pp->id])->get();

                        foreach ($pp["applicant_uploaded_documents"] as $file) {
                            $doc = explode("/", $file->url);
                            $d = end($doc);
                            $path =  storage_path('app/public/applicantProjectFiles/' . $d);
                            $base = basename($path);
                            $base = explode('.', $base);
                            $base = end($base);

                            if (strpos($file->name, "/") !== false) {
                                $f = explode('/', $file->name);
                                $rr = "";
                                foreach ($f as $g) {
                                    $rr .= $g . " or ";
                                }
                                $file->name = $rr;
                            }
                            $relativeName =  $file->name.".".$base; //basename($path);

                            $zip->addFile($path, $p->lot_name.'/'.$relativeName);
                        }
                    }

                }
                $zip->close();
            }
            return response()->download(storage_path('app/public/'.$fileName));

        // }else{
        //     return response()->json([
        //         'status' => false,
        //         'message' => trans('auth.failed')
        //     ], 401);
        // }
    }


}
