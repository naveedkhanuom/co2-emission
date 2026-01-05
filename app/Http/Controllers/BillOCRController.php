<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Bill;

class BillOCRController extends Controller
{
    public function showForm()
    {
        return view('bill_upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'bill_file' => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $file = $request->file('bill_file');

        // Send to OCR.space
        $response = Http::withHeaders([
            'apikey' => env('OCR_SPACE_API_KEY')
        ])->attach(
            'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
        )->post('https://api.ocr.space/parse/image', [
            'language' => 'eng',
            'isOverlayRequired' => 'false',
        ]);

        $result = $response->json();

        if (!isset($result['ParsedResults'][0]['ParsedText'])) {
            return back()->withErrors(['bill_file' => 'OCR failed, please try again.']);
        }

        $text = $result['ParsedResults'][0]['ParsedText'];

        // Extract data using regex
        preg_match('/Invoice:\s*(\d+)/', $text, $invoice);
        preg_match('/Issue Date:(\d{2}\/\d{2}\/\d{4})/', $text, $issue_date);
        preg_match('/Month:\s*([A-Za-z]+\s\d{4})/', $text, $month);
        preg_match('/Period:\s*([\d\/]+\s*to\s*[\d\/]+)/', $text, $period);
        preg_match('/Account Number\s*(\d+)/', $text, $account);
        preg_match('/Meter number\(s\):\s*([\w\d]+)/', $text, $meter);
        preg_match('/Current reading:\s*(\d+)/', $text, $current);
        preg_match('/Previous reading:\s*(\d+)/', $text, $previous);
        preg_match('/Consumption\s*([\d,]+\s*kWh)/', $text, $consumption);
        preg_match('/Rate\s*([\d.]+AED)/', $text, $rate);
        preg_match('/Sub total\s*([\d.]+)/', $text, $sub_total);
        preg_match('/VAT.*?([\d.]+)/', $text, $vat);
        preg_match('/Total\s*([\d.]+)/', $text, $total);
dd($consumption[1]);
        // Save to DB
        $bill = Bill::create([
            'invoice_no' => $invoice[1] ?? null,
            'issue_date' => isset($issue_date[1]) ? date('Y-m-d', strtotime($issue_date[1])) : null,
            'month' => $month[1] ?? null,
            'period' => $period[1] ?? null,
            'account_no' => $account[1] ?? null,
            'meter_no' => $meter[1] ?? null,
            'current_reading' => $current[1] ?? null,
            'previous_reading' => $previous[1] ?? null,
            'consumption' => $consumption[1] ?? null,
            'rate' => $rate[1] ?? null,
            'sub_total' => $sub_total[1] ?? null,
            'vat' => $vat[1] ?? null,
            'total' => $total[1] ?? null,
        ]);

        return redirect()->back()->with('success', 'Bill processed and saved! Invoice No: ' . $bill->invoice_no);
    }
}
