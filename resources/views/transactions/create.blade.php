@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">{{ __('Add Manual Transaction') }}</h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('transactions.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="transaction_date" class="form-label">{{ __('Date') }}</label>
                            <input id="transaction_date" type="datetime-local" class="form-control @error('transaction_date') is-invalid @enderror" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('transaction_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <input id="description" type="text" class="form-control @error('description') is-invalid @enderror" name="description" value="{{ old('description') }}" placeholder="e.g. Lunch at KFC" required>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">{{ __('Amount (Tsh)') }}</label>
                                <input id="amount" type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" required>
                                @error('amount')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="charge" class="form-label">{{ __('Charge/Fee (Optional)') }}</label>
                                <input id="charge" type="number" step="0.01" class="form-control @error('charge') is-invalid @enderror" name="charge" value="{{ old('charge', 0) }}">
                                @error('charge')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">{{ __('Type') }}</label>
                                <select id="type" class="form-select @error('type') is-invalid @enderror" name="type" required>
                                    <option value="debit" {{ old('type') == 'debit' ? 'selected' : '' }}>Debit (Spending)</option>
                                    <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Credit (Income)</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">{{ __('Category') }}</label>
                                <select id="category" class="form-select @error('category') is-invalid @enderror" name="category" required>
                                    <option value="food">Food & Drinks</option>
                                    <option value="shopping">Shopping</option>
                                    <option value="transport">Transport</option>
                                    <option value="bills">Bills & Utilities</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="withdrawal">Withdrawal</option>
                                    <option value="salary">Salary/Income</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('category')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="channel" class="form-label">{{ __('Payment Channel') }}</label>
                                <select id="channel" class="form-select @error('channel') is-invalid @enderror" name="channel" required>
                                    <option value="cash">Cash</option>
                                    <option value="mobile">Mobile Money</option>
                                    <option value="bank">Bank</option>
                                </select>
                                @error('channel')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary px-4">
                                {{ __('Add Transaction') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
