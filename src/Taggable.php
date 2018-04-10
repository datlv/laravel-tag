<?php namespace Datlv\Tag;

use DB;

/**
 * Class Taggable
 *
 * @property-read \Illuminate\Database\Eloquent\Collection $tags
 * @package Datlv\Tag
 * @mixin \Eloquent
 */
trait Taggable {
    /**
     * @var \Illuminate\Support\Collection
     */
    public static $usedTagNames;
    protected $tagsDirty = false;
    protected $tagsTmp;

    /**
     * Boot the soft taggable trait for a model.
     *
     * @return void
     */
    public static function bootTaggable() {

        static::deleting( function ( $model ) {
            /** @var Taggable $model */
            $model->untag();
        } );
        static::saved( function ( $model ) {
            /** @var Taggable $model */
            $model->saveTags();
        } );
    }

    /**
     * Tất cả tags đang sử dụng đối với 'model type' này
     *
     * @param string $glue
     *
     * @return string
     */
    public static function usedTagNames( $glue = ',' ) {
        if ( is_null( static::$usedTagNames ) ) {
            static::$usedTagNames = Tag::usedBy( static::class )->get()->pluck( 'name' );
        }

        return implode( $glue, static::$usedTagNames->toArray() );
    }

    /**
     * Luu tags đã gán vào biến tạm
     */
    public function saveTags() {
        if ( $this->tagsDirty ) {
            $this->retag( $this->tagsTmp );
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags() {
        return $this->morphToMany( Tag::class, 'taggable' );
    }

    /**
     * @return string[]
     */
    public function tagNames() {
        return $this->tags->map( function ( $item ) {
            return $item->name;
        } )->toArray();
    }

    /**
     * setter $this->tag_names
     *
     * @param string|array $value
     */
    public function setTagNamesAttribute( $value ) {
        $this->tagsDirty = true;
        $this->tagsTmp = $value;
    }

    /**
     * getter $this->tag_names
     *
     * @return string
     */
    public function getTagNamesAttribute() {
        return implode( ',', $this->tagNames() );
    }

    /**
     * @param string|array $tags
     */
    public function tag( $tags ) {
        $ids = array_map( function ( $tag ) {
            return Tag::firstOrCreate( [ 'name' => $tag ] )->getKey();
        }, mb_array_sure( $tags ) );
        $this->tags()->attach( $ids );
    }

    /**
     * Nếu $tags = null, xóa mọi tag của model này
     *
     * @param null|string|array $tags
     */
    public function untag( $tags = null ) {
        $ids = is_null( $tags ) ? null : ( empty( $tags ) ? [] : Tag::findByNames( $tags )->pluck( 'id' ) );
        $this->tags()->detach( $ids );
        Tag::deleteUnused();
    }

    /**
     * @param string|array $tags
     */
    public function retag( $tags ) {
        $tags = mb_array_sure( $tags );
        if ( $tags ) {
            $currentTagNames = $this->tagNames();
            $this->untag( array_diff( $currentTagNames, $tags ) );
            $this->tag( array_diff( $tags, $currentTagNames ) );
        } else {
            $this->untag();
        }
    }

    /**
     * Danh sách model đã được tag với các $tags, $all: tất cả hay chỉ cần một
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $tags
     * @param bool $all
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTagged( $query, $tags, $all = false ) {
        return $all ? $this->scopeTaggedAll( $query, $tags ) : $this->scopeTaggedOne( $query, $tags );
    }

    /**
     * Danh sách model đã được tag với MỘT TRONG các $tags
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $tags
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTaggedOne( $query, $tags ) {
        return $query->whereIn( $this->getTable() . '.' . $this->getKeyName(), $this->getTaggedIds( $tags ) );
    }

    /**
     * Danh sách model đã được tag với TÂT CẢ các $tags
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $tags
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTaggedAll( $query, $tags ) {
        foreach ( mb_array_sure( $tags ) as $tag ) {
            $query->whereIn( $this->getTable() . '.' . $this->getKeyName(), $this->getTaggedIds( $tag ) );
        }

        return $query;
    }

    /**
     * Get models liên quan, cũng được gán tât cả các tags giống nó
     *
     * @param \Illuminate\Database\Query\Builder|static $query
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function scopeRelated( $query ) {
        return $this->scopeTaggedAll( $query, $this->getTagNamesAttribute() );
    }

    /**
     * Sắp xếp theo đếm tags
     *
     * @param \Illuminate\Database\Query\Builder|static $query
     * @param string|array $tags
     * @param string $direction
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function scopeOrderByMatchedTag( $query, $tags, $direction = 'desc' ) {
        if ( empty( $tags = mb_array_sure( $tags ) ) ) {
            return $query;
        }
        $tags = "'" . implode( "','", $tags ) . "'";
        $type = $this->getMorphClass();

        return $query->addSelect(
            DB::raw(
                "(
                    SELECT COUNT(*)
                    FROM taggables
                    LEFT JOIN tags ON taggables.tag_id = tags.id
                    WHERE
                        taggables.taggable_id = {$this->getTable()}.id AND
                        taggables.taggable_type = '{$type}' AND
                        tags.name IN ({$tags})
                ) AS count_matched_tag"
            )
        )->orderBy( 'count_matched_tag', $direction );
    }

    /**
     * Danh sách model ids đã được gán $tags
     *
     * @param string|array $tags
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTaggedIds( $tags ) {
        return Tag::usedBy( $this )->whereIn( 'tags.name', mb_array_sure( $tags ) )->get()->pluck( 'taggable_id' );
    }
}