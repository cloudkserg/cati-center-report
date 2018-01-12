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
 * Class EmptyRowModel
 * @package Report\Row
 */
class EmptyRowModel extends RowModel
{




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
    public function __construct(RowInterface $row, Context $context, array $params = array())
    {
        $this->_row = $row;
        $this->_params = $params;
        $this->_context = $context;
    }


    /**
     * @param ColumnInterface $column
     * @return bool
     */
    private function isCustomColumn(ColumnInterface $column)
    {
        return ($this->_context->getParam($column->getId()) !== null);
    }


    /**
     * @param ColumnInterface $column
     * @return mixed
     */
    private function customColumn(ColumnInterface $column)
    {
        $value = $this->_context->getParam($column->getId());
        if (!is_callable($value)) {
            return $value;
        }

        return call_user_func_array(
            $value,
            array_merge(
                $column->getContext()->getParams(),
                [$this]
            )
        );
    }


    /**
     * @param ColumnInterface $column
     * @return mixed
     */
    protected function getRawValue(ColumnInterface $column)
    {
        if ($this->isCustomColumn($column)) {
            return $this->customColumn($column);
        }
        return '';
    }


}