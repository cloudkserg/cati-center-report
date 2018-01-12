<?php
namespace Report\GenProject;

use Export\Style\Color;
use Export\Style\CellStyle;
use Export\Cell\Cell;
use Iterator;
use Report\Row\Context;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\GenProject\Template\PaymentDayTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use OperatorRecord;
use Carbon\Carbon;
use Project;
use GenProjectFilter;
use Report\Report;

/**
 * PaymentDayReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class PaymentDayReport extends Report
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
     * @return array
     */
    public function getTitles()
    {

        $startDate = Carbon::createFromTimestamp($this->_filter->startTimestamp)->format('d.m.Y');
        $endDate = Carbon::createFromTimestamp($this->_filter->endTimestamp)->format('d.m.Y');
        return array(array("title" =>  "Отчет от {$startDate} по {$endDate}"));

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

        $dates = $this->getDates();
        $totalModel = new TotalRowModel($this->_row, new Context([
            'title' => 'Итого',
            'paymentType' => '',
            'pollCost' => '',
            'hourCost' => '',
            'pollRate' => '',
            'date' => '',
            'totalProfitPercent' => '',
            'totalProfit' => function (array $results) {
                $value = array_sum($results);
                return $this->colorValue($value);
            }
        ]));
        $totalModel->setDefaultStyle($this->getRowStyle());
        $totalModel->setFormatters($this->getTotalFormatters());
        foreach ($dates as $date) {
            $this->addDateModels($date, $models, $totalModel);
        }
        $models[] = $totalModel;

        return $models;
    }

    /**
     * addDateModels
     *
     * @param Carbon $date
     * @param array $models
     * @return void
     */
    private function addDateModels(Carbon $date, array &$models, TotalRowModel &$totalModel)
    {
        $dateModel = new TotalRowModel($this->_row, new Context([
            'date' => $date->format('d.m.Y'),
            'title' => '',
            'pollRate' => '',
            'pollRevenue' => '',
            'totalProfitPercent' => function (array $results) {
                $count = count(array_filter($results, function ($el) { return $el != 0; }));
                if ($count == 0 ) {
                    return 0;
                }
                return array_sum($results)/$count;
            },
            'totalProfit' => function (array $results) {
                $value = array_sum($results);
                return $this->colorValue($value, Color::GRAY);
            }
        ]));
        $dateModel->setDefaultStyle($this->getDateStyle());
        $dateModel->setFormatters($this->getDateFormatters());
        $models[] = $dateModel;

        $projects = $this->getProjects($date);
        foreach ($projects as $project) {
            $model = new RowModel($this->_row, array($date, $project));
            $model->setDefaultStyle($this->getRowStyle());
            $dateModel->addModel($model);
            $totalModel->addModel($model);

            $models[] = $model;
        }
    }
    
    /**
     * colorValue
     *
     * @param int $value
     * @return Cell
     */
    private function colorValue($value, $colorBackground = null)
    {
        $cell = Cell::create($value);
        $cell->setStyle(CellStyle::create($colorBackground)->setBorder(true));
        if ($value <= 0) {
            $cell->getStyle()->setColorText(Color::RED);
        }
        return $cell;
    }



    private function getRowStyle()
    {
        return CellStyle::create()->setBorder(true);
    }

    private function getDateStyle()
    {
        return CellStyle::create(Color::GRAY)
           ->setBorder(true); 
    }
    
    /**
     * getTotalFormatters
     *
     * @return array
     */
    private function getTotalFormatters()
    {
        return array_merge(
            $this->getFormatters(),
            array(
                'pollRate' => null,
                'pollRevenue' => null,
                'totalProfitPercent' => null,
                'pollCost' => array('DecimalHelper', 'formatDecimal'),
                'hourCost' => array('DecimalHelper', 'formatDecimal')
           )
        );
    
    }

    /**
     * getDateFormatters
     *
     * @return array
     */
    private function getDateFormatters()
    {
        return array_merge(
            $this->getFormatters(),
            array(
                'pollRate' => null,
                'pollRevenue' => null,
                'pollCost' => array('DecimalHelper', 'formatDecimal'),
                'hourCost' => array('DecimalHelper', 'formatDecimal')
           )
        );
    
    }

    /**
     * @return Carbon[]
     */
    private function getDates()
    {
        return OperatorRecordBuilder::create()
            ->forModel(
                OperatorRecord::model()
                ->applySearch($this->_filter)
            )->addOrder('date ASC')->getDates();
    }

    /**
     * getProjects
     *
     * @return \Project[]
     */
    private function getProjects(Carbon $date)
    {
        $builder = new OperatorRecordBuilder();
        $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
                ->forDate($date)
        );
        return $builder->getProjects();
    }

    protected function getTemplate()
    {
        return new PaymentDayTemplate($this->_filter);
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
            'payment' => array('DecimalHelper', 'formatRound'),
            'companyPrice' => array('DecimalHelper', 'formatRound'),
            'totalPrice' => array('DecimalHelper', 'formatRound'),
            'pollRate' => array('DecimalHelper', 'formatDecimal'),
            'poll' => array('DecimalHelper', 'formatDecimal'),
            'totalProfit' => function ($value) {
                return \DecimalHelper::formatRound($value);
            },
            'totalRevenue' => array('DecimalHelper', 'formatRound'),
            'totalProfitPercent' => array('DecimalHelper', 'formatPercent'),
        );
    }





}
