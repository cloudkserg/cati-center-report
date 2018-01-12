<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 26.06.15
 * Time: 15:35
 */

namespace Report\Template;


use Report\Row\RowModel;
use Report\Row\Row;

class ProjectTemplate extends RowTemplate
{
    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('title', 'Название', 'getTitle'));
        $row->addColumn($this->createColumn('value', 'Значение', 'getValue'));

        return $row;
    }


    /**
     * @param RowModel $row
     * @return mixed|null
     */
    public function getTitle($label, $value)
    {
        return $label;
    }

    /**
     * @param $label
     * @param $value
     * @param array|null $params
     * @return mixed
     */
    public function getValue($label, $value, $params = array())
    {
        if (!is_callable($value)) {
            return '';
        }

        if (!is_array($params)) {
            $params = array($params);
        }

        return call_user_func_array($value, $params);
    }


} 