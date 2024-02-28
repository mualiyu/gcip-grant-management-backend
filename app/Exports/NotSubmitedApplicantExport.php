<?php

namespace App\Exports;

use App\Models\Applicant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class NotSubmitedApplicantExport implements FromView
{
    public function view(): View
    {
        return view('exports.applicant_not', [
            'applications' => Applicant::where("isApproved", "=", "2")->get(),
        ]);
    }
}
