<?php
namespace Repository;

use Doctrine\Common\Persistence\ObjectRepository;

interface EntityRepositoryInterface extends CountableRepositoryInterface, ObjectRepository
{
    /**
     * find all possible entities with "like" where clause
     *
     * @param array $criteria  the usage is same as above
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @param string $operator default to "and"
     * @param string $pos      default to all positions, "prefix", "middle" and "suffix"
     *
     * @return array
     */
    public function findLike(array $criteria = [], $orderBy = [], $limit = null, $offset = null, $operator = null, $pos = null);

    /**
     * @param array $ids
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function findByIds(array $ids, $orderBy = [], $limit = null, $offset = null);

    /**
     * physically delete multiple entities based on given criteria. If no criteria are given, it will delete all from
     * table.
     *
     * @param array $criteria same as above
     * @param null $operator
     *
     * @return void
     */
    public function deleteBy(array $criteria = [], $operator = null);

    /**
     * @param $id
     *
     * @return void
     */
    public function deleteById($id);

    /**
     * @param array $ids
     *
     * @return void
     */
    public function deleteByIds(array $ids);

    /**
     * a shortcut method to deleteBy
     *
     * @return void
     */
    public function deleteAll();
}