@extends('layouts.main')

@section('left-panel')
    <!-- {{{  json_encode($photos_list,true) or "empty" }}} -->
    <?php var_dump($photos_list);?>
@stop

@section('right-panel')
    <p>This is right.</p>
@stop