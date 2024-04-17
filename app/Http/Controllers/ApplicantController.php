<?php

namespace App\Http\Controllers;

use App\Mail\AcceptApplicantMail;
use App\Mail\MessageNotificationMail;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\JV;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ApplicantController extends Controller
{
    public function register(Request $request)
    {
        // return response()->json([
        //     'status' => false,
        //     'message' => "Sorry, Registration is closed.",
        // ], 422);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:applicants,email',
            'username' => 'required|unique:applicants,username',
            'phone' => 'required|unique:applicants,phone',
            'person_incharge' => 'required',
            'rc_number'=>'required|unique:applicants,rc_number',
            'address'=>'required',
            'cac_certificate' => 'nullable',
            'tax_clearance_certificate' => 'nullable',
            'has_designed'=>'nullable',
            'has_operated'=>'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // if ($request->hasFile("cac_certificate")) {
        //     $fileNameWExt = $request->file("cac_certificate")->getClientOriginalName();
        //     $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
        //     $fileExt = $request->file("cac_certificate")->getClientOriginalExtension();
        //     $fileNameToStore = $fileName."_".time().".".$fileExt;
        //     $request->file("cac_certificate")->storeAs("public/profileFiles", $fileNameToStore);

        //     $url = url('/storage/profileFiles/'.$fileNameToStore);
        //     $request['cac_certificate'] = $url;

        // }else{
        //     $request['cac_certificate'] = "";
        // }

        // if ($request->hasFile("tax_clearance_certificate")) {
        //     $fileNameWExt = $request->file("tax_clearance_certificate")->getClientOriginalName();
        //     $fileName = pathinfo($fileNameWExt, PATHINFO_FILENAME);
        //     $fileExt = $request->file("tax_clearance_certificate")->getClientOriginalExtension();
        //     $fileNameToStore = $fileName."_".time().".".$fileExt;
        //     $request->file("tax_clearance_certificate")->storeAs("public/profileFiles", $fileNameToStore);

        //     $url = url('/storage/profileFiles/'.$fileNameToStore);
        //     $request['tax_clearance_certificate'] = $url;

        // }else{
        //     $request['tax_clearance_certificate'] = "";
        // }

        $pass = mt_rand(10000000,99999999);

        $password = Hash::make($pass);

        $request['password'] = $password;
        $request['isApproved'] = 1;

        $user = Applicant::create($request->all());



        if ($user) {
            $mailData = [
                'title' => 'Your registration is successful.',
                // 'body' => 'Use Username: '.$user->username.' & Password: '.$pass,
                // 'body'=> "Congratulations on your successful registration! We are currently reviewing applicants and will notify you soon regarding the Approval decision.
                // \nOnce the selection is made, you will receive an acceptance notification along with your password for accessing your portal and resources.
                // \nThank you for your patience. We will keep you updated.
                // \nBest regards,",
                'body'=> "Thank you for submitting your application!
                \nOur team is currently reviewing your documents and will notify you about the approval outcome soon.
                Once your documents are verified, you'll receive an acceptance notification along with your portal access password.
                We appreciate your patience, and we'll keep you updated. \n\nBest regards,",
            ];
            $adminMailData = [
                'title' => 'Your registration is successful.',
                'body'=> "A company has signed up on the grant management platform. Please access the portal to review the submitted documents and proceed to either accept or reject the user's application for portal access.",
            ];
            // return $pass;
            Mail::to($user->email)->send(new MessageNotificationMail($mailData));
            // Mail::to("amp@rea.gov.ng")->send(new MessageNotificationMail($adminMailData));
        }

        return response()->json([
            'status' => true,
            'data' => $user,
            'message' => 'Registration successfull.'
        ], 201);
    }

    // clearance files for Applicant registaration
    public function registerUpload(Request $request)
    {
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
                    'message' => "Error! File upload invalid. Try again."
                ], 422);
            }
    }

    //verify
    public function verify(Request $request)
    {
    }

    //login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // $user = Applicant::where('username', $request->username)->first();
        $user = Applicant::where('username', '=', $request->username)->get();
        if (!count($user)>0) {
            $user = Applicant::where('email', '=', $request->username)->get();
            // $user = Applicant::where('email', $request->username)->first();

            if (!count($user)>0) {
                return response()->json([
                    'status' => false,
                    'message' => "Sorry, user not found."
                ], 422);
            }
        }else{
            $user = Applicant::where('username', '=', $request->username)->get();
            // $user = Applicant::where('username', $request->username)->first();
        }
        $user = $user[0];

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 422);
        }

        if ($user->isApproved==2) {
            return response()->json([
                'status' => true,
                'data' => [
                    'user' => $user,
                    'token' => $user->createToken($request->device_name, ['Applicant'])->plainTextToken
                ],
                'message' => 'Login successfull.'
            ]);
        }else{
            return response()->json([
                'status' => false,
                'message' => "Sorry, your Account is not activated, Try again later..."
            ], 422);

        }

    }

    //recover
    public function recover(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $applicant = Applicant::where('username', '=', $request->username)->get();
        if (!count($applicant)>0) {
            $applicant = Applicant::where('email', '=', $request->username)->get();
        }else{
            $applicant = Applicant::where('username', '=', $request->username)->get();
        }
        $username = $request->user;
        if (is_numeric($username) == true) {
            $user = Applicant::where('phone', '=', $username)->get();
        } else {
            $user = Applicant::where('username', '=', $username)->get();
        }
        if (count($applicant) > 0) {

            $pass = mt_rand(10000000, 99999999);

            $password = Hash::make($pass);

            $user = $applicant[0];

            $mailData = [
                    'title' => 'Your password reset',
                    'body' => 'Use Username: ' . $user->username . ' & Password: ' . $pass,
                ];

            $update = Applicant::where('id', '=', $applicant[0]->id)->update([
                'password' => $password
            ]);

            if ($update) {
                // return "Your username is: ".$user->username." & password is: ".$pass;

                Mail::to($user->email)->send(new AcceptApplicantMail($mailData));

                return response()->json([
                    'status' => true,
                    'message' => "An email is sent to your mail. Thank you!"
                ], 200);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "System Error, Failed to change password. Try again later."
                ], 422);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => "Failed, User not found"
            ], 422);
        }
    }

    //reset
    public function reset(Request $request)
    {
     if ($request->user()->tokenCan('Applicant')) {
            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $applicant = Applicant::where('id', '=', $request->user()->id)->get();

            if(count($applicant)>0){
                if (Hash::check($request->current_password, $applicant[0]->password)) {

                        $update = Applicant::where('id', '=', $applicant[0]->id)->update([
                            'password' => Hash::make($request->password),
                        ]);

                        if ($update) {
                            return response()->json([
                                'status' => true,
                                'message' => "You've successfully changed your password."
                            ], 200);
                        }else{
                            return response()->json([
                                'status' => false,
                                'message' => "System Error, Failed to change password. Try again later."
                            ], 422);
                        }

                } else {
                    return response()->json([
                        'status' => false,
                        'message' => "Failed, Password does not match the current password"
                    ], 422);
                }
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Failed, User not found"
                ], 422);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    //user
    public function user(Request $request)
    {
        $user = Applicant::where('id', $request->user()->id)->with('jvs')->get()[0];
        if ($request->user()->tokenCan('Applicant')) {
            return response()->json([
                'status' => true,
                'data' => [
                    'user' => $user
                ],
            ]);

        }else{
            // $request->user()->tokens()->delete();
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function updateProfile(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'phone' => 'required',
                'person_incharge' => 'nullable',
                'rc_number' => 'required',
                'address' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = Applicant::where('id',$request->user()->id)->update($request->all());

            if ($user) {
                # code...
                return response()->json([
                    'status' => true,
                    'message' => "Profile update completed",
                    // 'data' => [
                    //     'user' => User::find($request->user()->id),
                    // ],
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Sorry Profile Update Failed"
                ], 422);
            }

        }else{
            // $request->user()->tokens()->delete();
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function addJv(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'phone' => 'required',
                'email' => "required",
                'rc_number' => 'nullable',
                'address' => 'nullable',
                'document'=> 'nullable',
                'type'=> 'nullable',

                'evidence_of_cac'=> 'nullable',
                'company_income_tax'=> 'nullable',
                'audited_account'=> 'nullable',
                'letter_of_authorization'=> 'nullable',
                'sworn_affidavits'=> 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }



            $request['applicant_id'] = $request->user()->id;

            // $request['document'] = $document;
            // $request['evidence_of_cac'] = $evidence_of_cac;
            // $request['company_income_tax'] = $company_income_tax;
            // $request['audited_account'] = $audited_account;
            // $request['letter_of_authorization'] = $letter_of_authorization;
            // $request['sworn_affidavits'] = $sworn_affidavits;

            // return $request->all();
            $jv = JV::create([
                'applicant_id'=> $request->user()->id,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'rc_number' => $request->rc_number,
                'address' => $request->address,
                'document'=> $request->document,
                'type'=> $request->type,

                'evidence_of_cac'=> $request->evidence_of_cac,
                'company_income_tax'=> $request->company_income_tax,
                'audited_account'=> $request->audited_account,
                'letter_of_authorization'=> $request->letter_of_authorization,
                'sworn_affidavits'=> $request->sworn_affidavits,
            ]);

            if ($jv) {

                return response()->json([
                    'status' => true,
                    'message' => "Join Venture is added to system info",
                    'data' => [
                        'jv' => $jv,
                    ],
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Sorry Failed to add JV"
                ], 422);
            }

        }else{
            // $request->user()->tokens()->delete();
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function updateJv(Request $request, $id)
    {
        if ($request->user()->tokenCan('Applicant')) {

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'phone' => 'required',
                'email' => "required",
                'rc_number' => 'nullable',
                'address' => 'nullable',
                'document'=> 'nullable',
                'type'=>'nullable',

                'evidence_of_cac'=> 'nullable',
                'company_income_tax'=> 'nullable',
                'audited_account'=> 'nullable',
                'letter_of_authorization'=> 'nullable',
                'sworn_affidavits'=> 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $jv = JV::where(['id'=>$id, 'applicant_id'=>$request->user()->id])->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'rc_number' => $request->rc_number,
                'address' => $request->address,
                'document'=> $request->document,
                'type'=> $request->type,

                'evidence_of_cac'=> $request->evidence_of_cac,
                'company_income_tax'=> $request->company_income_tax,
                'audited_account'=> $request->audited_account,
                'letter_of_authorization'=> $request->letter_of_authorization,
                'sworn_affidavits'=> $request->sworn_affidavits,
            ]);

            if ($jv) {

                return response()->json([
                    'status' => true,
                    'message' => "Join Venture is updated",
                ]);
            }else{
                return response()->json([
                    'status' => false,
                    'message' => "Sorry Failed to update JV"
                ], 422);
            }

        }else{
            // $request->user()->tokens()->delete();
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function uploadJv(Request $request)
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
                $fileNameToStore = $fileName."_".time().".".$fileExt;
                $request->file("file")->storeAs("public/jvFiles", $fileNameToStore);

                $url = url('/storage/jvFiles/'.$fileNameToStore);

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

    //logout
    public function logout(Request $request)
    {
        if ($request->user()->tokenCan('Applicant')) {
            $request->user()->tokens()->delete();
            return response()->json([
                'status' => true,
                'message' => "Logged out",
            ]);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }


    // Admin get all applicants
    public function showAllApplicant(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {
            $applicants = [
                'wait_list' => "",
                'verified' => "",
                'declined' => "",
            ];

            $wait = Applicant::where('isApproved', '=', '1')->orderBy('created_at', 'desc')->get();
            $verified = Applicant::where('isApproved', '=', '2')->orderBy('created_at', 'desc')->get();
            $unverified = Applicant::where('isApproved', '=', '3')->orderBy('created_at', 'desc')->get();

            $applicants['wait_list'] = $wait;
            $applicants['verified'] = $verified;
            $applicants['declined'] = $unverified;

            return response()->json([
                'status' => true,
                'data' => [
                    'applicants' => $applicants
                ],
            ]);

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    // Admin get all applicants
    public function acceptApplicant(Request $request)
    {
        if ($request->user()->tokenCan('Admin')) {
            $validator = Validator::make($request->all(), [
                'applicant_id' => 'required',
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $pass = mt_rand(10000000,99999999);

            $password = Hash::make($pass);

            $isApproved = $request->status;

            $user = Applicant::where(['id'=>$request->applicant_id])->update([
                'password'=>$password,
                'isApproved'=>$isApproved,
            ]);

            if ($user) {
                $applicant = Applicant::find($request->applicant_id);

                // return $pass;
                if ($request->status == "2") {
                    $mailData = [
                        'title' => 'Your registration approved by an Administrator.',
                        // 'body' => "Use your Username or Email and ".$pass." as your password \n\nThank you...",
                        'body'=>"Congratulations! Your registration has been approved.
                        \n\nYour username: $applicant->email or $applicant->username
                        \nYour password: $pass
                        \n\nIf you have any questions or need assistance, feel free to reach out to our team.
                        \n\nBest regards,",
                    ];
                    Mail::to($applicant->email)->send(new MessageNotificationMail($mailData));
                }
                if ($request->status == "3") {
                    $mailData = [
                        'title' => 'Account declined by an Administrator.',
                        'body' => "We regret to inform you that your registration has been rejected due to your documents not being up to date. If you have updated documents, you may resubmit your application for further consideration. If you need any clarification or assistance, please don't hesitate to contact our team. Thank you for your interest, and we appreciate your understanding. Best regards,",
                        // 'body' => "Sorry, Your Account has been declined due to some reasons. \n\nThank you...",
                    ];
                    Mail::to($applicant->email)->send(new MessageNotificationMail($mailData));
                }

                return response()->json([
                    'status' => true,
                    'data' => [
                        'applicant' => $applicant
                    ],
                    'message' => "An email has been sent to the applicant to notify them about this action."
                ]);
            }else {
                return response()->json([
                    'status' => false,
                    'message' => "Failde to update Applicant record, Try again..."
                ], 404);
            }

        }else{
            return response()->json([
                'status' => false,
                'message' => trans('auth.failed')
            ], 404);
        }
    }

    public function get_info()
    {
        $apps = Application::where("status", "=", "5")->get();

        $al = [];

        foreach ($apps as $key => $a) {
            if (count($a->app_document)>12) {
                array_push($al, $a->id);
            }
        }

        return $al;
    }
}
