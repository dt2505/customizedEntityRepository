<?php
namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

class BaseEntityRepository extends EntityRepository implements EntityRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function deleteById($id)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Id is required.');
        }
        return $this->deleteBy(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public function deleteByIds(array $ids)
    {
        if (empty($ids)) {
            throw new \InvalidArgumentException('Id list is required.');
        }
        return $this->deleteBy(['id' => $ids]);
    }

    /**
     * @inheritdoc
     */
    public function deleteBy(array $criteria = [], $operator = null)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $where = $this->getWhere($qb, $criteria, $parameters, $alias, $operator);
        $this->deletePhysically($qb, $where, $parameters, $alias);
    }

    /**
     * @inheritdoc
     */
    public function deleteAll()
    {
        $this->deleteBy();
    }

    /**
     * @return int
     */
    public function count()
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select($qb->expr()->count('e.id'));

        return intval($qb->getQuery()->getSingleScalarResult());
    }

    /**
     * @param array $criteria
     * @param null $operator
     *
     * @return int
     */
    public function countBy(array $criteria = [], $operator = null)
    {
        if (empty($criteria)) {
            return $this->count();
        }

        $qb = $this->createQueryBuilder($alias = 'e');
        $where = $this->getWhere($qb, $criteria, $parameters, $alias, $operator);

        return $this->countRecordsBy($qb, $where, $parameters, $alias);
    }

    /**
     * @param array $criteria
     * @param null $operator
     * @param null $pos
     *
     * @return int
     */
    public function countLike(array $criteria = [], $operator = null, $pos = null)
    {
        if (empty($criteria)) {
            return $this->count();
        }

        $qb = $this->createQueryBuilder($alias = 'e');
        $where = $this->getWhereLike($qb, $criteria, $parameters, $alias, $operator, $pos);
        return $this->countRecordsBy($qb, $where, $parameters, $alias);
    }

    /**
     * @inheritdoc
     */
    public function findByIds(array $ids, $orderBy = [], $limit = null, $offset = null)
    {
        return empty($ids) ? [] : $this->findBy(['id' => $ids], $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     */
    public function findLike(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null)
    {
        if (empty($criteria)) {
            return $this->findBy($criteria, $orderBy, $limit, $offset);
        }

        $qb = $this->createQueryBuilder($alias = 'et');
        $where = $this->getWhereLike($qb, $criteria, $parameters, $alias, $operator, $pos);

        return $this->getResults($this->addOrderBy($qb, $orderBy, $alias), $where, $parameters, $offset, $limit);
    }

    /**
     * @param QueryBuilder $qb
     * @param $where
     * @param $parameters
     * @param $offset
     * @param $limit
     *
     * @return array
     */
    protected function getResults(QueryBuilder $qb, $where, $parameters, $offset, $limit)
    {
        $query = $qb
            ->where($where)
            ->setParameters($parameters)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param $where
     * @param $parameters
     * @param $alias
     *
     * @return int
     */
    protected function countRecordsBy(QueryBuilder $qb, $where, $parameters, $alias)
    {
        $qb->select($qb->expr()->count($this->attachAlias("id", $alias)));

        if (empty($where)) {
            return 0;
        }

        $result = $qb
            ->where($where)
            ->setParameters($parameters)
            ->getQuery()
            ->getSingleScalarResult();
        return intval($result);
    }

    /**
     * @param QueryBuilder $qb
     * @param $fieldsToSet
     * @param string $alias
     * @param null $where
     * @param array $parameters
     */
    protected function bulkUpdate(QueryBuilder $qb, $fieldsToSet, $alias = "", $where = null, $parameters = [])
    {
        $qb->update($this->getEntityName(), $alias);

        if (!empty($where)) {
            $qb->where($where)

                // this will overwrite all parameters so this must be set first if exists
                ->setParameters($parameters);
        }

        foreach ($fieldsToSet as $field => $value) {
            $key = ":$field";
            $qb->set($this->attachAlias($field, $alias), $key)

                // this will add to current parameter array so this must be set later otherwise it will be erased by
                // 'setParameters'
                ->setParameter($key, $value);
        }

        $q = $qb->getQuery();
        $q->execute();
    }

    /**
     * @param $field
     * @param string $alias
     * @param bool $force
     *
     * @return string
     */
    protected function attachAlias($field, $alias = "", $force = false)
    {
        if (empty($alias)) {
            return $field;
        }

        if ($force) {
            return sprintf('%s.%s', $alias, $this->removeAlias($field));
        }

        return !$this->hasAlias($field) ? sprintf('%s.%s', $alias, $field) : $field;
    }

    /**
     * @param $criteria
     * @param $normalFields
     *
     * @return array
     */
    protected function splitAssociationFieldsOut($criteria, &$normalFields)
    {
        $associationNames = $this->getClassMetadata()->getAssociationNames();
        $fields = array_keys($criteria);
        $fieldsWithoutAssociation = array_diff($fields, $associationNames);
        $fieldsWithAssociation = array_intersect($associationNames, $fields);

        $normalFields = [];
        foreach ($fieldsWithoutAssociation as $field) {
            $normalFields[$field] = $criteria[$field];
        }

        $associativeFields = [];
        foreach ($fieldsWithAssociation as $field) {
            $associativeFields[$field] = $criteria[$field];
        }

        return $associativeFields;
    }

    /**
     * @param QueryBuilder $qb
     * @param $orderBy
     * @param string $alias
     *
     * @return QueryBuilder
     */
    protected function addOrderBy(QueryBuilder $qb, $orderBy, $alias = '')
    {
        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy(!empty($alias) ? sprintf('%s.%s', $alias, $field) : $field, $order);
            }
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param $criteria
     * @param $parameters
     * @param string $alias
     * @param $operator
     *
     * @return Andx|Orx
     */
    protected function getWhere(QueryBuilder $qb, $criteria, &$parameters, $alias = "", $operator = null)
    {
        return $this->where($qb, $criteria, $parameters, $alias, $operator);
    }

    /**
     * @param QueryBuilder $qb
     * @param $criteria
     * @param $parameters
     * @param string $alias
     * @param null $operator
     * @param null $pos
     *
     * @return Andx|Orx
     */
    protected function getWhereLike(QueryBuilder $qb, $criteria, &$parameters, $alias = "", $operator = null, $pos = null)
    {
        // ignore all the associations for 'like' where clause
        $this->splitAssociationFieldsOut($criteria, $normalFields);
        $pattern = $this->getLikePattern($pos);
        return $this->where($qb, $normalFields, $parameters, $alias, $operator, function (QueryBuilder $queryBuilder, $field, $varKey, $value, $hasAssociation, array &$params = []) use ($pattern) {
            // association field should not have a 'like' operator
            return $hasAssociation ? null : $this->getLike($queryBuilder, $pattern, $field, $value, $varKey, $params);
        });
    }

    /**
     * @param $qb
     * @param $where
     * @param $parameters
     * @param $extraCriteria
     * @param $alias
     *
     * @return Andx
     */
    protected function appendExtraField($qb, $where, &$parameters, array $extraCriteria, $alias)
    {
        $extraClause = $this->getWhere($qb, $extraCriteria, $extraParameter, $alias);
        if ($where) {
            $parameters = array_merge(empty($parameters) ? [] : $parameters, empty($extraParameter) ? [] : $extraParameter);
            return $qb->expr()->andX($where, $extraClause);
        } else {
            $parameters = $extraParameter;
            return $extraClause;
        }
    }

    /**
     * make sure that $criteria can be defined as below:
     * <code>
     *     $criteria([
     *          "foo" => "foo-value",                                                       // => foo = "foo-value"
     *          "bar" => ["value" => "bar-value", "operator" => "like", "pos" => "prefix"]  // => 'bar like "bar-value%"'
     *     ]);
     *
     *     // or
     *
     *     $criteria([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],                                     // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => ["value" => "bar-value", "operator" => "like", "pos" => "prefix"]  // => 'bar like "bar-value%"'
     *     ]);
     *
     *     // or
     *
     *     $criteria([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],                 // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => ["value" => "value", "operator" => "like"]     // => '(bar like "value%" or bar like "%value%" or bar like "%value")'
     *     ]);
     *
     *     // or
     *
     *     $criteria([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],                 // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => ["value" => "bar-value", "operator" => "eq"]   // => 'bar = "bar-value"'
     *     ]);
     *
     *     this is equivalent to:
     *
     *     $criteria([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],     // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => "bar-value"                        // => 'bar = "bar-value"'
     *     ]);
     *
     *     or
     *
     *     $criteria([
     *          "foo" => null,     // => 'foo is null'
     *     ]);
     * </code>
     *
     * @param QueryBuilder $qb
     * @param array $criteria
     *
     * @param $parameters
     * @param string $rootAlias
     * @param null $operator
     * @param callable $getClause
     *
     * @return Andx|Orx|null
     */
    private function where(QueryBuilder $qb, $criteria, &$parameters, $rootAlias = "", $operator = null, callable $getClause = null)
    {
        if (empty($criteria)) {
            return null;
        }

        $metadata = $this->getClassMetadata();
        $where = $this->getLogicExpression($qb, $operator);

        $parameters = [];
        $clauseParams = [];
        foreach ($criteria as $field => $value) {
            $hasAssociation = $metadata->hasAssociation($field);

            // remove any alias as parameter key doesn't need one
            $varKey = sprintf(':%s', $this->removeAlias($field));
            $fieldName = $this->attachAlias($field, $rootAlias);

            // this allow value to be an array
            if (is_array($value)) {
                $clause = $this->getWhereClauseFromArray($qb, $value, $fieldName, $varKey, $hasAssociation, $actualValue, $clauseParams);
                $parameters = array_merge($parameters, $clauseParams);
            } else if (is_null($value)) {
                $clause = $qb->expr()->isNull($fieldName);
            } else {
                $actualValue = $value;
                if (is_callable($getClause)) {
                    $clause = $getClause($qb, $fieldName, $varKey, $actualValue, $hasAssociation, $clauseParams);
                    $parameters = array_merge($parameters, $clauseParams);
                } else {
                    $clause = $qb->expr()->eq($fieldName, $varKey);
                    $parameters[$varKey] = $actualValue;
                }
            }

            if (empty($clause)) continue;

            $where->add($clause);

            // clear $clauseParams for being ready to use next time
            $clauseParams = [];
        }

        return $where->count() > 0 ? $where : null;
    }

    /**
     * This will allow value in criteria to be an array which contains
     *  1. only value list
     *  2. more specific details to determine what operators should be applied to
     *
     * @param QueryBuilder $qb
     * @param array $value
     * @param $field
     * @param $varKey
     * @param $hasAssociation
     * @param $actualValue
     * @param array $clauseParams
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|\Doctrine\ORM\Query\Expr\Func|Orx|null
     */
    private function getWhereClauseFromArray(QueryBuilder $qb, array $value, $field, $varKey, $hasAssociation, &$actualValue, &$clauseParams)
    {
        static $defaultOp = "eq";
        $clauseParams = [];

        if (!array_key_exists("value", $value)) {
            // only value list? put them into 'in' clause
            $clause = $qb->expr()->in($field, $varKey);
            $actualValue = $value;
            $clauseParams[$varKey] = $actualValue;
        } else {
            /** with more details, create corresponding clause based on operator specified. this allows value to be
             * defined as:
             *  [
             *      <field> => [
             *          "value" => <field value>,
             *          "operator" => <operator>  - logic operators supported by Doctrine Expr like "eq", "like", "in" and etc, default to 'eq'
             *           Note: with "is", the clause will be 'IS NULL' if <field value> is null, otherwise, it will be 'IS NOT NULL'
             *
             *          ["pos" => "prefix"|"middle"|"suffix"] - position value appears, mainly used by 'like', default to all of them in 'or' clause
             *      ]
             *  ]
             */
            $actualValue = $value["value"];
            $op = isset($value["operator"]) ? $value["operator"] : $defaultOp;
            switch ($op) {
                case 'like':
                    if (empty($actualValue)) {
                        return null;
                    }

                    if ($hasAssociation) {
                        throw new \InvalidArgumentException(sprintf('criteria field "%s" has an association related so "%s" is not suitable for it, please use "%s" instead.', $this->removeAlias($field), $op, $defaultOp));
                    }

                    if (!is_string($actualValue)) {
                        throw new \InvalidArgumentException(sprintf('Expecting a string for operator "%s" in criteria field "%s", but got "%s"', $op, $field, gettype($actualValue)));
                    }

                    $pos = isset($value["pos"]) ? $value["pos"] : "";
                    $pattern = $this->getLikePattern($pos);
                    $clause = $this->getLike($qb, $pattern, $field, $actualValue, $varKey, $clauseParams);
                    break;
                case 'is':
                    if (is_null($actualValue)) {
                        $clause = $qb->expr()->isNull($field);
                    } else {
                        $clause = $qb->expr()->isNotNull($field);
                    }
                    break;
                default:
                    if ($hasAssociation) {
                        // current field is an associative field? only 'eq' is most suitable for it, all others will be ignored
                        $clause = $qb->expr()->eq($field, $varKey);
                    } else {
                        // call to QueryBuilder's Expr with given method
                        $clause = call_user_func_array([$qb->expr(), $op], [$field, $varKey]);
                    }

                    $clauseParams[$varKey] = $actualValue;
                    break;
            }
        }

        return $clause;
    }

    /**
     * @param $field
     * @param string $alias
     *
     * @return bool
     */
    private function hasAlias($field, $alias = "")
    {
        $isPrefixed = strpos($field, ".") !== false;
        $prefixedByAlias = strpos($field, "$alias.") === 0;

        return empty($alias) ? $isPrefixed : $prefixedByAlias;
    }

    /**
     * @param $field
     * @param string $alias
     *
     * @return string
     */
    private function removeAlias($field, $alias = "")
    {
        $prefixed = $this->hasAlias($field, $alias);
        if (!$prefixed) {
            return $field;
        }

        if (!empty($alias)) {
            $updatedField = str_replace("$alias.", "", $field);
        } else {
            $pos = strpos($field, ".");
            $updatedField = substr($field, $pos + 1);
            $updatedField = $updatedField === false ? "" : $updatedField;
        }

        return $updatedField;
    }

    /**
     * @param $pos
     *
     * @return array|string
     */
    private function getLikePattern($pos)
    {
        switch (strtolower($pos)) {
            case "prefix":
                $pattern = "%s%%";      // -> "foo%"
                break;
            case "suffix":
                $pattern = "%%%s";      // -> "%foo"
                break;
            case "middle":
                $pattern = "%%%s%%";    // -> "%foo%"
                break;
            default:
                // by default all three above
                $pattern = ["%s%%", "%%%s%%", "%%%s"];
                break;
        }

        return $pattern;
    }

    /**
     * @param QueryBuilder $qb
     * @param $operator
     *
     * @return Andx|Orx
     */
    private function getLogicExpression(QueryBuilder $qb, $operator)
    {
        switch ($operator) {
            case "or":
                $where = $qb->expr()->orX();
                break;
            case "and":
            default:
                $where = $qb->expr()->andX();
                break;
        }

        return $where;
    }

    /**
     * Note: this method should not be called with associative fields
     *
     * @param QueryBuilder $qb
     * @param array $patterns
     * @param $field
     * @param $value
     * @param string $varKey
     * @param array $params
     *
     * @return Orx
     */
    private function getLikeOrx(QueryBuilder $qb, array $patterns, $field, $value, $varKey = "", array &$params)
    {
        $orx = $qb->expr()->orX();
        $index = 0;
        $params = [];
        foreach ($patterns as $p) {
            $key = sprintf('%s%d', $varKey, $index);
            $params[$key] = sprintf($p, $value);
            $orx->add($qb->expr()->like($field, sprintf('%s%d', $varKey, $index)));
            $index++;
        }

        return $orx;
    }

    /**
     * Note: this method should not be called with associative fields
     *
     * @param QueryBuilder $qb
     * @param $pattern
     * @param $field
     * @param $value
     * @param string $varKey
     * @param array $params
     *
     * @return \Doctrine\ORM\Query\Expr\Comparison|Orx
     */
    private function getLike(QueryBuilder $qb, $pattern, $field, $value, $varKey, array &$params)
    {
        if (is_array($pattern)) {
            return $this->getLikeOrx($qb, $pattern, $field, $value, $varKey, $params);
        } else {
            $params[$varKey] = sprintf($pattern, $value);
            return $qb->expr()->like($field, $varKey);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param null $where
     * @param array $parameters
     * @param string $alias
     */
    private function deletePhysically(QueryBuilder $qb, $where = null, $parameters = [], $alias = "")
    {
        $qb->delete($this->getEntityName(), $alias);
        if (!empty($where)) {
            $qb->where($where)
                ->setParameters($parameters);
        }

        $qb->getQuery()->execute();
    }
}