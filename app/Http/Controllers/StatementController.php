<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Services\StatementParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StatementController extends Controller
{
    protected $parser;

    public function __construct(StatementParser $parser)
    {
        $this->parser = $parser;
        $this->middleware('auth');
    }

    public function index()
    {
        $statements = auth()->user()->statements()->latest()->paginate(10);
        return view('statements.index', compact('statements'));
    }

    public function create()
    {
        return view('statements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'statement_file' => 'required|file|mimes:pdf,csv,xlsx,xls|max:10240',
            'provider' => 'required|string',
            'type' => 'required|string',
        ]);

        $file = $request->file('statement_file');
        $path = $file->store('statements');

        $statement = Statement::create([
            'user_id' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'provider' => $request->provider,
            'type' => $request->type,
            'status' => 'pending',
        ]);

        // In a real app, this should be a queued job
        $this->parser->parse($statement);

        return redirect()->route('statements.show', $statement)
            ->with('success', 'Statement uploaded and processing started.');
    }

    public function show(Statement $statement)
    {
        if ($statement->user_id !== auth()->id()) {
            abort(403);
        }

        $transactions = $statement->transactions()->paginate(20);
        return view('statements.show', compact('statement', 'transactions'));
    }

    public function export(Statement $statement)
    {
        if ($statement->user_id !== auth()->id()) {
            abort(403);
        }

        $transactions = $statement->transactions;
        $filename = "transactions_" . $statement->id . "_" . date('Ymd') . ".csv";
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // CSV Header
        fputcsv($handle, ['Date', 'From', 'To', 'Description', 'Amount', 'Balance', 'Charge', 'Type', 'Category']);

        foreach ($transactions as $transaction) {
            fputcsv($handle, [
                $transaction->transaction_date,
                $transaction->from,
                $transaction->to,
                $transaction->description,
                $transaction->amount,
                $transaction->balance,
                $transaction->charge,
                $transaction->type,
                $transaction->category
            ]);
        }

        fclose($handle);
        exit;
    }

    public function destroy(Statement $statement)
    {
        if ($statement->user_id !== auth()->id()) {
            abort(403);
        }

        Storage::delete($statement->file_path);
        $statement->delete();

        return redirect()->route('statements.index')
            ->with('success', 'Statement deleted.');
    }
}
