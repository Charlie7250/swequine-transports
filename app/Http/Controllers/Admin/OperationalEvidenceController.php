<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReleaseOneOperationalEvidenceBuilder;
use Illuminate\View\View;

class OperationalEvidenceController extends Controller
{
    public function __invoke(ReleaseOneOperationalEvidenceBuilder $builder): View
    {
        return view('admin.operational-evidence.index', [
            'operationalEvidence' => $builder->build(),
        ]);
    }
}
