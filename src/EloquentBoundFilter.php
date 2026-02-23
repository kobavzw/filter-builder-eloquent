<?php

namespace Koba\FilterBuilder\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Koba\FilterBuilder\Core\Contracts\BoundFilterInterface;

/**
 * @template TModel of Model
 */
class EloquentBoundFilter implements BoundFilterInterface
{
    /**
     * @param class-string<TModel> $type
     * @param Closure(Builder<TModel>):void $applyFn
     */
    public function __construct(protected $type, protected $applyFn) {}

    /**
     * @param Builder<TModel> $qry
     */
    public function apply($qry): void
    {
        ($this->applyFn)($qry);
    }
}
