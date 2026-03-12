<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create()
    {
        return view('transactions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'required|string',
            'type' => 'required|in:credit,debit',
            'category' => 'required|string',
            'charge' => 'nullable|numeric',
        ]);

        // Find or create a "Manual Entry" statement for the user
        $statement = Statement::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'provider' => 'Manual',
                'type' => 'manual',
            ],
            [
                'file_name' => 'Manual Transactions',
                'file_path' => 'manual',
                'status' => 'processed',
            ]
        );

        $transaction = Transaction::create([
            'statement_id' => $statement->id,
            'transaction_date' => Carbon::parse($request->transaction_date),
            'amount' => $request->amount,
            'description' => $request->description,
            'charge' => $request->charge ?? 0,
            'type' => $request->type,
            'category' => $request->category,
            'is_charge_row' => false,
        ]);

        // Update statement totals
        $this->updateStatementTotals($statement);

        return redirect()->route('home')->with('success', 'Manual transaction added successfully.');
    }

    protected function updateStatementTotals(Statement $statement)
    {
        $statement->total_debits = $statement->transactions()->where('type', 'debit')->sum('amount');
        $statement->total_credits = $statement->transactions()->where('type', 'credit')->sum('amount');
        $statement->total_charges = $statement->transactions()->sum('charge');
        $statement->save();
    }
}
