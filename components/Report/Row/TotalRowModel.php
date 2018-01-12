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
 * Class TotalRowModel
 * @package Report\Row
 */
class TotalRowModel extends RowModel
{



    /**
     * @var callable
     */
    private $_checkModel;

    /**
     * @var array
     */
    private $_models = [];


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
    public function __construct(RowInterface $row, Context $context, array $params = array(), callable $checkModel = null)
    {
        if (!isset($checkModel)) {
            $checkModel = function () { return true; };
        }

        $this->_row = $row;
        $this->_params = $params;
        $this->_checkModel = $checkModel;
        $this->_context = $context;
    }




    /**
     * @param mixed $model
     */
    public function addModel($model)
    {
        if (call_user_func($this->_checkModel, $model)) {
            $this->_models[] = $model;
        }
    }

    /**
     * @param array $models
     */
    public function addModels(array $models)
    {
        foreach ($models as $model) {
            $this->addModel($model);
        }
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
     * @return array
     */
    private function getValues(ColumnInterface $column)
    {
        return array_map(function (ModelInterface $model) use ($column) {
            return $model->getValue($column);
        }, $this->_models);
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
                [$this->getValues($column), $this]
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
        return ItemsHelper::sum($this->getValues($column));
    }


}