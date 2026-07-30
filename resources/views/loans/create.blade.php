@extends('layouts.app')

@section('title', 'Ausleihe erfassen')

@section('content')
    <livewire:loans.create :copy-id="$copyId" />
@endsection
