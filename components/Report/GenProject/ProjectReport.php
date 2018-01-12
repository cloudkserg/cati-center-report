<?php
namespace Report\GenProject;

use Carbon\Carbon;
use ExportComponent;
use Iterator;
use OperatorRecord;
use Report\Row\Context;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use GenProjectFilter;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Report\Report;
use Report\GenProject\Template\ProjectTemplate;

/**
 * OperatorReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectReport extends Report
{
    /**
     * createFilter
     *
     * @return GenProjectFilter
     *
     */
    protected function createFilter()
    {
        return new GenProjectFilter();
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
        $models = [];
        $totalModel = new TotalRowModel(
            $this->_row,
            new Context(array(
            'id' => '',
            'title' => 'Итого',
            'date.title' => function (Carbon $date) {
                return $date->format('d.m.Y');
            }
        )));

        foreach ($this->getProjects() as $project) {
            $model = new RowModel($this->_row, array($project));
            $totalModel->addModel($model);
            $models[] = $model;
        }

        $models[] = $totalModel;

        return $models;
    }


    /**
     * getProjects
     *
     * @return array
     */
    private function getProjects()
    {
        $builder = new OperatorRecordBuilder;
        $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
        );
        return $builder->getProjects();
    }



    /**
     * getTitles
     *
     * @return string
     */
    protected function getTitles()
    {
        $startDate = Carbon::createFromTimestamp($this->_filter->startTimestamp);
        $endDate = Carbon::createFromTimestamp($this->_filter->endTimestamp);
        return  array(
            "Отчет по проектам от {$startDate} по {$endDate}"
        );
    }


    protected function getTemplate()
    {
        return new ProjectTemplate($this->_filter);
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
            'duration' => array('DurationHelper', 'formatMinutes'),
            'price' => array('DecimalHelper', 'formatDecimal'),
            'callPrice' => array('DecimalHelper', 'formatDecimal'),
            'poll' => array('DecimalHelper', 'formatDecimal'),

            'date.time' => array('DurationHelper', 'formatHours'),
            'date.duration' => array('DurationHelper', 'formatMinutes'),
            'date.price' => array('DurationHelper', 'formatMinutes'),


        );
    }




}
