<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 02.07.15
 * Time: 16:52
 */

namespace Report;

use Report\Row\ColumnInterface;
use Report\Row\ModelInterface;
use Report\Row\RowModel;
use Report\Template\RowTemplate;

class Data
{
    /**
     * @var RowModel[]
     */
    protected $_items = array();

    /**
     * @var \Report\Row\RowInterface
     */
    private $_row;

    /**
     * @var RowTemplate
     */
    protected $_template;

    /**
     * @param RowTemplate $template
     */
    public function __construct(RowTemplate $template)
    {
        $this->_template = $template;
        $this->_row = $template->getRow();
        //update columns
        $this->_row->getColumns();
        $this->generateItems();
    }

    /**
     * @return RowTemplate
     */
    public function getTemplate()
    {
        return $this->_template;
    }


    /**
     * @return array
     */
    protected function generateItems()
    {
        return array();
    }

    /**
     * @param mixed $item
     * @param null|mixed $key
     */
    public function addItem($item, $key = null)
    {
        $key = (isset($key) ? (string)$key : count($this->_items));
        $this->_items[$key] = $this->createRowModel($item);
    }


    /**
     * @param array $items
     */
    public function addItems(array $items)
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
    }


    /**
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * @param $exportColumnId
     * @param bool $isAsc
     */
    public function sortByColumn($exportColumnId , $isAsc = true)
    {
        $column = $this->_row->getColumn($exportColumnId);

        $this->fillValues($column);

        uasort($this->_items, function (RowModel $item1, RowModel $item2) use ($column, $isAsc) {
            $value1 = $item1->getValue($column);
            $value2 = $item2->getValue($column);
            if ($value1 == $value2) {
                return 0;
            }

            $sign = $isAsc ? 1 : -1;
            $mark =  ($value1 < $value2) ? -1 : 1;

            return $sign * $mark;
        });
    }

    /**
     * @param ColumnInterface $column
     */
    private function fillValues(ColumnInterface $column)
    {
        foreach ($this->_items as $item) {
            $item->getValue($column);
        }
    }


    /**
     * @return \Report\Row\RowInterface
     */
    public function getRow()
    {
        return $this->_row;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getIndexByKey($key)
    {
        return array_search($key, array_keys($this->_items));
    }

    /**
     * @param $key
     * @return RowModel
     * @throws \Exception
     */
    public function getModelByKey($key)
    {
        if (!isset($this->_items[$key])) {
            throw new \Exception('Not found item by key = ' .$key);
        }
        return $this->_items[$key];
    }

    /**
     * @param ModelInterface $model
     * @param $columnId
     * @return mixed
     */
    public function getValue(ModelInterface $model, $columnId)
    {
        return $model->getValue($this->_row->getColumn($columnId));
    }


    /**
     * @param array $model
     * @return RowModel[]
     */
    private function createRowModel($model)
    {
        if (!$model instanceof ModelInterface) {
            $model =  new RowModel($this->_row, array($model));
        }

        return $model;
    }







}