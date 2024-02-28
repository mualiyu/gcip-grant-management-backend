<?php

namespace App\Exports;

use App\Models\Application;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ApplicationsExport implements FromView
{
    public $type;
    /**
     * Class constructor.
     */
    public function __construct($type=5)
    {
        $this->type = $type;
    }

    public function view(): View
    {
        return view('exports.applications', [
            'applications' => Application::where('status', '=', $this->type)->get(),
        ]);
    }
}
