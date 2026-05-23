@extends('layouts.admin')
@section('title', 'Edit Bank / Cash Account')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.bank-accounts.index') }}">Bank Accounts</a></li><li class="breadcrumb-item active">Edit</li>@endsection
@section('content')
@include('admin.bank-accounts.form')
@endsection
