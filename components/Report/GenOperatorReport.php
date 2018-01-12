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
use Report\Template\GenOperatorDaysTemplate;
use Report\Template\GenOperatorTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Carbon\Carbon;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\Row\Context;
use Stat\PayDuration\PayDurationBuilder;
use Operator;
use Stat\Payment\Period\PeriodInterface;

/**
 * OperatorReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class GenOperatorReport extends Report
{

    /**
     * @var \GenOperatorFilter
     */
    protected $_filter;


    /**
     * createFilter
     *
     * @return \GenOperatorFilter
     *
     */
    protected function createFilter()
    {
        return new \GenOperatorFilter();
    }

    protected function getPreModels()
    {
       return array(
           new EmptyRowModel($this->_row, $this->getMonthContext())
       );
    }

    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $models = array();
        $totalModel = new TotalRowModel(
            $this->_row,
            $this->getTotalContext()
        );
        $totalModel->setFormatters($this->getOperatorFormatters());
        $totalModel->setDefaultStyle($this->getOperatorStyle());

        foreach ($this->getOperators() as $operator) {
            $operatorModel = new TotalRowModel(
                $this->_row,
                $this->getOperatorContext($operator)
            );
            $operatorModel->setFormatters($this->getOperatorFormatters());
            $operatorModel->setDefaultStyle($this->getOperatorStyle());
            $models[] = $operatorModel;


            foreach ($this->getProjects($operator) as $project) {
                $model = new RowModel($this->_row, array($operator, $project));
                $model->setDefaultStyle($this->getProjectStyle());
                $operatorModel->addModel($model);
                $totalModel->addModel($model);
                $models[] = $model;
            }
        }

        $models[] = $totalModel;

        return $models;
    }

    /**
     * @return Context
     */
    private function getMonthContext()
    {
        return new Context(array(
            'month.rate' => function (PeriodInterface $period) {
                return Cell::create(
                    \Yii::app()->dateFormatter->format(
                        'LLLL', $period->getStartDate()->timestamp
                    )
                )->setStyle(
                    CellStyle::create()->setColorText(Color::RED)
                );
            },
        ));
    }

    private function getProjectStyle()
    {
        return CellStyle::create()->setBorder(true);
    }

    private function getOperatorStyle()
    {
        return CellStyle::create(Color::GRAY)->setBorder(true);
    }

    /**
     * @param \Operator $operator
     * @return Context
     */
    private function getOperatorContext(\Operator $operator)
    {
        return new Context(array(
            'fio' => $operator->fullname,
            'group' => $operator->getRelation('operatorGroup')->title,
            'project' => '',
            'project_rate' => '',
            'project_status' => '',
            'delim' => '',

            'month.plan_poll_speed' => '',
            'month.poll_speed' => '',
            'month.perform_plan' => function(PeriodInterface $period, array $values, ModelInterface $model) {
                return ReportHelper::getValue($model, 'relative_perform_plan', array('month' => ItemsHelper::getPeriodId($period)));
            },
            'month.rate_title' => function (PeriodInterface $period, array $values, ModelInterface $model) {
                return empty($values) ? '' : $values[0];
            },
            'month.rate' => function (PeriodInterface $period, array $values, ModelInterface $model) {
                return ReportHelper::getValue($model, 'rate_title', array('month' => ItemsHelper::getPeriodId($period)));
            },
            'month.rate_group' => '',

            'month.delim' => ''
        ));
    }

    /**
     * @return Context
     */
    private function getTotalContext()
    {
        return new Context(array(
            'fio' => 'Итого',
            'group' => '',
            'project' => '',
            'project_rate' => '',
            'project_status' => '',
            'delim' => '',

            'month.plan_poll_speed' => '',
            'month.poll_speed' => '',
            'month.perform_plan' => '',
            'month.relative_perform_plan' => '',
            'month.rate_title' => '',
            'month.rate' => '',
            'month.rate_group' => '',

            'month.delim' => ''

        ));
    }



    /**
     * @return \Operator[]
     */
    private function getOperators()
    {
        $builder = new OperatorRecordBuilder;
        $operators = $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
            )->getOperators();
        return Operator::model()->sortOperatorsByGroup($operators);
    }



    /**
     * @param \Operator $item
     * @return \Project[]
     */
    private function getProjects(\Operator $item)
    {
        $builder = new OperatorRecordBuilder();
        return $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
                ->forOperator($item->id)
        )->getProjects();
    }


    /**
     * getTitles
     *
     * @return string
     */
    protected function getTitles()
    {
        return  array(
            array(
                'project' => 'Период отчета',
                'project_rate' => $this->_filter->startDate,
                'project_status' => $this->_filter->endDate
            ),
            array(
                'project' => Carbon::create()->format('d.m.Y')
            ),
            array()
        );
    }

    /**
     *
     * comppoundName of hide columns
     *
     * @return array
     */
    protected function getHides()
    {
        return array(
            'month.rate_title',
            'month.complete_payment'
        );
    }


    /**
     * @return GenOperatorTemplate|Template\RowTemplate
     */
    protected function getTemplate()
    {
        return new GenOperatorTemplate($this->_filter);
    }

    /**
     * getOperatorFormatters
     *
     * @return array
     */
    private function getOperatorFormatters()
    {
        $formatters = $this->getFormatters();
        unset($formatters['project_rate']);
        unset($formatters['month.plan_poll_speed']);
        unset($formatters['month.poll_speed']);
        unset($formatters['month.rate']);
        unset($formatters['month.relative_pay_duration']);
        return $formatters;
    }



    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
            'project_rate' => array('DecimalHelper', 'formatDecimal'),


            'month.plan_poll_speed' => array('DecimalHelper', 'formatDecimal'),
            'month.poll_speed' => array('DecimalHelper', 'formatDecimal'),
            'month.perform_plan' => function ($value) {
                return \DecimalHelper::formatPercent($value) . '%';
            },
            'month.relative_perform_plan' => function ($value) {
                return  \DecimalHelper::formatPercent($value) . '%';
            },
            'month.rate' => array('DecimalHelper', 'formatDecimal'),
            'month.pay_duration' => function ($value) {
                $value = \DurationHelper::formatHours($value);
                return $value;
            }, 
            'month.relative_pay_duration' => array('DecimalHelper', 'formatDecimal'),
            'month.payment' => array('DecimalHelper', 'formatDecimal'),

            'payment' => function ($value) {
                $value = \DecimalHelper::formatDecimal($value);
                return $value;
            },
            'complete_payment' => function ($value) {
                if ($value === '') {
                    return '';
                }
                return \DecimalHelper::formatDecimal($value);
            },
        );
    }




}
