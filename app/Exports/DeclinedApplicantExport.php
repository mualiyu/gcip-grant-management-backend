<?php

namespace App\Exports;

use App\Models\Applicant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DeclinedApplicantExport implements FromView
{
    public function view(): View
    {
        return view('exports.applicant_dec', [
            'applicants' => Applicant::where("isApproved", "=", "3")->get(),
        ]);
    }
}
