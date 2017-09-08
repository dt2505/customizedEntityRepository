<?php
namespace Repository;

interface CountableRepositoryInterface
{
    /**
     * @return int
     */
    public function count();

    /**
     * @param array $criteria
     * @param null $operator
     *
     * @return int
     */
    public function countBy(array $criteria = [], $operator = null);

    /**
     * @param array $criteria
     * @param null $operator
     * @param null $pos
     *
     * @return int
     */
    public function countLike(array $criteria = [], $operator = null, $pos = null);
}