<?php
namespace Report;

use Iterator;
use ProjectReportFilter;
use Report\Row\Context;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\Template\ProjectPaymentTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use OperatorRecord;
use Project;
use Carbon\Carbon;

/**
 * ProjectPaymentReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectPaymentReport extends Report
{


    /**
     * _project
     *
     * @var \Project
     */
    private $_project;


    /**
     * createFilter
     *
     * @return ProjectReportFilter
     *
     */
    protected function createFilter()
    {
        return new ProjectReportFilter();
    }

    /**
     * getProject
     *
     * @return \Project
     */
    protected function getProject()
    {
        if (!isset($this->_project)) {
            $this->_project = Project::model()->findByPk($this->_filter->project_id);
        }
        return $this->_project;
    }

    /**
     * @return array
     */
    public function getTitles()
    {
        $project = $this->getProject();

        $models = [];
        $startDate = Carbon::createFromTimestamp($this->_filter->startTimestamp)->format('d.m.Y');
        $endDate = Carbon::createFromTimestamp($this->_filter->endTimestamp)->format('d.m.Y');
        $models[] = "Отчет по проекту с {$startDate} по {$endDate}";
        $models[] = "Название проекта: {$project->title}";
        $models[] = "Тип оплаты: {". \PaymentType::model()->getTitle($project->payment_type) . "}";
		$models[] =  "Расходы, стоимость успешной анкеты для оператора, руб.: [" . $project->poll_cost . "]";
        $models[] = "Расходы, стоимость часа работы оператора, руб.: [" . $project->hour_cost . "]";
        return $models;
    }

    /**
     * getModels
     *
     * Operator::model()->findAll();
     *
     * @return Iterator
     */
    protected function getModels()
    {
        $dates = $this->getDates();

        $models = [];
        $totalModel = new TotalRowModel($this->_row, new Context([
           'date' => 'Итого'
        ]));
        foreach ($dates as $date) {
            $model = new RowModel(
                $this->_row,
                array(
                    $date->copy()
                )
            );
            $totalModel->addModel($model);
            $models[] = $model;
        }

        $models[] = $totalModel;

        return $models;
    }

    /**
     * getDates
     *
     * @return Carbon[]
     */
    protected function getDates()
    {
        $builder = new OperatorRecordBuilder();
        $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
        );
        return $builder->addOrder('date ASC')->getDates();
    }

    protected function getTemplate()
    {
        return new ProjectPaymentTemplate($this->_filter);
    }


    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
            'payDuration' => array('DurationHelper', 'formatHours'),
            'callPrice' => array('DecimalHelper', 'formatDecimal'),
            'poll' => array('DecimalHelper', 'formatDecimal'),
            'payment' => array('DecimalHelper', 'formatDecimal'),
            'companyPrice' => array('DecimalHelper', 'formatDecimal'),
            'totalPrice' => array('DecimalHelper', 'formatDecimal'),
        );
    }



}
