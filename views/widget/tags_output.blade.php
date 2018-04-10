<?php
/**
 * @var \Datlv\Layout\Widget $widget
 * @var array $tags
 */
?>
@if($tags)
    @foreach ( $tags as $name => $count )
        <a href="{{Route::has($widget->data['route_show']) ? route($widget->data['route_show'], ['tag' => $name]) : "#{$name}"}}" class="tag">
            <span class="tag-name {{$widget->data['tag_css']}}">{{$name}}</span>
            @if($widget->data['show_count'])
                <span class="tag-count badge">{{$count}}</span>
            @endif
        </a>
    @endforeach
@endif
