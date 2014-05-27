@extends('layouts.main')

@section('left-panel')
    <!-- {{{  json_encode($photos_list,true) or "empty" }}} -->
    @foreach ($photos_list as $photos_list_row)
	    <div class="row">
			@foreach ($photos_list_row as $photo)
			    <div class="medium-4 columns photo-row">
				  	<div class="explore_frame">
				  		@if($photo['url'])
						<img src="{{$photo['url']}}"/>	
						@else
						<div class="img_no">未设置头像</div>
				  		@endif
				  	</div>
				  </div>
			@endforeach
		  

		</div>
	@endforeach
    
@stop

@section('right-panel')
    <p>This is right.</p>
@stop