<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 08.07.15
 * Time: 18:04
 */

namespace Report\Row;

use Export\Cell\Cell;


interface ModelInterface
{

    /**
     * @return callable[]|null
     */
    public function getFormatters();


    /**
     * @return Context
     */
    public function getContext();

    /**
     * @return array
     */
    public function getParams();

    /**
     * @param ColumnInterface $column
     * @return mixed
     */
    public function getValue(ColumnInterface $column);

    /**
     * @param ColumnInterface $column
     * @return Cell
     */
    public function getCell(ColumnInterface $column);

    /**
     * @return RowInterface
     */
    public function getRow();


    /**
     * @param string $exportColumnId
     * @return ColumnInterface
     */
    public function getColumn($exportColumnId);

} 