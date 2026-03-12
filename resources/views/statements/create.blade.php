@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Upload Financial Statement') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('statements.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="provider" class="form-label">{{ __('Provider') }}</label>
                            <select id="provider" class="form-select @error('provider') is-invalid @enderror" name="provider" required>
                                <option value="M-Pesa">M-Pesa</option>
                                <option value="Selcom Pesa">Selcom Pesa</option>
                                <option value="NBC Bank">NBC Bank</option>
                                <option value="CRDB Bank">CRDB Bank</option>
                                <option value="NMB Bank">NMB Bank</option>
                                <option value="YAS">YAS (Mobile Money)</option>
                                <option value="Tigo Pesa">Tigo Pesa</option>
                                <option value="Airtel Money">Airtel Money</option>
                            </select>
                            @error('provider')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('Statement Type') }}</label>
                            <select id="type" class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="mobile">Mobile Money</option>
                                <option value="bank">Bank</option>
                            </select>
                            @error('type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="statement_file" class="form-label">{{ __('Statement File (PDF, CSV, Excel)') }}</label>
                            <input id="statement_file" type="file" class="form-control @error('statement_file') is-invalid @enderror" name="statement_file" required>
                            @error('statement_file')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Upload and Process') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
