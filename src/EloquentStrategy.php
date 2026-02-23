<?php

namespace Koba\FilterBuilder\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Koba\FilterBuilder\Core\Contracts\StrategyInterface;
use Koba\FilterBuilder\Core\Enums\GroupType;
use Koba\FilterBuilder\Core\Enums\Operation;
use LogicException;

/**
 * @template TModel of Model
 * @implements StrategyInterface<EloquentBoundFilter<TModel>>
 */
class EloquentStrategy implements StrategyInterface
{
    /**
     * @param class-string<TModel> $type
     */
    public function __construct(private $type) {}

    public function makeGroupBoundFilter(GroupType $type, array $children)
    {
        return new EloquentBoundFilter($this->type, function ($qry) use ($type, $children) {
            $qry->where($this->getApplyChildrenFn($this->type, $type, $children));
        });
    }

    /**
     * @param Closure(Builder<TModel>,Closure(string,Builder<TModel>):void):void $queryConstraint
     * @return Closure(Operation,int|string|float|(int|string|float)[]):EloquentBoundFilter<TModel>
     */
    public function makeRule($queryConstraint)
    {
        return fn(Operation $operation, $value) => new EloquentBoundFilter(
            $this->type,
            function ($qry) use ($operation, $value, $queryConstraint) {
                ($queryConstraint)(
                    $qry,
                    function ($field, $qry) use ($operation, $value) {
                        $this->applyOperation($operation, $qry, $field, $value);
                    }
                );
            }
        );
    }

    /**
     * @template TRelated of Model
     * @param class-string<TRelated> $className
     * @param Closure(Builder<TModel>,Closure(Builder<TRelated>):void):void $queryConstraint
     * @return Closure(GroupType,EloquentBoundFilter<TRelated>[]):EloquentBoundFilter<TModel>
     */
    public function makeRelation(string $className, $queryConstraint)
    {
        return fn($groupType, $children) => new EloquentBoundFilter(
            $this->type,
            function ($qry) use ($queryConstraint, $groupType, $children, $className) {
                ($queryConstraint)(
                    $qry,
                    $this->getApplyChildrenFn($className, $groupType, $children),
                );
            }
        );
    }

    /**
     * @template TApply of Model
     * @param class-string<TApply> $type
     * @param EloquentBoundFilter<TApply>[] $children
     * @return Closure(Builder<TApply>):void
     */
    private function getApplyChildrenFn($type, GroupType $groupType, $children)
    {
        return static function ($qry) use ($groupType, $children) {
            foreach ($children as $child) {
                if ($groupType === GroupType::AND) {
                    $child->apply($qry);
                } else {
                    $qry->orWhere(function ($qry) use ($child) {
                        $child->apply($qry);
                    });
                }
            }
        };
    }

    /**
     * @param Builder<TModel> $qry
     * @param int|string|float|(int|string|float)[] $value
     */
    public function applyOperation(Operation $operation, $qry, string $attribute, $value): void
    {
        switch ($operation) {
            case Operation::EQUALS:
                $qry->where($attribute, $value);
                break;
            case Operation::GREATER_THAN:
                $qry->where($attribute, '>', $value);
                break;
            case Operation::LESS_THAN:
                $qry->where($attribute, '<', $value);
                break;
            case Operation::STARTS_WITH:
                if (is_array($value)) {
                    throw new LogicException();
                }

                $qry->where($attribute, 'like', "{$value}%");
                break;
            case Operation::ONE_OF:
                $qry->whereIn($attribute, $value);
                break;
        }
    }
}
