@extends('layouts.admin')
@section('title', 'Create Bank / Cash Account')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.bank-accounts.index') }}">Bank Accounts</a></li><li class="breadcrumb-item active">Create</li>@endsection
@section('content')
@include('admin.bank-accounts.form')
@endsection
