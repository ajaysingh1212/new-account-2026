@extends('layouts.admin')
@section('title','Edit Estimate')
@section('content')
@include('admin.estimates.form', ['estimate' => $estimate])
@endsection
