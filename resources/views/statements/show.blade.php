@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Statement Summary: ') }} {{ $statement->file_name }} ({{ $statement->provider }})</span>
                    <div>
                        <a href="{{ route('statements.export', $statement) }}" class="btn btn-sm btn-success me-2">{{ __('Export CSV') }}</a>
                        <a href="{{ route('statements.index') }}" class="btn btn-sm btn-secondary">{{ __('Back to List') }}</a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h5 class="text-success">{{ __('Total Credits') }}</h5>
                            <h3>Tsh {{ number_format($statement->total_credits, 2) }}</h3>
                        </div>
                        <div class="col-md-4">
                            <h5 class="text-danger">{{ __('Total Debits') }}</h5>
                            <h3>Tsh {{ number_format($statement->total_debits, 2) }}</h3>
                        </div>
                        <div class="col-md-4">
                            <h5 class="text-warning">{{ __('Total Charges') }}</h5>
                            <h3>Tsh {{ number_format($statement->total_charges, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">{{ __('Transactions List') }}</div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('From') }}</th>
                                    <th>{{ __('To') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Balance') }}</th>
                                    <th>{{ __('Charge') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Category') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d H:i') : '-' }}</td>
                                        <td>{{ $transaction->from }}</td>
                                        <td>{{ $transaction->to }}</td>
                                        <td>{{ $transaction->description }}</td>
                                        <td class="text-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">
                                            {{ $transaction->type === 'credit' ? '+' : '-' }} {{ number_format($transaction->amount, 2) }}
                                        </td>
                                        <td>{{ number_format($transaction->balance, 2) }}</td>
                                        <td class="text-warning">{{ number_format($transaction->charge, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td>{{ ucfirst($transaction->category) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">{{ __('No transactions found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
