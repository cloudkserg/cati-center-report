<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 07.06.16
 * Time: 12:20
 */

namespace Report\GenPollComment\ModelBuilder\Operator;


use Export\Style\CellStyle;
use Export\Style\Merger;
use Report\Row\Context;
use Report\Row\EmptyRowModel;
use Report\Row\RowInterface;

class GroupHeaderBuilder
{

    private $_row;

    public function __construct(RowInterface $row)
    {
        $this->_row = $row;
    }

    public function createModel()
    {
        $model = new EmptyRowModel($this->_row, $this->getContext());
        $model->setDefaultStyle($this->getStyle());
        $model->setFormatters(array());
        return $model;
    }

    private function getContext()
    {
        return new Context(array(
            'full_listen' => 'Контроль',
            'full_listen_plan' => 'План прослушки',
            'time' => 'Время'
        ));
    }

    private function getStyle()
    {
        $style = CellStyle::create()->setBorder(true);
        $style->addMerger(new Merger(['full_listen', 'partial_listen', 'total_listen']));
        $style->addMerger(new Merger(['full_listen_plan', 'partial_listen_plan']));
        return $style;
    }

}