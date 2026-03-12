@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">{{ __('Financial Overview') }}</h2>
        <div>
            <a href="{{ route('transactions.create') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-plus-circle me-1"></i> {{ __('Manual Entry') }}
            </a>
            <a href="{{ route('statements.create') }}" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i> {{ __('Upload Statement') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Total Credits') }}</h6>
                    <h3>Tsh {{ number_format($totalCredits, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Total Debits') }}</h6>
                    <h3>Tsh {{ number_format($totalDebits, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Total Charges') }}</h6>
                    <h3>Tsh {{ number_format($totalCharges, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Net Balance Change') }}</h6>
                    <h3>Tsh {{ number_format($totalCredits - $totalDebits, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Spending by Category -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Spending Distribution') }}</div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Trends -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Monthly Trends') }}</div>
                <div class="card-body">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Payees -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Top 5 Payees') }}</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse ($topPayees as $payee)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ Str::limit($payee->entity, 20) }}
                                <span class="badge bg-danger rounded-pill">Tsh {{ number_format($payee->total, 2) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-center">{{ __('No data available') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header">{{ __('Recent Transactions') }}</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Type') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('M d, H:i') : '-' }}</td>
                                        <td>{{ Str::limit($transaction->description, 40) }}</td>
                                        <td class="text-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">
                                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $transaction->type === 'credit' ? 'success' : 'danger' }} small">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center p-4">{{ __('No transactions found. Upload a statement to get started!') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category Chart
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        const catData = @json($categoryBreakdown);

        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catData.map(item => item.category.charAt(0).toUpperCase() + item.category.slice(1)),
                datasets: [{
                    data: catData.map(item => item.total),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = @json($monthlyTrends);

        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendData.map(item => item.month),
                datasets: [
                    {
                        label: 'Credits',
                        data: trendData.map(item => item.credits),
                        backgroundColor: '#198754'
                    },
                    {
                        label: 'Debits',
                        data: trendData.map(item => item.debits),
                        backgroundColor: '#dc3545'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    });
</script>
@endsection
