<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 12:45
 */

namespace Report\Row;



interface BlockInterface
{


    /**
     * @return mixed
     */
    public function getId();

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
     * @param Context $context
     * @return array
     */
    public function getColumns(Context $context);


    /**
     * @param TotalColumn $column
     * @return mixed
     */
    public function addTotalColumn(TotalColumn $column);

    /**
     * @param $columnId
     * @return ColumnInterface[]
     */
    public function getColumnsById($columnId);


}