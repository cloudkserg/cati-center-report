<?php
namespace Report;

use Export\Cell\Cell;
use Export\Style\CellStyle;
use Export\Style\Color;
use OperatorRecord;
use Report\Helper\ItemsHelper;
use Report\Helper\ReportHelper;
use Report\Row\EmptyRowModel;
use Report\Row\ModelInterface;
use Report\Template\ControlOperatorTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Carbon\Carbon;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\Row\Context;
use Stat\PayDuration\PayDurationBuilder;
use Operator;
use Stat\Payment\Period\PeriodInterface;

/**
 * ControlOperatorReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ControlOperatorReport extends Report
{

    /**
     * @var \OperatorControlOperatorFilter
     */
    protected $_filter;


    /**
     * createFilter
     *
     * @return \OperatorControlOperatorFilter
     *
     */
    protected function createFilter()
    {
        return new \OperatorControlOperatorFilter();
    }


    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $models = array();
        foreach ($this->getOperators() as $operator) {
            $models[] = $operator;
        }
        return $models;
    }

    /**
     * @return \Operator[]
     */
    private function getOperators()
    {
        return Operator::model()
            ->forCompany($this->_filter->company_id)
            ->applySearch($this->_filter)
            ->findAll();
    }





    /**
     * @return GenOperatorTemplate|Template\RowTemplate
     */
    protected function getTemplate()
    {
        return new ControlOperatorTemplate($this->_filter);
    }
    
    /**
     * getTitles
     *
     * @return array
     */
    protected function getTitles()
    {
        return  array(
            "Отчет по операторам",
            "Всего " . $this->getCountModels() . " операторов."
        );
    }


    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
        );
    }




}
