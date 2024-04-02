<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/user', function (Request $request) {
    // return $request->user();
    return view('mail.MessageNotification');
});

# Admins
Route::prefix('admin')->group(function () {

    Route::get('download/applicationDocuments', [\App\Http\Controllers\ApplicationController::class, 'downloadApplicationDocuments']);

    // Route::get('download/applicationDocument', [\App\Http\Controllers\ApplicationController::class, 'downloadApplicationDocument']);

    # register
    Route::post('register', [\App\Http\Controllers\UserController::class, 'register']);

    # verify
    Route::post('verify', [\App\Http\Controllers\UserController::class, 'verify']);

    # login
    Route::post('login', [\App\Http\Controllers\UserController::class, 'login']);

    # logout
    Route::middleware('auth:sanctum')->post('logout', [\App\Http\Controllers\UserController::class, 'logout']);

    # recover
    Route::post('recover', [\App\Http\Controllers\UserController::class, 'recover']);

    # reset
    Route::post('reset', [\App\Http\Controllers\UserController::class, 'reset']);

    #get Admin profile
    Route::middleware('auth:sanctum')->get('profile', [\App\Http\Controllers\UserController::class, 'user']);

    // programs
    Route::middleware('auth:sanctum')->prefix('program')->group(function () {
        # store
        Route::post('create', [\App\Http\Controllers\ProgramController::class, 'create']);

        # upload
        Route::post('file/upload', [\App\Http\Controllers\ProgramController::class, 'upload']);

        # get
        Route::get('list', [\App\Http\Controllers\ProgramController::class, 'showAll']);

        # get
        Route::get('info', [\App\Http\Controllers\ProgramController::class, 'showObj']);
        // Route::get('info/v2', [\App\Http\Controllers\ProgramController::class, 'showObj']);

        // Applications Not done yet
        Route::middleware('auth:sanctum')->prefix('applications')->group(function () {
            #Get application
            Route::get('getAll', [\App\Http\Controllers\ProgramController::class, 'getApplications']);

            Route::get('getOne', [\App\Http\Controllers\ProgramController::class, 'getSingleApplication']);

            Route::post('make-decision', [\App\Http\Controllers\ApplicationController::class, 'make_decision']);

            // Route::get('download/applicationDocuments', [\App\Http\Controllers\ApplicationController::class, 'downloadApplicationDocuments']);
        });
    });

    // Applicant
    Route::middleware('auth:sanctum')->prefix('applicants')->group(function () {
        // # store
        // Route::post('create', [\App\Http\Controllers\CategoryController::class, 'create']);

        # get list
        Route::get('list', [\App\Http\Controllers\ApplicantController::class, 'showAllApplicant']);
        Route::post('accept', [\App\Http\Controllers\ApplicantController::class, 'acceptApplicant']);
    });

    // Messages
    Route::middleware('auth:sanctum')->prefix('messages')->group(function () {
        # get
        Route::get('{program}', [\App\Http\Controllers\MessageController::class, 'getAll']);

        # send
        Route::post('{program}', [\App\Http\Controllers\MessageController::class, 'adminSend']);

        #update status to read
        Route::post('read/{program}/{applicant}', [\App\Http\Controllers\MessageController::class, 'adminReadMsg']);

        Route::get('get-unread/{program}', [\App\Http\Controllers\MessageController::class, 'adminGetUnreadMsg']);
    });

    // projects
    Route::middleware('auth:sanctum')->prefix('projects')->group(function () {
        # get all
        Route::get('{program}', [\App\Http\Controllers\ProjectController::class, 'getAll']);

        # get single
        Route::get('{program}/{project}', [\App\Http\Controllers\ProjectController::class, 'getOne']);

        #update status to read
        Route::post('{program}/create', [\App\Http\Controllers\ProjectController::class, 'create']);

        // update project
        Route::post('{program}/update/{project}', [\App\Http\Controllers\ProjectController::class, 'update']);
        Route::post('{program}/update/{project}/requirement', [\App\Http\Controllers\ProjectController::class, 'update_r']);
        Route::post('{program}/update/{project}/document', [\App\Http\Controllers\ProjectController::class, 'update_d']);

        // delete project
        Route::post('{program}/delete/{project}', [\App\Http\Controllers\ProjectController::class, 'delete']);

        Route::post('file/upload', [\App\Http\Controllers\ProjectController::class, 'upload']);


        #allocate project to applicant
        Route::post('allocate-project-to-applicant', [\App\Http\Controllers\ProjectController::class, 'allocate']);
        #remove project from applicant
        Route::post('remove-project-from-applicant', [\App\Http\Controllers\ProjectController::class, 'misallocate']);
    });

    Route::middleware('auth:sanctum')->get('proposals/{program}', [\App\Http\Controllers\ProjectController::class, 'getAllProposals']);
    Route::middleware('auth:sanctum')->get('proposals/{program}/{applicantproposal}', [\App\Http\Controllers\ProjectController::class, 'getOneProposal']);
    Route::get('proposals/{program}/{applicantproposal}/downloadzip', [\App\Http\Controllers\ProjectController::class, 'downloadProposalDocs']);
});





# Applicants
Route::prefix('applicant')->group(function () {
    # register
    Route::post('register', [\App\Http\Controllers\ApplicantController::class, 'register']);
    Route::post('registerUpload', [\App\Http\Controllers\ApplicantController::class, 'registerUpload']);

    # verify
    Route::post('verify', [\App\Http\Controllers\ApplicantController::class, 'verify']);

    # login
    Route::post('login', [\App\Http\Controllers\ApplicantController::class, 'login']);

    # logout
    Route::middleware('auth:sanctum')->post('logout', [\App\Http\Controllers\ApplicantController::class, 'logout']);

    # recover
    Route::post('recover', [\App\Http\Controllers\ApplicantController::class, 'recover']);

    # reset
    Route::middleware('auth:sanctum')->post('reset', [\App\Http\Controllers\ApplicantController::class, 'reset']);

    Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
        Route::get('', [\App\Http\Controllers\ApplicantController::class, 'user']);

        Route::post('add/jv', [\App\Http\Controllers\ApplicantController::class, 'addJv']);
        Route::post('jv/upload', [\App\Http\Controllers\ApplicantController::class, 'uploadJv']);

        Route::post('update/jv/{id}', [\App\Http\Controllers\ApplicantController::class, 'updateJv']);

        Route::post('update', [\App\Http\Controllers\ApplicantController::class, 'updateProfile']);
    });


    // programs
    Route::middleware('auth:sanctum')->prefix('program')->group(function () {

        # get
        Route::get('list', [\App\Http\Controllers\ProgramController::class, 'showAll']);

        # get
        Route::get('info', [\App\Http\Controllers\ProgramController::class, 'showObj']);
        // Route::get('info/v2', [\App\Http\Controllers\ProgramController::class, 'showObj']);

    });


    // Application
    Route::middleware('auth:sanctum')->prefix('application')->group(function () {
        # initial
        Route::post('create/initial', [\App\Http\Controllers\ApplicationController::class, 'createInitial']);

        # add profile
        Route::post('create/eligibility_criteria', [\App\Http\Controllers\ApplicationController::class, 'createEligibility_criteria']);
        Route::post('update/eligibility_criteria/{applicationEligibility}', [\App\Http\Controllers\ApplicationController::class, 'updateEligibility_criteria']);

        // # Upload documents
        Route::post('create/documents', [\App\Http\Controllers\ApplicationController::class, 'createDocument']);
        Route::post('create/documents/upload', [\App\Http\Controllers\ApplicationController::class, 'uploadDocument']);

        # add Staff
        Route::post('create/company_info', [\App\Http\Controllers\ApplicationController::class, 'createCompanyInfo']);
        Route::post('update/company_info/{applicationCompanyInfo}', [\App\Http\Controllers\ApplicationController::class, 'updateCompanyInfo']);
        Route::post('create/company_info/upload', [\App\Http\Controllers\ApplicationController::class, 'uploadCompanyInfo']);

        # add Reference Projects
        Route::post('create/business_proposal', [\App\Http\Controllers\ApplicationController::class, 'createBusinessProposal']);
        Route::post('update/business_proposal/{applicationBusinessProposal}', [\App\Http\Controllers\ApplicationController::class, 'updateBusinessProposal']);
        Route::post('create/business_proposal/upload', [\App\Http\Controllers\ApplicationController::class, 'uploadBusinessPro']);

        # submit
        Route::post('submit', [\App\Http\Controllers\ApplicationController::class, 'submit']);

        #Get application
        Route::get('get', [\App\Http\Controllers\ApplicationController::class, 'getApplication']);

        // ---------------------------------------------


        // Route::post('accept/pre-qualification', [\App\Http\Controllers\ApplicationController::class, 'pre_qualification']);


        #Get application progress
        Route::get('get-progress', [\App\Http\Controllers\ApplicationController::class, 'getApplicationProgress']);
    });

    // Messages
    Route::middleware('auth:sanctum')->prefix('messages')->group(function () {
        # get
        Route::get('{program}', [\App\Http\Controllers\MessageController::class, 'applicantGetAll']);

        # send
        Route::post('{program}', [\App\Http\Controllers\MessageController::class, 'applicantSend']);

        #update read status
        Route::post('read/{program}', [\App\Http\Controllers\MessageController::class, 'applicantReadMsg']);

        # gt unread
        Route::get('get-unread/{program}', [\App\Http\Controllers\MessageController::class, 'applicantGetUnreadMsg']);
    });

    // projects
    Route::middleware('auth:sanctum')->prefix('projects')->group(function () {
        # get all
        Route::get('', [\App\Http\Controllers\ProjectController::class, 'appGetAll']);

        # get single
        Route::get('{id}', [\App\Http\Controllers\ProjectController::class, 'appGetOne']);

        #update status to read
        Route::post('file/upload', [\App\Http\Controllers\ProjectController::class, 'appUpload']);

        // submiting of documents for proposal
        Route::post('submit-requirement', [\App\Http\Controllers\ProjectController::class, 'appSubmit']);

        // delete documents from proposal
        Route::post('remove-document', [\App\Http\Controllers\ProjectController::class, 'appDeleteDocument']);

        Route::get('review/proposal', [\App\Http\Controllers\ProjectController::class, 'appReview']);

        Route::post('submit-proposal', [\App\Http\Controllers\ProjectController::class, 'appSubmitProposal']);
    });
});
