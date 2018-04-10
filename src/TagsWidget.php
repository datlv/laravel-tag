<?php namespace Datlv\Tag;

use Datlv\Kit\Support\HasRouteAttribute;
use Datlv\Layout\WidgetTypes\WidgetType;
use DB;
use Kit;

/**
 * Class TagsWidget
 *
 * @package Datlv\Tag
 */
class TagsWidget extends WidgetType {
    use HasRouteAttribute;
    /**
     * @return static
     */
    public function getTagTypes() {
        return DB::table( 'taggables' )->distinct()->pluck( 'taggable_type' )->mapWithKeys( function ( $type ) {
            return [ $type => Kit::title( $type ) ];
        } );
    }

    /**
     * @return string
     */
    protected function formView() {
        return 'tag::widget.tags_form';
    }

    /**
     * @param $widget
     *
     * @return array|\Illuminate\Support\Collection
     */
    protected function getTags( $widget ) {
        return $widget->data['tag_type'] ?
            DB::table( 'taggables' )
              ->where( 'taggable_type', $widget->data['tag_type'] )
              ->leftJoin( 'tags', 'taggables.tag_id', '=', 'tags.id' )
              ->select( 'name', DB::raw( 'count(*) as count' ) )
              ->orderBy( 'count', 'desc' )
              ->groupBy( 'tag_id' )
              ->pluck( 'count', 'name' ) :
            [];
    }

    /**
     * @param \Datlv\Layout\Widget $widget
     *
     * @return string
     */
    protected function content( $widget ) {
        $tags = $this->getTags( $widget )->all();

        return view( 'tag::widget.tags_output', compact( 'widget', 'tags' ) )->render();
    }

    protected function dataAttributes() {
        return [
            [ 'name' => 'tag_type', 'title' => trans( 'tag::widget.tags.tag_type' ), 'rule' => 'required|max:255', 'default' => null ],
            [ 'name' => 'route_show', 'title' => trans( 'tag::widget.tags.route_show' ), 'rule' => 'required|max:255', 'default' => '#' ],
            [ 'name' => 'tag_css', 'title' => trans( 'tag::widget.tags.tag_css' ), 'rule' => 'max:255', 'default' => 'label label-primary' ],
            [ 'name' => 'show_count', 'title' => trans( 'tag::widget.tags.show_count' ), 'rule' => 'integer', 'default' => 0 ],
        ];
    }
}