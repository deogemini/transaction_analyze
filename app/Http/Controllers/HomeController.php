<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Models\Transaction;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $statementIds = $user->statements()->pluck('id');
        
        $totalDebits = $user->statements()->sum('total_debits');
        $totalCredits = $user->statements()->sum('total_credits');
        $totalCharges = $user->statements()->sum('total_charges');
        
        // Category breakdown (excluding charge rows as they are already in totalCharges)
        $categoryBreakdown = Transaction::whereIn('statement_id', $statementIds)
            ->where('is_charge_row', false)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get();
            
        // Monthly trends
        $monthlyTrends = Transaction::whereIn('statement_id', $statementIds)
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as debits, SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credits")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        // Top 5 Payees
        $topPayees = Transaction::whereIn('statement_id', $statementIds)
            ->where('type', 'debit')
            ->where('is_charge_row', false)
            ->selectRaw('`to` as entity, SUM(amount) as total')
            ->groupBy('entity')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recentTransactions = Transaction::whereIn('statement_id', $statementIds)
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        return view('home', compact(
            'totalDebits', 
            'totalCredits', 
            'totalCharges', 
            'categoryBreakdown', 
            'monthlyTrends', 
            'topPayees',
            'recentTransactions'
        ));
    }
}
