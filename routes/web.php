<?php

use App\Exports\ApplicationsExport;
use App\Exports\DeclinedApplicantExport;
use App\Exports\NotSubmitedApplicantExport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/*', function () {
    // return view('welcome');
    return response()->json([
                'status' => false,
                'message' => "This endpoint is not valid"
            ], 404);
});

Route::get('/tes/email-view', function () {
    return view('mail.submitApplicationNotification');
});


Route::get('storage/{p}/{filename}', function ($p, $filename)
{
    $path = storage_path('app/public/'.$p.'/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

Route::get('/send-email-to-applicants/{dd}',  [\App\Http\Controllers\ApplicationController::class, 'send_email_to_applicants']);

Route::get('/send-email-to-applicants-rfg',  [\App\Http\Controllers\ApplicationController::class, 'send_email_rfg']);

Route::get('/get-info',  [\App\Http\Controllers\ApplicantController::class, 'get_info']);

Route::get('/get-excel-submited/{d}', function ($d) {
    return Excel::download(new ApplicationsExport($d), 'Submited Applicants.xlsx');
});

Route::get('/get-excel-not-submited', function () {
    return Excel::download(new NotSubmitedApplicantExport, 'Not Submited Applicants.xlsx');
});

Route::get('/get-excel-declined-applicants', function () {
    return Excel::download(new DeclinedApplicantExport, 'Declined Applicants.xlsx');
});
