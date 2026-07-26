@extends('layouts.app')

@section('title', $media->title)

@section('content')
    <livewire:media.show :media="$media" />
@endsection
