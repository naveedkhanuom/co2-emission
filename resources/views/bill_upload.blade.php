@extends('layouts.app')

@section('content')
    <!-- Main Content -->
    <div id="content">

       @include('layouts.top-nav') 
<div class="container mt-5">
    <h2>Upload Electricity Bill</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>

        @if(session('bill'))
            <h4>Parsed Bill Data:</h4>
            <ul>
                <li><strong>Invoice No:</strong> {{ session('bill')->invoice_number }}</li>
                <li><strong>Bill Date:</strong> {{ session('bill')->bill_date }}</li>
                <li><strong>Supplier:</strong> {{ session('bill')->supplier }}</li>
                <li><strong>Consumption:</strong> {{ session('bill')->consumption }} {{ session('bill')->unit }}</li>
                <li><strong>Amount:</strong> ${{ session('bill')->amount }}</li>
            </ul>
            <h5>Raw Text:</h5>
            <pre>{{ session('bill')->raw_text }}</pre>
        @endif
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('bill.upload.post') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="bill_file" class="form-label">Choose Bill File (JPG, PNG, PDF)</label>
            <input type="file" class="form-control" name="bill_file" id="bill_file" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Parse</button>
    </form>
</div>
@endsection

@push('styles')
   @include('emission_records.css')

@endpush
