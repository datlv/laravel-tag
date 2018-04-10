<?php namespace Datlv\Tag;

use Eloquent;

/**
 * Class Tag
 *
 * @package Datlv\Tag
 * @property int $id
 * @property string $name
 * @method static \Illuminate\Database\Query\Builder|\Datlv\Tag\Tag used()
 * @method static \Illuminate\Database\Query\Builder|\Datlv\Tag\Tag usedBy($model)
 * @method static \Illuminate\Database\Query\Builder|\Datlv\Tag\Tag whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Datlv\Tag\Tag whereName($value)
 */
class Tag extends Eloquent
{
    protected $table = 'tags';

    protected $fillable = ['name'];

    public $timestamps = false;

    /**
     * Xóa các tags không còn dùng ở bất kỳ model nào?
     */
    public static function deleteUnused()
    {
        static::leftJoin('taggables', 'taggables.tag_id', '=', 'tags.id')->whereNull('taggables.tag_id')->delete();
    }

    /**
     * Các tags có sử dụng
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsed($query)
    {
        return $query->join('taggables', 'taggables.tag_id', '=', 'tags.id');
    }

    /**
     * Các tags có sử dụng cho $model
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Taggable|string $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsedBy($query, $model)
    {
        /** @var Taggable $model */
        $model = is_string($model) ? new $model : $model;

        return $this->scopeUsed($query)->where('taggables.taggable_type', $model->getMorphClass());
    }

    /**
     * @param string|array $names
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function findByNames($names, $columns = ['*'])
    {
        return static::whereIn('name', mb_array_sure($names))->get($columns);
    }
}