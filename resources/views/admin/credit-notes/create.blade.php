@extends('layouts.admin')
@section('title','New Credit Note')
@section('content')
@include('admin.credit-notes.form', ['formAction' => route('admin.credit-notes.store'), 'formMethod' => 'POST', 'creditNote' => null])
@endsection
