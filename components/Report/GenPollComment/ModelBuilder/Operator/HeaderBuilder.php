<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 07.06.16
 * Time: 12:29
 */

namespace Report\GenPollComment\ModelBuilder\Operator;


use Export\Style\CellStyle;
use Report\Row\Context;
use Report\Row\EmptyRowModel;
use Report\Row\RowInterface;

class HeaderBuilder
{
    /**
     * @var RowInterface
     */
    private $_row;


    /**
     * @param RowInterface $row
     */
    public function __construct(RowInterface $row)
    {
        $this->_row = $row;
    }


    /**
     * @return EmptyRowModel
     */
    public function createModel()
    {
        $model = new EmptyRowModel($this->_row, $this->getContext());
        $model->setDefaultStyle($this->getStyle());
        $model->setFormatters(array());
        return $model;
    }

    /**
     * @return Context
     */
    private function getContext()
    {
        return new Context(array(
            'project' => 'Проект',
            'medium_duration' => 'Средняя длина анкеты',
            'number' => '№',
            'class' => 'Класс',
            'fio' => 'Оператор',
            'poll' => 'Анкет',
            'full_listen' => 'Полный',
            'partial_listen' => 'Часть',
            'total_listen' => 'Итого',
            'listen_percent' => '%',
            'defect' => 'Принято с браком',
            'reject' => 'Не принято',
            'total_defect' => 'Итого с браком',
            'defect_percent' => '%',
            'full_listen_plan' => 'Полный',
            'partial_listen_plan' => 'Часть',
            'time' => ''
        ));
    }

    /**
     * @return CellStyle
     */
    private function getStyle()
    {
        return CellStyle::create()->setBorder(true);
    }

}