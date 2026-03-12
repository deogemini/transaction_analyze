@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-3">{{ __('Financial Statement Charges Analyzer Platform') }}</h1>
            <p class="lead mb-4">
                {{ __('Automatically analyze your bank and mobile money statements. Understand your spending patterns and discover where you lose money in transaction fees.') }}
            </p>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                @guest
                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 me-md-2">{{ __('Get Started') }}</a>
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg px-4">{{ __('Login') }}</a>
                @else
                    <a href="{{ route('home') }}" class="btn btn-primary btn-lg px-4 me-md-2">{{ __('Go to Dashboard') }}</a>
                    <a href="{{ route('statements.create') }}" class="btn btn-outline-primary btn-lg px-4">{{ __('Upload Statement') }}</a>
                @endguest
            </div>
        </div>
        <div class="col-lg-6">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Financial Analytics" class="img-fluid rounded shadow">
        </div>
    </div>

    <div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
        <div class="col d-flex align-items-start">
            <div class="icon-square bg-light text-dark flex-shrink-0 me-3">
                <i class="bi bi-file-earmark-pdf fs-2"></i>
            </div>
            <div>
                <h2>{{ __('Statement Upload') }}</h2>
                <p>{{ __('Support for M-Pesa, Tigo Pesa, Airtel Money, and major bank statements in PDF, CSV, or Excel formats.') }}</p>
            </div>
        </div>
        <div class="col d-flex align-items-start">
            <div class="icon-square bg-light text-dark flex-shrink-0 me-3">
                <i class="bi bi-search fs-2"></i>
            </div>
            <div>
                <h2>{{ __('Charge Detection') }}</h2>
                <p>{{ __('Our engine automatically identifies transaction charges, ATM fees, and service costs hidden in your statements.') }}</p>
            </div>
        </div>
        <div class="col d-flex align-items-start">
            <div class="icon-square bg-light text-dark flex-shrink-0 me-3">
                <i class="bi bi-pie-chart fs-2"></i>
            </div>
            <div>
                <h2>{{ __('Financial Insights') }}</h2>
                <p>{{ __('Visual dashboards showing spending distribution, monthly trends, and financial health scores.') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
@endsection