<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Services\FinancialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Response;

class PaymentImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.receipt'),
        ];
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sample_payments_import.csv"',
        ];

        $columns = ['reference_type', 'reference_no', 'amount', 'payment_method', 'transaction_id', 'payment_date'];
        $sampleData = [
            ['invoice', 'INV-17072026-01', '1500.00', 'bank_transfer', 'TXN-001', '2026-07-17 10:30:00'],
            ['order', 'ORD-17072026-02', '500.50', 'credit_card', 'CH-12345', '2026-07-18 14:15:00'],
        ];

        $callback = function () use ($columns, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $data = array_map('str_getcsv', file($path));

        if (count($data) < 2) {
            return response()->json(['success' => false, 'message' => 'The CSV file is empty or missing headers.'], 400);
        }

        $headers = array_shift($data);
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        $expectedHeaders = ['reference_type', 'reference_no', 'amount', 'payment_method', 'transaction_id', 'payment_date'];

        // Find indexes
        $indexes = [];
        foreach ($expectedHeaders as $header) {
            $index = array_search($header, $headers);
            $indexes[$header] = $index !== false ? $index : -1;
        }

        if ($indexes['reference_type'] === -1 || $indexes['reference_no'] === -1 || $indexes['amount'] === -1) {
            return response()->json(['success' => false, 'message' => 'Missing required headers: reference_type, reference_no, amount.'], 400);
        }

        $previewData = [];
        $errors = [];

        foreach ($data as $rowIndex => $row) {
            if (empty(array_filter($row))) {
                continue;
            } // Skip empty rows

            $rowNum = $rowIndex + 2;

            $refType = isset($row[$indexes['reference_type']]) ? trim($row[$indexes['reference_type']]) : '';
            $refNo = isset($row[$indexes['reference_no']]) ? trim($row[$indexes['reference_no']]) : '';
            $amount = isset($row[$indexes['amount']]) ? trim($row[$indexes['amount']]) : 0;

            $methodVal = $indexes['payment_method'] !== -1 && isset($row[$indexes['payment_method']]) ? trim($row[$indexes['payment_method']]) : '';
            $method = $methodVal !== '' ? $methodVal : 'bank_transfer';

            $txnVal = $indexes['transaction_id'] !== -1 && isset($row[$indexes['transaction_id']]) ? trim($row[$indexes['transaction_id']]) : '';

            $dateVal = $indexes['payment_date'] !== -1 && isset($row[$indexes['payment_date']]) ? trim($row[$indexes['payment_date']]) : '';
            $date = $dateVal !== '' ? $dateVal : now()->toDateTimeString();

            if (empty($refType) || empty($refNo) || empty($amount)) {
                $errors[] = "Row {$rowNum}: Missing reference_type, reference_no, or amount.";

                continue;
            }

            if (! is_numeric($amount) || $amount <= 0) {
                $errors[] = "Row {$rowNum}: Invalid amount ({$amount}).";

                continue;
            }

            $invoiceId = null;
            $orderId = null;
            $dueAmount = 0;

            if (strtolower($refType) === 'invoice') {
                $invoice = Invoice::where('invoice_no', $refNo)->first();
                if (! $invoice) {
                    $errors[] = "Row {$rowNum}: Invoice not found ({$refNo}).";

                    continue;
                }
                $invoiceId = $invoice->id;
                $orderId = $invoice->order_id;
                $dueAmount = $invoice->due_amount;
            } elseif (strtolower($refType) === 'order') {
                $order = Order::where('order_no', $refNo)->first();
                if (! $order) {
                    $errors[] = "Row {$rowNum}: Order not found ({$refNo}).";

                    continue;
                }
                if (! $order->invoice) {
                    $errors[] = "Row {$rowNum}: Order has no associated invoice ({$refNo}).";

                    continue;
                }
                $invoiceId = $order->invoice->id;
                $orderId = $order->id;
                $dueAmount = $order->invoice->due_amount;
            } else {
                $errors[] = "Row {$rowNum}: Invalid reference_type. Must be 'invoice' or 'order'.";

                continue;
            }

            if ((float) $amount > $dueAmount) {
                $errors[] = "Row {$rowNum}: Payment amount (".$amount.') exceeds due amount ('.$dueAmount.').';

                continue;
            }

            try {
                $parsedDate = Carbon::parse($date)->toDateTimeString();
            } catch (\Exception $e) {
                $parsedDate = now()->toDateTimeString();
            }

            $previewData[] = [
                'row' => $rowNum,
                'reference_type' => $refType,
                'reference_no' => $refNo,
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
                'amount' => (float) $amount,
                'due_amount' => $dueAmount,
                'payment_method' => strtolower($method),
                'transaction_id' => $txnVal,
                'payment_date' => $parsedDate,
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $previewData,
            'errors' => $errors,
        ]);
    }

    public function process(Request $request, FinancialService $financialService)
    {
        $validated = $request->validate([
            'payments' => 'required|array',
            'payments.*.invoice_id' => 'required|exists:invoices,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.payment_method' => 'required|string',
            'payments.*.transaction_id' => 'nullable|string',
            'payments.*.payment_date' => 'nullable|date',
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($validated['payments'] as $index => $paymentData) {
            try {
                $invoice = Invoice::findOrFail($paymentData['invoice_id']);
                $financialService->processPayment(
                    $invoice,
                    (float) $paymentData['amount'],
                    $paymentData['payment_method'],
                    $paymentData['transaction_id'] ?? null,
                    $paymentData['payment_date'] ?? null
                );
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = 'Row '.($index + 1).' failed: '.$e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Import completed. {$successCount} successful, {$failedCount} failed.",
            'errors' => $errors,
        ]);
    }
}
