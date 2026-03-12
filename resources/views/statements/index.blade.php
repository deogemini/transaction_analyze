@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('My Statements') }}</span>
                    <a href="{{ route('statements.create') }}" class="btn btn-sm btn-primary">{{ __('Upload New') }}</a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('File Name') }}</th>
                                    <th>{{ __('Provider') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Credits') }}</th>
                                    <th>{{ __('Debits') }}</th>
                                    <th>{{ __('Charges') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statements as $statement)
                                    <tr>
                                        <td>{{ $statement->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $statement->file_name }}</td>
                                        <td>{{ $statement->provider }}</td>
                                        <td>
                                            <span class="badge bg-{{ $statement->status === 'processed' ? 'success' : ($statement->status === 'failed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($statement->status) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($statement->total_credits, 2) }}</td>
                                        <td>{{ number_format($statement->total_debits, 2) }}</td>
                                        <td>{{ number_format($statement->total_charges, 2) }}</td>
                                        <td>
                                            <a href="{{ route('statements.show', $statement) }}" class="btn btn-sm btn-info">{{ __('View') }}</a>
                                            <form action="{{ route('statements.destroy', $statement) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">{{ __('No statements found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $statements->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection