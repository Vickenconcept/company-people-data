<?php

namespace App\Http\Controllers;

use App\Models\LeadRequest;
use App\Models\LeadResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function exportLeads(Request $request)
    {
        $leadRequests = LeadRequest::where('user_id', $request->user()->id)
            ->with(['leadResults.company', 'leadResults.person'])
            ->get();

        $filename = 'leads_export_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($leadRequests) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Lead Request ID',
                'Reference Company',
                'Status',
                'Company Name',
                'Company Industry',
                'Contact Name',
                'Contact Title',
                'Contact Email',
                'Contact Phone',
                'Lead Status',
                'Created At'
            ]);

            // Data rows
            foreach ($leadRequests as $leadRequest) {
                if ($leadRequest->leadResults->count() > 0) {
                    foreach ($leadRequest->leadResults as $leadResult) {
                        fputcsv($file, [
                            $leadRequest->id,
                            $leadRequest->reference_company_name,
                            $leadRequest->status,
                            $leadResult->company->name ?? '',
                            $leadResult->company->industry ?? '',
                            $leadResult->person->full_name ?? '',
                            $leadResult->person->title ?? '',
                            $leadResult->person->email ?? '',
                            $leadResult->person->phone ?? '',
                            $leadResult->status,
                            $leadResult->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                } else {
                    // If no results, still export the lead request
                    fputcsv($file, [
                        $leadRequest->id,
                        $leadRequest->reference_company_name,
                        $leadRequest->status,
                        '', '', '', '', '', '', '',
                        $leadRequest->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
