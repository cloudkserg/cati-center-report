<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 02.07.15
 * Time: 13:44
 */

namespace Report\Row;
use Report\Helper\ItemsHelper;

/**
 * Class NumberRowModel
 * @package Report\Row
 */
class NumberRowModel extends RowModel
{

    /**
     * @var null|callable[]
     */
    protected $_formatters = array();

    private $_index = 0;


    /**
     * @param Context $context
     *
     * for custom columns
     * columnId => string
     * columnId => function ($columnParams, $paramsFromTotalRow)
     *
     * @param array $params
     * @param callable $checkModel
     */
    public function __construct(RowInterface $row)
    {
        $this->_row = $row;
    }


    /**
     * @param ColumnInterface $column
     * @return mixed
     */
    protected function getRawValue(ColumnInterface $column)
    {
        return ++$this->_index;
    }


}