<?php
namespace Report\GenProject;

use Export\Style\Color;
use Export\Style\CellStyle;
use Export\Cell\Cell;
use Iterator;
use Report\Row\Context;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\Row\ModelInterface;
use Report\Helper\ReportHelper; 

use Report\GenProject\Template\PaymentTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use OperatorRecord;
use Carbon\Carbon;
use Project;
use GenProjectFilter;
use Report\Report;

/**
 * PaymentReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class PaymentReport extends Report
{
    const RECOMMEND_PERFORM_PLAN = 75;


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
        return array("Отчет от {$startDate} по {$endDate}");

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

        $projects = $this->getProjects();
        $totalModel = new TotalRowModel($this->_row, new Context([
            'title' => 'Итого',
            'paymentType' => '',
            'pollCost' => '',
            'hourCost' => '',
            'pollRate' => '',
            'date' => '',
            'totalProfit' => function (array $results) {
                $value = array_sum($results);
                return $this->colorValue($value);
            },
            'totalProfitPercent' => ''
        ]));
        $totalModel->setDefaultStyle($this->getRowStyle());
        $totalModel->setFormatters($this->getTotalFormatters());

        foreach ($projects as $project) {
            $this->addProjectModels($project, $models, $totalModel);
        }
        $models[] = $totalModel;
        return $models;
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


    /**
     * @param Project $project
     * @param array $models
     * @param TotalRowModel $totalModel
     */
    private function addProjectModels(Project $project, array &$models, TotalRowModel &$totalModel)
    {
        $projectModel = new TotalRowModel($this->_row, new Context([
            'title' => $project->id . '-' . $project->title,
            'paymentType' => \PaymentType::model()->getTitle($project->payment_type),
            'pollCost' => $project->poll_cost,
            'hourCost' => $project->hour_cost,
            'pollRevenue' => $project->getRelation('lastProjectPeriod')->revenue,
            'pollRate' => '',
            'date' => '',
            
            'pollSpeed' => array('App\Helper\ArrayHelper', 'medium'),

            'mediumPerformPlan' => function (array $results, ModelInterface $model) use ($project) {
                return $this->getMediumPerformPlan($project, $model);
            },
            'recommendPollRate' => function (array $results, ModelInterface $model) use ($project) {
                return $this->getRecommendPollRate($project, $model);
            },


            'totalProfit' => function (array $results) {
                $value = array_sum($results);
                return $this->colorValue($value, Color::GRAY);
            },
            'totalProfitPercent' => array('App\Helper\ArrayHelper', 'mediumPositive')
        ]));
        $projectModel->setDefaultStyle($this->getProjectStyle());
        $projectModel->setFormatters($this->getProjectFormatters());
        $models[] = $projectModel;

        $dates = $this->getDates($project);
        foreach ($dates as $date) {
            $model = new RowModel($this->_row, array($date, $project));
            $model->setDefaultStyle($this->getRowStyle());
            $projectModel->addModel($model);
            $totalModel->addModel($model);

            $models[] = $model;
        }
    }


    /**
     * 
     *
     * @param Project $project
     * @param ModelInterface $model
     * @return int
     */
    public function getMediumPerformPlan(Project $project, ModelInterface $model)
    {
        $pollSpeed = ReportHelper::getValue($model, 'pollSpeed');
        return $pollSpeed / $project->poll_rate;
    }

    /**
     * getRecommendPollRate
     *
     * @param Project $project
     * @param ModelInterface $model
     * @return int
     */
    public function getRecommendPollRate(Project $project, ModelInterface $model)
    {
        $planPerform = ReportHelper::getValue($model, 'mediumPerformPlan') * 100;
        if ($planPerform >= self::RECOMMEND_PERFORM_PLAN) {
            return 0;
        }
        return (self::RECOMMEND_PERFORM_PLAN / 100) * $project->poll_rate;
    }

    protected function getHides()
    {
        return array(
            'pollSpeed'
        );
    }

    private function getRowStyle()
    {
        return CellStyle::create()->setBorder(true);
    }

    private function getProjectStyle()
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
                'totalProfitPercent' => null,
                'pollRevenue' => array('DecimalHelper', 'formatDecimal'),
                'pollCost' => array('DecimalHelper', 'formatDecimal'),
                'hourCost' => array('DecimalHelper', 'formatDecimal')
           )
        );
    
    }


    /**
     * getProjectFormatters
     *
     * @return array
     */
    private function getProjectFormatters()
    {
        return array_merge(
            $this->getFormatters(),
            array(
                'pollRate' => null,
                'pollRevenue' => array('DecimalHelper', 'formatRound'),
                'pollCost' => array('DecimalHelper', 'formatDecimal'),
                'hourCost' => array('DecimalHelper', 'formatDecimal'),
                'mediumPerformPlan' => array('DecimalHelper', 'formatPercent'),
                'recommendPollRate' => array('DecimalHelper', 'formatRound'),
           )
        );
    
    }

    /**
     * @return \Project[]
     */
    private function getProjects()
    {
        return OperatorRecordBuilder::create()
            ->forModel(
                OperatorRecord::model()
                ->applySearch($this->_filter)
            )->getProjects();
    }

    /**
     * getDates
     *
     * @return Carbon[]
     */
    private function getDates(Project $project)
    {
        $builder = new OperatorRecordBuilder();
        $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
                ->forProject($project->id)

        );
        return $builder->addOrder('date ASC')->getDates();
    }

    protected function getTemplate()
    {
        return new PaymentTemplate($this->_filter);
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
            'poll' => array('DecimalHelper', 'formatDecimal'),
            'pollRate' => array('DecimalHelper', 'formatDecimal'),
            'totalProfit' => function ($value) {
                return \DecimalHelper::formatRound($value);
            },
            'totalRevenue' => array('DecimalHelper', 'formatRound'),
            'totalProfitPercent' => array('DecimalHelper', 'formatPercent'),
        );
    }



}
