<?php
namespace Report;

use Carbon\Carbon;
use OperatorReportFilter;
use OperatorRecord;
use Report\Template\OperatorTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Project;
/**
 * OperatorReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class OperatorReport extends Report
{


    /**
     * @var OperatorReportFilter
     */
    protected $_filter;

    /**
     * createFilter
     *
     * @return OperatorReportFilter
     *
     */
    protected function createFilter()
    {
        return new OperatorReportFilter();
    }

    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $builder = new OperatorRecordBuilder;
        $builder->forModel(
            OperatorRecord::model()
            ->applySearch($this->_filter)
        );
        return $builder->getOperators();
    }



    /**
     * getTitles
     *
     * @return string
     */
    protected function getTitles()
    {
        $project = Project::model()->findByPK($this->_filter->project_id);
        $startDate = Carbon::createFromTimestamp($this->_filter->startTimestamp)->format('d.m.Y');
        $endDate = Carbon::createFromTimestamp($this->_filter->endTimestamp)->format('d.m.Y');
        return  array(
            "Отчет по операторам с {$startDate} по {$endDate}",
            "Название проекта: {$project->title}",
            "Тип оплаты: {". \PaymentType::model()->getTitle($project->payment_type) . "}",
            "Расходы, стоимость успешной анкеты для оператора, руб.: {" . $project->poll_cost . "}",
            "Расходы, стоимость часа работы оператора, руб.: {" . $project->hour_cost . "}"
        );
    }


    protected function getTemplate()
    {
        return new OperatorTemplate($this->_filter);
    }


    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
            'time' => array('DurationHelper', 'formatHours'), 
            'duration' => array('DurationHelper', 'formatHours'),
            'activeDuration' => array('DurationHelper', 'formatHours'),
            'payDuration' => array('DurationHelper', 'formatHours'),
            'pollSpeed' => array('DecimalHelper', 'formatDecimal'),
            'poll' => array('DecimalHelper', 'formatDecimal'),
            'payment' => array('DecimalHelper', 'formatDecimal'),
            'availDuration' => array('DurationHelper', 'formatHours'),
            'callLimitFinishDuration' => array('DurationHelper', 'formatHours'),
            'callFinishDuration' => array('DurationHelper', 'formatHours'),
            'performPlan' => array('DecimalHelper', 'formatDecimal'),
            'notActiveDuration' => array('DurationHelper', 'formatHours'),

            'date.duration' => array('DurationHelper', 'formatHours'),
            'date.activeDuration' => array('DurationHelper', 'formatHours'),
            'date.payDuration' => array('DurationHelper', 'formatHours'),
            'date.pollSpeed' => array('DecimalHelper', 'formatDecimal'),
            'date.rate' => array('DecimalHelper', 'formatDecimal'),
            'date.poll' => array('DecimalHelper', 'formatDecimal'),
            'date.payment' => array('DecimalHelper', 'formatDecimal'),
            'date.availDuration' => array('DurationHelper', 'formatHours'),
            'date.callLimitFinishDuration' => array('DurationHelper', 'formatHours'),
            'date.callFinishDuration' => array('DurationHelper', 'formatHours'),
            'date.performPlan' => array('DecimalHelper', 'formatDecimal'),
            'date.performPlanFormula' => array('DecimalHelper', 'formatPoints'),
            'date.formula' => array('DecimalHelper', 'formatPoints'),
            'date.notActiveDuration' => array('DurationHelper', 'formatHours')
        );
    }




}
