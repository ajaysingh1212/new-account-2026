@extends('layouts.admin')
@section('title','Edit Delivery Challan')
@section('content')
@include('admin.delivery-challans.form', ['deliveryChallan' => $deliveryChallan])
@endsection
