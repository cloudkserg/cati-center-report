<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 13:33
 */

namespace Report\Row;


interface RowInterface
{

    /**
     * @param ColumnInterface $column
     * @return $this
     */
    public function addColumn(ColumnInterface $column);

    /**
     * @param BlockInterface $block
     * @return $this
     */
    public function addBlock(BlockInterface $block);

    /**
     * @param mixed $row
     * @return ColumnInterface[]
     */
    public function getColumns();

    /**
     * @return \Export\Schema\Column[]
     */
    public function getExportColumns();

    /**
     * @param array $columns
     * @return void
     */
    public function addColumns(array $columns);


    /**
     * @param $exportColumnId
     * @return ColumnInterface
     */
    public function getColumn($exportColumnId);



}