<?php
namespace Repository;

interface RemovableEntityRepositoryInterface extends EntityRepositoryInterface
{
    /**
     * remove multiple entities based on given criteria by marking them as removed. If no criteria are given, it will
     * mark all entities as removed
     *
     * the usage of $criteria:
     * <code>
     *     $repo->removeBy([
     *          "foo" => "foo-value",                                                       // => foo = "foo-value"
     *          "bar" => ["value" => "bar-value", "operator" => "like", "pos" => "prefix"]  // => 'bar like "bar-value%"'
     *     ]);
     *
     *     // or
     *
     *     $repo->removeBy([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],                                     // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => ["value" => "bar-value", "operator" => "like", "pos" => "prefix"]  // => 'bar like "bar-value%"'
     *     ]);
     *
     *     // or
     *
     *     $repo->removeBy([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],                 // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => ["value" => "value", "operator" => "like"]     // => '(bar like "value%" or bar like "%value%" or bar like "%value")'
     *     ]);
     *
     *     // or
     *
     *     $repo->removeBy([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],                 // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => ["value" => "bar-value", "operator" => "eq"]   // => 'bar = "bar-value"'
     *     ]);
     *
     *     this is equivalent to:
     *
     *     $repo->removeBy([
     *          "foo" => ["v1", "v2", "v3", "v4", ...],     // => 'foo in ("v1", "v2", "v3", "v4", ...)'
     *          "bar" => "bar-value"                        // => 'bar = "bar-value"'
     *     ]);
     *
     *     or
     *
     *     $repo->removeBy([
     *          "foo" => null,     // => 'foo is null'
     *     ]);
     * </code>
     *
     * @param array $criteria
     * @param null $operator default to "and"
     * @param \DateTime|null $removedAt default to current date-time
     *
     * @return
     */
    public function removeBy(array $criteria = [], $operator = null, \DateTime $removedAt = null);

    /**
     * @param $id
     * @param \DateTime|null $removedAt
     *
     * @return void
     */
    public function removeById($id, \DateTime $removedAt = null);

    /**
     * @param array $ids
     * @param \DateTime|null $removedAt
     *
     * @return void
     */
    public function removeByIds(array $ids, \DateTime $removedAt = null);

    /**
     * @param \DateTime|null $removedAt
     *
     * @return void
     */
    public function removeAll(\DateTime $removedAt = null);

    /**
     * @param array $criteria same as above
     * @param null $operator
     *
     * @return int
     */
    public function countRemovedBy(array $criteria = [], $operator = null);

    /**
     * @param array $criteria same as above
     * @param null $operator
     *
     * @return int
     */
    public function countUnremovedBy(array $criteria = [], $operator = null);

    /**
     * @param array $criteria
     * @param null $operator
     * @param null $pos
     *
     * @return int
     */
    public function countRemovedLike(array $criteria = [], $operator = null, $pos = null);

    /**
     * @param array $criteria
     * @param null $operator
     * @param null $pos
     *
     * @return int
     */
    public function countUnremovedLike(array $criteria = [], $operator = null, $pos = null);

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function findUnremovedBy(array $criteria, array $orderBy = null, $limit = null, $offset = null);

    /**
     * @param array $ids
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function findUnremovedByIds(array $ids, array $orderBy = null, $limit = null, $offset = null);

    /**
     * @param array $criteria
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function findRemovedBy(array $criteria, array $orderBy = null, $limit = null, $offset = null);

    /**
     * @param array $criteria
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @param null $operator
     * @param null $pos
     *
     * @return array
     */
    public function findUnremovedLike(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null);

    /**
     * @param array $criteria
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @param null $operator
     * @param null $pos
     *
     * @return array
     */
    public function findRemovedLike(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null);
}