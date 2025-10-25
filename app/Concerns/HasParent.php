<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasParent
{
    private $descendents = [];

    private $ancestors = [];

    public function getParentKeyName()
    {
        return 'parent_id';
    }

    public function children()
    {
        return $this->hasMany(self::class, $this->getParentKeyName());
    }

    public function nestedChildren()
    {
        return $this->children()->with('nestedChildren');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, $this->getParentKeyName());
    }

    public function nestedParents()
    {
        return $this->belongsTo(self::class, $this->getParentKeyName())->with('nestedParents');
    }

    public function scopeHasChildren(Builder $query)
    {
        $keys = (new self)->newQuery()
            ->select($this->getParentKeyName())
            ->hasParent();

        return $query->whereIn($this->getKeyName(), $keys);
    }

    public function scopeHasParent(Builder $query)
    {
        return $query->whereNotNull($this->getParentKeyName());
    }

    public function scopeLeaf(Builder $query)
    {
        $keys = (new self)->newQuery()
            ->select($this->getParentKeyName())
            ->hasParent();

        return $query->whereNotIn($this->getKeyName(), $keys);
    }

    public function scopeRoot(Builder $query)
    {
        return $query->whereNull($this->getParentKeyName());
    }

    public function descendents()
    {
        if ($this->children) {
            $this->collectDescendents($this->children);
        }

        return collect($this->descendents);
    }

    private function collectDescendents($children)
    {
        foreach ($children as $child) {
            $this->descendents[] = $child;
            if ($child->children) {
                $this->collectDescendents($child->children);
            }
        }
    }

    public function ancestors()
    {
        if ($this->parent) {
            $this->collectAncestors($this->parent);
        }

        return collect($this->ancestors);
    }

    private function collectAncestors($parent)
    {
        $this->ancestors[] = $parent;
        if ($parent->parent) {
            $this->collectAncestors($parent->parent);
        }
    }

    public function topAncestor()
    {
        if ($this->parent) {
            $this->getTopAncestor($this->parent);
        }

        return $this->ancestor;
    }

    private function getTopAncestor($parent)
    {
        $this->ancestor = $parent;
        if ($parent->parent) {
            $this->getTopAncestor($parent->parent);
        }
    }

    public function siblings()
    {
        return self::all()->where($this->getKeyName(), '!=', $this->{$this->getKeyName()})->where($this->getParentKeyName(), $this->{$this->getParentKeyName()});
    }
}
