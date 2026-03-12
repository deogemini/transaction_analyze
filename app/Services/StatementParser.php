<?php

namespace App\Services;

use App\Models\Statement;
use App\Models\Transaction;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StatementParser
{
    protected $pdfParser;

    public function __construct()
    {
        $this->pdfParser = new Parser();
    }

    public function parse(Statement $statement)
    {
        $filePath = storage_path('app/private/' . $statement->file_path);

        if (!file_exists($filePath)) {
            $statement->update(['status' => 'failed']);
            return;
        }

        try {
            if (str_ends_with($statement->file_name, '.pdf')) {
                $this->parsePdf($statement, $filePath);
            }
            // Add CSV/Excel parsing later

            $statement->update(['status' => 'processed']);
            $this->calculateTotals($statement);
        } catch (\Exception $e) {
            \Log::error("Error parsing statement: " . $e->getMessage());
            $statement->update(['status' => 'failed']);
        }
    }

    protected function parsePdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // Updated regex to be more flexible with spaces and handle potential charge detection
            if (preg_match('/(\d{2}-\d{2}-\d{4} \d{2}:\d{2}(?:AM|PM))\s+(.*?)\s+(.*?)\s+([\d,.]+)\s+([\d,.]+)\s+(.*)/i', trim($line), $matches)) {
                $dateStr = $matches[1];
                $from = $matches[2];
                $to = $matches[3];
                $amount = (float) str_replace(',', '', $matches[4]);
                $balance = (float) str_replace(',', '', $matches[5]);
                $description = trim($matches[6]);

                $isChargeRow = $this->isChargeRow($description);
                $type = $this->determineType($from, $to, $description);
                $category = $this->categorize($description);

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => Carbon::createFromFormat('d-m-Y h:iA', $dateStr),
                    'from' => $from,
                    'to' => $to,
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $category,
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function isChargeRow($description)
    {
        $chargeKeywords = ['Transaction Charge', 'M-Pesa Charge', 'Withdrawal Charge', 'Transfer Fee', 'Sms Alert Fee', 'Service Fee'];
        foreach ($chargeKeywords as $keyword) {
            if (stripos($description, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function determineType($from, $to, $description)
    {
        // Credits: Deposit, Received, etc.
        $creditKeywords = ['Deposit', 'Received', 'Money received', 'Reversal'];
        foreach ($creditKeywords as $keyword) {
            if (stripos($description, $keyword) !== false) {
                return 'credit';
            }
        }
        return 'debit';
    }

    protected function categorize($description)
    {
        if (stripos($description, 'Pay Bill') !== false || stripos($description, 'Bill Payment') !== false) return 'bill';
        if (stripos($description, 'Deposit') !== false) return 'deposit';
        if (stripos($description, 'Withdraw') !== false) return 'withdrawal';
        if (stripos($description, 'Airtime') !== false) return 'airtime';
        if (stripos($description, 'Buy Goods') !== false || stripos($description, 'Lipa na M-Pesa') !== false) return 'merchant';
        if (stripos($description, 'Send Money') !== false || stripos($description, 'Transfer') !== false) return 'transfer';
        if ($this->isChargeRow($description)) return 'charge';

        return 'other';
    }

    protected function calculateTotals(Statement $statement)
    {
        $statement->total_debits = $statement->transactions()->where('type', 'debit')->sum('amount');
        $statement->total_credits = $statement->transactions()->where('type', 'credit')->sum('amount');
        $statement->total_charges = $statement->transactions()->sum('charge');
        $statement->save();
    }
}
