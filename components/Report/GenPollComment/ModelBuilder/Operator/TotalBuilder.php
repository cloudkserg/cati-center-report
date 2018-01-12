<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 07.06.16
 * Time: 12:15
 */

namespace Report\GenPollComment\ModelBuilder\Operator;


use Export\Cell\Cell;
use Export\Style\CellStyle;
use Export\Style\Color;
use Export\Style\Merger;
use Report\Helper\ReportHelper;
use Report\Row\Context;
use Report\Row\RowInterface;
use Report\Row\TotalRowModel;

class TotalBuilder
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
     * @return TotalRowModel
     */
    public function createModel()
    {
        $model =new TotalRowModel(
            $this->_row, $this->getContext()
        );
        $model->setDefaultStyle($this->getStyle());
        return $model;
    }


    private function getContext()
    {
        return new Context(array(
            'number' => 'Итого',
            'fio' => '',
            'medium_duration' => '',
            'class' => '',
            'listen_percent' => function ($params, $rowModel) {
                return $this->getListenPercent($rowModel);
            },
            'defect_percent' => function ($params, $rowModel) {
                return Cell::create(
                    $this->getDefectPercent($rowModel)
                )->setStyle($this->getColorStyle());
            },
            'time' => '',
            'full_listen_plan' => '',
            'partial_listen_plan' => ''
        ));
    }

    private function getListenPercent($rowModel)
    {
        $poll = ReportHelper::getValue($rowModel, 'poll');
        if ($poll == 0) {
            return 0;
        }
        return ReportHelper::getValue($rowModel, 'total_listen') / $poll;
    }

    private function getDefectPercent($rowModel)
    {
        $listen = ReportHelper::getValue($rowModel, 'total_listen');
        if ($listen == 0) {
            return 0;
        }
        return ReportHelper::getValue($rowModel, 'total_defect') / $listen;
    }

    private function getStyle()
    {
        $style = CellStyle::create()->setBorder(true);
        $style->addMerger(new Merger(array('number', 'fio')));
        return $style;
    }

    private function getColorStyle()
    {
        return CellStyle::create()->setColor(Color::YELLOW)->setBorder(true);
    }

}