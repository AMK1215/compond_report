<?php

namespace App\Http\Controllers\Admin\Shan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShanReportTransactionController extends Controller
{
    private $apiUrl = 'https://luckymillion.pro/api/report-transactions';

    /**
     * Display the Shan Report Transaction interface
     */
    public function index()
    {
        return view('admin.shan.report_transaction.index');
    }

    /**
     * Fetch report transactions from external API
     */
    public function fetchReportTransactions(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'agent_code' => 'required|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'member_account' => 'nullable|string',
                'group_by' => 'nullable|in:agent_id,member_account,both',
            ]);

            $params = [
                'agent_code' => $request->input('agent_code'),
            ];

            // Add optional parameters
            if ($request->filled('date_from')) {
                $params['date_from'] = $request->input('date_from');
            }
            if ($request->filled('date_to')) {
                $params['date_to'] = $request->input('date_to');
            }
            if ($request->filled('member_account')) {
                $params['member_account'] = $request->input('member_account');
            }
            if ($request->filled('group_by')) {
                $params['group_by'] = $request->input('group_by');
            }

            Log::info('ShanReportTransaction: Calling external API', [
                'url' => $this->apiUrl,
                'params' => $params
            ]);

            // Call the external API
            $response = Http::timeout(30)->post($this->apiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('ShanReportTransaction: API response received', [
                    'status' => $data['status'] ?? 'unknown',
                    'data_count' => count($data['data']['report_data'] ?? [])
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            } else {
                Log::error('ShanReportTransaction: API call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch data from external API. Status: ' . $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('ShanReportTransaction: Error occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get member transactions from external API
     */
    public function fetchMemberTransactions(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'agent_code' => 'required|string',
                'member_account' => 'required|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $params = [
                'agent_code' => $request->input('agent_code'),
                'member_account' => $request->input('member_account'),
            ];

            // Add optional parameters
            if ($request->filled('date_from')) {
                $params['date_from'] = $request->input('date_from');
            }
            if ($request->filled('date_to')) {
                $params['date_to'] = $request->input('date_to');
            }
            if ($request->filled('limit')) {
                $params['limit'] = $request->input('limit');
            }

            Log::info('ShanReportTransaction: Calling member transactions API', [
                'params' => $params
            ]);

            // Call the external API for member transactions
            $memberApiUrl = 'https://luckymillion.pro/api/member-transactions';
            $response = Http::timeout(30)->post($memberApiUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('ShanReportTransaction: Member API response received', [
                    'status' => $data['status'] ?? 'unknown',
                    'transactions_count' => count($data['data']['transactions'] ?? [])
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => $data['data'] ?? $data, // Handle both nested and direct response
                    'status' => $data['status'] ?? 'Request was successful.',
                    'message' => $data['message'] ?? 'Member transactions retrieved successfully'
                ]);
            } else {
                Log::error('ShanReportTransaction: Member API call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch member data from external API. Status: ' . $response->status()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('ShanReportTransaction: Error in member transactions', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the request: ' . $e->getMessage()
            ], 500);
        }
    }
}