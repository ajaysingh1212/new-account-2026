@extends('layouts.admin')
@section('title', 'Create Cost Center')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.cost-centers.index') }}">Cost Centers</a></li><li class="breadcrumb-item active">Create</li>@endsection
@section('content')
@include('admin.cost-centers.partials-form')
@endsection
