# Financial Statement Charges Analyzer Platform

## Executive Summary
A web-based financial analytics platform designed to help individuals and businesses understand their spending patterns and optimize financial behavior. The platform allows users to upload bank and mobile money statements to receive a detailed analysis of debits, credits, and transaction charges.

## Core Features
- **Statement Upload**: Support for M-Pesa (PDF) and other financial statements.
- **Automatic Extraction**: Normalized transaction data extraction from uploaded files.
- **Charge Detection**: Automatically identifies transaction fees, ATM withdrawals, and service charges.
- **Manual Entry**: Track cash expenses or non-digital transactions manually.
- **Analytics Dashboard**:
    - **Total Summaries**: Credits, Debits, and Charges overview.
    - **Visual Insights**: Spending distribution by category and monthly trends.
    - **Top Payees**: Identify major spending entities.
- **Export**: Export parsed transaction data to CSV for further analysis.

## Tech Stack
- **Backend**: Laravel 12
- **Frontend**: Bootstrap 5 + Chart.js
- **Parsing**: Smalot PDF Parser

## Getting Started
1. Clone the repository
2. Run `composer install`
3. Run `npm install && npm run build`
4. Configure your `.env` file and run `php artisan migrate`
5. Start the server: `php artisan serve`
