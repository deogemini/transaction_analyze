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
                if ($statement->provider === 'Selcom Pesa') {
                    $this->parseSelcomPdf($statement, $filePath);
                } elseif ($statement->provider === 'NBC Bank') {
                    $this->parseNbcPdf($statement, $filePath);
                } elseif ($statement->provider === 'CRDB Bank') {
                    $this->parseCrdbPdf($statement, $filePath);
                } elseif ($statement->provider === 'YAS') {
                    $this->parseYasPdf($statement, $filePath);
                } elseif ($statement->provider === 'NMB Bank') {
                    $this->parseNmbPdf($statement, $filePath);
                } elseif ($statement->provider === 'M-Pesa') {
                    $this->parseMpesaPdf($statement, $filePath);
                } else {
                    $this->parsePdf($statement, $filePath);
                }
            }

            $statement->update(['status' => 'processed']);
            $this->calculateTotals($statement);
        } catch (\Exception $e) {
            \Log::error("Error parsing statement: " . $e->getMessage());
            $statement->update(['status' => 'failed']);
        }
    }

    protected function parseSelcomPdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // Regex for Selcom Pesa layout: Date Details Deposit Withdrawal Balance
            // Example: 2025-11-24 19:11:11 Details 0 3,500 415
            if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+(.*?)\s+([\d,.]+)\s+([\d,.]+)\s+([\d,.]+)/i', trim($line), $matches)) {
                $dateStr = $matches[1];
                $description = trim($matches[2]);
                $deposit = (float) str_replace(',', '', $matches[3]);
                $withdrawal = (float) str_replace(',', '', $matches[4]);
                $balance = (float) str_replace(',', '', $matches[5]);

                $amount = $deposit > 0 ? $deposit : $withdrawal;
                $type = $deposit > 0 ? 'credit' : 'debit';
                $isChargeRow = $this->isChargeRow($description);

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => Carbon::parse($dateStr),
                    'from' => $type === 'credit' ? 'External' : 'My Account',
                    'to' => $type === 'debit' ? 'External' : 'My Account',
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $this->categorize($description),
                    'channel' => 'mobile',
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function parseMpesaPdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();
        
        // Debugging extraction
        \Log::info("M-Pesa Extraction: " . substr($text, 0, 500));
        
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Highly specific M-Pesa regex for the 6-column layout
            // Date Time From To Amount Balance Description
            // Example: 21-03-2025 01:30PM 255765597134 245151 25,000 71,553.29 Pay Bill to...
            if (preg_match('/^(\d{1,2}[-\/\.]\d{1,2}[-\/\.]\d{2,4}\s+\d{1,2}:\d{2}(?::\d{2})?(?:\s?[AP]M)?)\s+([^\s]+)\s+([^\s]+)\s+([\d,.]+)\s+([\d,.]+)\s+(.*)$/i', $line, $matches)) {
                $dateStr = $matches[1];
                $from = $matches[2];
                $to = $matches[3];
                $amountStr = $matches[4];
                $balanceStr = $matches[5];
                $description = trim($matches[6]);

                $amount = (float) str_replace(',', '', $amountStr);
                $balance = (float) str_replace(',', '', $balanceStr);
                
                $isChargeRow = $this->isChargeRow($description);
                $type = $this->determineType($from, $to, $description);
                $category = $this->categorize($description);

                try {
                    // Try to parse the date specifically for M-Pesa format (DD-MM-YYYY HH:MM AM/PM)
                    $date = Carbon::parse(str_replace(['/', '.'], '-', $dateStr));
                } catch (\Exception $e) {
                    $date = now();
                }

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => $date,
                    'from' => $from,
                    'to' => $to,
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $category,
                    'channel' => 'mobile',
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function parseNmbPdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // Regex for NMB: Date ValueDate BrName Narration ... Debit Credit Balance
            // Handles formats like 14/Feb/2026 or 23/02/2026
            if (preg_match('/(\d{2}\/(?:\d{2}|\w{3})\/\d{4})\s+(\d{2}\/(?:\d{2}|\w{3})\/\d{4})?\s*(.*?)\s+([\d,.]+)\s+([\d,.]+)\s+([\d,.]+)/i', trim($line), $matches)) {
                $dateStr = $matches[1];
                // $matches[2] is value date
                $narration = trim($matches[3]);
                $debit = (float) str_replace(',', '', $matches[4]);
                $credit = (float) str_replace(',', '', $matches[5]);
                $balance = (float) str_replace(',', '', $matches[6]);

                if ($narration === 'OPENING BALANCE') continue;

                $amount = $debit > 0 ? $debit : $credit;
                $type = $credit > 0 ? 'credit' : 'debit';
                $isChargeRow = $this->isChargeRow($narration);

                try {
                    $date = Carbon::parse(str_replace('/', '-', $dateStr));
                } catch (\Exception $e) {
                    $date = now();
                }

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => $date,
                    'from' => $type === 'credit' ? 'External' : 'My NMB Account',
                    'to' => $type === 'debit' ? 'External' : 'My NMB Account',
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $narration,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $this->categorize($narration),
                    'channel' => 'bank',
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function parseYasPdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // Updated Regex for YAS: Handles the specific multi-line or single-line layout
            // Example: 01/02/2026 Received From... 0 15,000 559,634
            if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s+(.*?)\s+([\d,.]+)\s+([\d,.]+)\s+([\d,.]+)/i', trim($line), $matches)) {
                $dateStr = $matches[1];
                $description = trim($matches[2]);
                $moneyOut = (float) str_replace(',', '', $matches[3]);
                $moneyIn = (float) str_replace(',', '', $matches[4]);
                $balance = (float) str_replace(',', '', $matches[5]);

                $amount = $moneyIn > 0 ? $moneyIn : $moneyOut;
                $type = $moneyIn > 0 ? 'credit' : 'debit';

                // Extract ServiceCharge from description if present
                $charge = 0;
                if (preg_match('/ServiceCharge:([\d,.]+)/i', $description, $chargeMatches)) {
                    $charge = (float) str_replace(',', '', $chargeMatches[1]);
                }

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => Carbon::createFromFormat('d/m/Y', $dateStr),
                    'from' => $type === 'credit' ? 'External' : 'My YAS Wallet',
                    'to' => $type === 'debit' ? 'External' : 'My YAS Wallet',
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $charge,
                    'type' => $type,
                    'category' => $this->categorize($description),
                    'channel' => 'mobile',
                    'is_charge_row' => false,
                ]);
            }
        }
    }

    protected function parseCrdbPdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // Regex for CRDB: SN TRANS_DATE DETAILS ... VALUE_DATE DEBIT CREDIT BALANCE
            // Example: 1 2026-03-07 Details 2026-03-07 305.10 39,444.99
            // Note: CRDB columns can be complex. We match date patterns followed by amounts.
            if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(.*?)\s+(\d{4}-\d{2}-\d{2})\s+([\d,.]*)\s+([\d,.]*)\s+([\d,.]+)/i', trim($line), $matches)) {
                $dateStr = $matches[1];
                $description = trim($matches[2]);
                $drStr = trim($matches[4]);
                $crStr = trim($matches[5]);
                $balance = (float) str_replace(',', '', $matches[6]);

                $debit = $drStr ? (float) str_replace(',', '', $drStr) : 0;
                $credit = $crStr ? (float) str_replace(',', '', $crStr) : 0;

                $amount = $debit > 0 ? $debit : $credit;
                $type = $credit > 0 ? 'credit' : 'debit';
                $isChargeRow = $this->isChargeRow($description);

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => Carbon::parse($dateStr),
                    'from' => $type === 'credit' ? 'External' : 'My CRDB Account',
                    'to' => $type === 'debit' ? 'External' : 'My CRDB Account',
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $this->categorize($description),
                    'channel' => 'bank',
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function parseNbcPdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            // NBC Regex for layout: Date Branch Description ... ValueDate Dr Cr Balance
            // Example: 05/02/2026 1 Bank to Wallet Service Charge 05/02/2026 4,000.00 0.00 1266169.33
            if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s+(\d+)\s+(.*?)\s+(\d{2}\/\d{2}\/\d{4})\s+([\d,.]+)\s+([\d,.]+)\s+([\d,.]+)/i', trim($line), $matches)) {
                $dateStr = $matches[1];
                $description = trim($matches[3]);
                $dr = (float) str_replace(',', '', $matches[5]);
                $cr = (float) str_replace(',', '', $matches[6]);
                $balance = (float) str_replace(',', '', $matches[7]);

                $amount = $dr > 0 ? $dr : $cr;
                $type = $cr > 0 ? 'credit' : 'debit';
                $isChargeRow = $this->isChargeRow($description);

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => Carbon::createFromFormat('d/m/Y', $dateStr),
                    'from' => $type === 'credit' ? 'External' : 'My NBC Account',
                    'to' => $type === 'debit' ? 'External' : 'My NBC Account',
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $this->categorize($description),
                    'channel' => 'bank',
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function parsePdf(Statement $statement, $filePath)
    {
        $pdf = $this->pdfParser->parseFile($filePath);
        $text = $pdf->getText();

        // Debug log the text to see what's being extracted
        \Log::info("Extracted PDF Text for " . $statement->file_name . ": " . substr($text, 0, 1000));

        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Updated regex to be EXTREMELY flexible:
            // 1. Handles different date separators (- or / or .)
            // 2. Handles different space/tab gaps
            // 3. Handles optional AM/PM or 24h format
            // 4. Handles descriptions that might start on the same line or next line
            if (preg_match('/(\d{1,2}[-\/\.]\d{1,2}[-\/\.]\d{2,4}\s+\d{1,2}:\d{2}(?::\d{2})?(?:\s?[AP]M)?)\s+(.*?)\s+(.*?)\s+([\d,.]+)\s+([\d,.]+)\s+(.*)/i', $line, $matches)) {
                $dateStr = $matches[1];
                $from = trim($matches[2]);
                $to = trim($matches[3]);
                $amount = (float) str_replace(',', '', $matches[4]);
                $balance = (float) str_replace(',', '', $matches[5]);
                $description = trim($matches[6]);

                $isChargeRow = $this->isChargeRow($description);
                $type = $this->determineType($from, $to, $description);
                $category = $this->categorize($description);

                try {
                    $date = Carbon::parse(str_replace(['/', '.'], '-', $dateStr));
                } catch (\Exception $e) {
                    $date = now();
                }

                Transaction::create([
                    'statement_id' => $statement->id,
                    'transaction_date' => $date,
                    'from' => $from,
                    'to' => $to,
                    'amount' => $amount,
                    'balance' => $balance,
                    'description' => $description,
                    'charge' => $isChargeRow ? $amount : 0,
                    'type' => $type,
                    'category' => $category,
                    'channel' => 'mobile',
                    'is_charge_row' => $isChargeRow,
                ]);
            }
        }
    }

    protected function isChargeRow($description)
    {
        $chargeKeywords = [
            'Transaction Charge', 'M-Pesa Charge', 'Withdrawal Charge',
            'Transfer Fee', 'Sms Alert Fee', 'Service Fee',
            'SP Transaction Charge', 'Bank to Wallet Service Charge', 'CHARGES',
            'Debit Arrangement Tax', 'Maintenance Fee', 'Value Added Tax (VAT)',
            'Government Levy', 'ATM Cash WDL On.Us charges',
            'Monthly Fee', 'FSCH debit', 'GEPG_PAY'
        ];
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
