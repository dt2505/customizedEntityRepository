<?php
namespace Repository;

use Doctrine\ORM\Query\Expr\Andx;

class RemovableEntityRepository extends BaseEntityRepository implements RemovableEntityRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function removeBy(array $criteria = [], $operator = null, \DateTime $removedAt = null)
    {
        $this->remove(["removed" => true, "removedAt" => $removedAt ?: new \DateTime()], $criteria, $operator);
    }

    /**
     * @inheritdoc
     */
    public function removeById($id, \DateTime $removedAt = null)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Id is required.');
        }

        $this->removeBy(['id' => $id], null, $removedAt);
    }

    /**
     * @inheritdoc
     */
    public function removeByIds(array $ids, \DateTime $removedAt = null)
    {
        if (empty($ids)) {
            throw new \InvalidArgumentException('Id list is required.');
        }

        $this->removeBy(["id" => $ids], null, $removedAt);
    }

    /**
     * @inheritdoc
     */
    public function removeAll(\DateTime $removedAt = null)
    {
        $this->removeBy([], null, $removedAt);
    }

    /**
     * @inheritdoc
     */
    public function countRemovedBy(array $criteria = [], $operator = null)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $where = $this->getWhere($qb, $criteria, $parameters, $alias, $operator);
        $finalWhere = $this->appendRemovableField($qb, $where, $parameters, true, $alias);

        return $this->countRecordsBy($qb, $finalWhere, $parameters, $alias);
    }

    /**
     * @inheritdoc
     */
    public function countUnremovedBy(array $criteria = [], $operator = null)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $where = $this->getWhere($qb, $criteria, $parameters, $alias, $operator);
        $finalWhere = $this->appendRemovableField($qb, $where, $parameters, false, $alias);

        return $this->countRecordsBy($qb, $finalWhere, $parameters, $alias);
    }

    /**
     * @inheritdoc
     */
    public function countRemovedLike(array $criteria = [], $operator = null, $pos = null)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $whereLike = $this->getWhereLike($qb, $criteria, $parameters, $alias, $operator, $pos);
        $finalWhere = $this->appendRemovableField($qb, $whereLike, $parameters, true, $alias);

        return $this->countRecordsBy($qb, $finalWhere, $parameters, $alias);
    }

    /**
     * @inheritdoc
     */
    public function countUnremovedLike(array $criteria = [], $operator = null, $pos = null)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $whereLike = $this->getWhereLike($qb, $criteria, $parameters, $alias, $operator, $pos);
        $finalWhere = $this->appendRemovableField($qb, $whereLike, $parameters, false, $alias);

        return $this->countRecordsBy($qb, $finalWhere, $parameters, $alias);
    }

    /**
     * @inheritdoc
     */
    public function findUnremovedBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findBy(array_merge($criteria, ['removed' => false]), $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     */
    public function findUnremovedByIds(array $ids, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->findUnremovedBy(['id' => $ids], $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     */
    public function findRemovedBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findBy(array_merge($criteria, ['removed' => true]), $orderBy, $limit, $offset);
    }

    /**
     * @inheritdoc
     */
    public function findUnremovedLike(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null)
    {
        if (empty($criteria)) {
            return $this->findUnremovedBy($criteria, $orderBy, $limit, $offset);
        }

        return $this->findRecordsWithRemovableFieldAttached($criteria, $orderBy, $limit, $offset, $operator, $pos);
    }

    /**
     * @inheritdoc
     */
    public function findRemovedLike(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null)
    {
        if (empty($criteria)) {
            return $this->findRemovedBy($criteria, $orderBy, $limit, $offset);
        }

        return $this->findRecordsWithRemovableFieldAttached($criteria, $orderBy, $limit, $offset, $operator, $pos, true);
    }

    /**
     * @param $qb
     * @param $where
     * @param $parameters
     * @param $removableFieldValue
     * @param $alias
     *
     * @return Andx
     */
    protected function appendRemovableField($qb, $where, &$parameters, $removableFieldValue, $alias)
    {
        return parent::appendExtraField($qb, $where, $parameters, ['removed' => ["value" => $removableFieldValue, "operator" => "eq"]], $alias);
    }

    /**
     * this ensures that clause 'removed = ?' will be appended with 'and' operator
     * e.g. ... where (..) and removed = ?
     *
     * @param array $criteria
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @param null $operator
     * @param null $pos
     * @param bool $removed
     *
     * @return array
     */
    private function findRecordsWithRemovableFieldAttached(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null, $removed = false)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $whereLike = $this->getWhereLike($qb, $criteria, $parameters, $alias, $operator, $pos);
        $where = $this->appendRemovableField($qb, $whereLike, $parameters, $removed, $alias);

        return $this->getResults($this->addOrderBy($qb, $orderBy, $alias), $where, $parameters, $offset, $limit);
    }

    /**
     * remove entities by marking them as removed
     *
     * @param array $fieldsToSet what fields should be set when updating the entities
     * @param array $criteria
     * @param null $operator
     *
     * @internal param $field
     */
    private function remove($fieldsToSet, $criteria = [], $operator = null)
    {
        $qb = $this->createQueryBuilder($alias = 'e');
        $where = $this->getWhere($qb, $criteria, $parameters, $alias, $operator);
        $this->bulkUpdate($qb, $fieldsToSet, $alias, $where, $parameters);
    }
}