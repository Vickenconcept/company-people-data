<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEmailAutomation;
use App\Jobs\ProcessLeadDiscovery;
use App\Models\LeadRequest;
use App\Models\LeadResult;
use App\Models\QueuedEmail;
use App\Services\EmailAutomationService;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadGenController extends Controller
{
    /**
     * Create a new lead generation request
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference_company_name' => 'required|string|max:255',
            'reference_company_url' => 'nullable|url|max:500',
            'target_count' => 'nullable|integer|min:1|max:100',
            'target_job_titles' => 'nullable|array',
            'target_job_titles.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $leadRequest = LeadRequest::create([
            'user_id' => $request->user()->id,
            'reference_company_name' => $request->reference_company_name,
            'reference_company_url' => $request->reference_company_url,
            'target_count' => $request->target_count ?? 10,
            'target_job_titles' => $request->target_job_titles ?? ['CEO', 'CFO'],
            'status' => 'pending',
        ]);

        // Dispatch job to process lead discovery
        ProcessLeadDiscovery::dispatch($leadRequest);

        return response()->json([
            'success' => true,
            'message' => 'Lead generation request created successfully',
            'data' => [
                'id' => $leadRequest->id,
                'status' => $leadRequest->status,
            ],
        ], 201);
    }

    /**
     * Get all lead requests for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $leadRequests = LeadRequest::where('user_id', $request->user()->id)
            ->with(['leadResults.company', 'leadResults.person'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $leadRequests,
        ]);
    }

    /**
     * Get a specific lead request
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $leadRequest = LeadRequest::where('user_id', $request->user()->id)
            ->with(['leadResults.company', 'leadResults.person'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $leadRequest,
        ]);
    }

    /**
     * Get lead results for a specific request
     */
    public function results(Request $request, int $id): JsonResponse
    {
        $leadRequest = LeadRequest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $results = $leadRequest->leadResults()
            ->with(['company', 'person', 'queuedEmails'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Generate email content for a lead result with optional custom context
     */
    public function generateEmail(Request $request, int $leadResultId): JsonResponse
    {
        $leadResult = LeadResult::whereHas('leadRequest', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->with(['person', 'company', 'leadRequest.user'])
            ->findOrFail($leadResultId);

        if (!$leadResult->person || !$leadResult->person->email) {
            return response()->json([
                'success' => false,
                'error' => 'No email address found for this contact',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'custom_context' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $openAIService = new OpenAIService();
        $openAIService->setApiKeyFromUser($leadResult->leadRequest->user);

        $sender = $leadResult->leadRequest->user;
        $senderData = [
            'name' => $sender?->name ?? '',
            'email' => $sender?->email ?? '',
            'company_name' => config('app.name', 'Company'),
            'from_name' => config('mail.from.name'),
            'from_address' => config('mail.from.address'),
        ];

        $emailResult = $openAIService->generateEmailContent(
            $leadResult->person->toArray(),
            $leadResult->company->toArray(),
            $request->input('custom_context'),
            $senderData
        );

        if (!$emailResult['success']) {
            return response()->json([
                'success' => false,
                'error' => $emailResult['error'] ?? 'Failed to generate email',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => $emailResult['subject'],
                'body' => $emailResult['body'],
                'lead_result_id' => $leadResult->id,
                'person_email' => $leadResult->person->email,
                'person_name' => $leadResult->person->full_name,
            ],
        ]);
    }

    /**
     * Generate emails for multiple lead results with optional custom context
     */
    public function generateBulkEmails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_result_ids' => 'required|array|min:1',
            'lead_result_ids.*' => 'required|integer|exists:lead_results,id',
            'custom_context' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $leadResults = LeadResult::whereHas('leadRequest', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->whereIn('id', $request->lead_result_ids)
            ->with(['person', 'company', 'leadRequest.user'])
            ->get();

        if ($leadResults->count() !== count($request->lead_result_ids)) {
            return response()->json([
                'success' => false,
                'error' => 'Some lead results not found or access denied',
            ], 403);
        }

        $openAIService = new OpenAIService();
        $generatedEmails = [];

        foreach ($leadResults as $leadResult) {
            if (!$leadResult->person || !$leadResult->person->email) {
                continue;
            }

            $openAIService->setApiKeyFromUser($leadResult->leadRequest->user);

            $sender = $leadResult->leadRequest->user;
            $senderData = [
                'name' => $sender?->name ?? '',
                'email' => $sender?->email ?? '',
                'company_name' => config('app.name', 'Company'),
                'from_name' => config('mail.from.name'),
                'from_address' => config('mail.from.address'),
            ];

            $emailResult = $openAIService->generateEmailContent(
                $leadResult->person->toArray(),
                $leadResult->company->toArray(),
                $request->input('custom_context'),
                $senderData
            );

            if ($emailResult['success']) {
                $generatedEmails[] = [
                    'lead_result_id' => $leadResult->id,
                    'person_email' => $leadResult->person->email,
                    'person_name' => $leadResult->person->full_name,
                    'company_name' => $leadResult->company->name,
                    'subject' => $emailResult['subject'],
                    'body' => $emailResult['body'],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'generated_count' => count($generatedEmails),
                'emails' => $generatedEmails,
            ],
        ]);
    }

    /**
     * Queue email automation for selected lead results
     */
    public function queueEmails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_result_ids' => 'required|array|min:1',
            'lead_result_ids.*' => 'required|integer|exists:lead_results,id',
            'emails' => 'required|array',
            'emails.*.lead_result_id' => 'required|integer|exists:lead_results,id',
            'emails.*.subject' => 'required|string|max:500',
            'emails.*.body' => 'required|string|max:10000',
            'custom_context' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $leadResults = LeadResult::whereHas('leadRequest', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->whereIn('id', $request->lead_result_ids)
            ->with(['person', 'company', 'leadRequest.user'])
            ->get();

        if ($leadResults->count() !== count($request->lead_result_ids)) {
            return response()->json([
                'success' => false,
                'error' => 'Some lead results not found or access denied',
            ], 403);
        }

        $queuedEmails = [];
        $emailAutomationService = new EmailAutomationService();

        foreach ($leadResults as $leadResult) {
            if (!$leadResult->person || !$leadResult->person->email) {
                continue;
            }

            // Find matching email data from request
            $emailData = collect($request->emails)->firstWhere('lead_result_id', $leadResult->id);
            
            if (!$emailData) {
                continue;
            }

            // Create queued email record
            $queuedEmail = $emailAutomationService->queueEmail(
                $leadResult->person,
                $emailData['subject'],
                $emailData['body'],
                $leadResult->id
            );

            // Store custom context in metadata if provided
            if ($request->has('custom_context')) {
                $queuedEmail->update([
                    'metadata' => ['custom_context' => $request->custom_context],
                ]);
            }

            // Queue the email automation job
            ProcessEmailAutomation::dispatch($leadResult);

            $queuedEmails[] = $queuedEmail;
        }

        return response()->json([
            'success' => true,
            'message' => count($queuedEmails) . ' email(s) queued successfully',
            'data' => [
                'queued_count' => count($queuedEmails),
                'queued_emails' => $queuedEmails,
            ],
        ]);
    }

    /**
     * Get queued emails for a lead request
     */
    public function queuedEmails(Request $request, int $leadRequestId): JsonResponse
    {
        $leadRequest = LeadRequest::where('user_id', $request->user()->id)
            ->findOrFail($leadRequestId);

        $queuedEmails = QueuedEmail::whereHas('leadResult', function ($query) use ($leadRequestId) {
            $query->where('lead_request_id', $leadRequestId);
        })
            ->with(['leadResult.company', 'leadResult.person'])
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $queuedEmails,
        ]);
    }
}
