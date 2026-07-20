@extends('layouts.admin')
@section('title', 'Other Income / Expense')
@section('content')
    @include('admin.other-transactions.form', ['transaction' => $transaction])
@endsection
