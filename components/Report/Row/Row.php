<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 13:36
 */

namespace Report\Row;


class Row implements RowInterface
{
    /**
     * @var array
     */
    private $_columns = array();


    /**
     * @param ColumnInterface $column
     */
    public function addColumn(ColumnInterface $column)
    {
        $this->_columns[$column->getExportColumn()->getId()] = $column;
    }


    /**
     * @param array $columns
     */
    public function addColumns(array $columns)
    {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
    }



    /**
     * @param BlockInterface $block
     */
    public function addBlock(BlockInterface $block)
    {

        foreach ($block->getColumns(new Context()) as $column) {
            $this->addColumn($column);
        }

    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * @param $exportColumnId
     * @return ColumnInterface
     */
    public function getColumn($exportColumnId)
    {
        if (!isset($this->_columns[$exportColumnId])) {
            throw new \Exception(
                'Not found column with id = ' .$exportColumnId .
                ' columns = (' . print_r(array_keys($this->_columns), true) . ')'
            );
        }
        return $this->_columns[$exportColumnId];
    }


    /**
     * @return array
     */
    public function getExportColumns()
    {
        return array_map(function (ColumnInterface $column) {
            return $column->getExportColumn();
        }, $this->_columns);
    }


}