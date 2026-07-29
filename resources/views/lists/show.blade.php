@extends('layouts.app')

@section('title', $mediaList->name)

@section('content')
    <livewire:lists.show :media-list="$mediaList" />
@endsection
