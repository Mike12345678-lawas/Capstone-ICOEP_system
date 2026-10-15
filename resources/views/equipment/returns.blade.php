@extends('layouts.app')

@section('title', 'Equipment Returns Desk — ' . config('icoep.app_name'))

@section('content')
    @include('dashboard.partials._equipment_returns')
@endsection
