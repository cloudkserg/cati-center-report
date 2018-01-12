<?php
namespace Report;

use Export\Style\CellStyle;
use Export\Style\Color;
use Export\Style\Merger;
use OperatorRecord;
use Report\Row\Block;
use Report\Row\EmptyRowModel;
use Report\Row\NumberRowModel;
use Report\Template\GenOperatorDaysTemplate;
use Report\Template\GenPaymentTemplate;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\Row\Context;
use Stat\Payment\Period\PeriodInterface;
use Operator;
use Stat\Payment\Period\PeriodFactory;

/**
 * GenPaymentReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class GenPaymentReport extends Report
{

    /**
     * @var \GenPaymentFilter
     */
    protected $_filter;


    /**
     * createFilter
     *
     * @return \GenPaymentFilter
     *
     */
    protected function createFilter()
    {
        return new \GenPaymentFilter();
    }



    protected function getPreModels()
    {
        $model = new EmptyRowModel($this->_row, $this->getProjectContext());
        $model->setDefaultStyle($this->getLabelStyle());
       return array($model);
    }

    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $models = array();
        $numberModel = new NumberRowModel($this->_row);
        $numberModel->setDefaultStyle($this->getNumberStyle());
        $models[] = $numberModel;

        $totalModel = new TotalRowModel($this->_row, $this->getTotalContext());
        $totalModel->setFormatters($this->getTotalFormatters());
        $totalModel->setDefaultStyle($this->getTotalStyle()); 

        foreach ($this->getOperators() as $operator) {
            $operatorModel = new TotalRowModel($this->_row, $this->getOperatorContext($operator));
            $operatorModel->setDefaultStyle($this->getOperatorStyle());
            $operatorModel->setFormatters($this->getTotalFormatters());
            $models[] = $operatorModel;

            foreach ($this->getPeriods($operator) as $period) {
                $model = new RowModel($this->_row, array($operator, $period));
                $model->setDefaultStyle($this->getPeriodStyle());
                $operatorModel->addModel($model);
                $totalModel->addModel($model);
                $models[] = $model;
            }
        }

        $models[] = $totalModel;

        return $models;
    }

    private function getNumberStyle()
    {
        return CellStyle::create(Color::YELLOW)
            ->setBorder(true);
    }
    
    /**
     * @return Context
     */
    private function getTotalContext()
    {
        return new Context(array(
            'fio' => 'Итого',
            'month' => '',
            'group' => '',
            'rate_group' => '',

            'project.plan_poll_speed' => '',
            'project.poll_speed' => '',
            'project.perform_plan' => '',
            'project.rate' => '',
        ));
    }
    
    /**
     * @return Context
     */
    private function getOperatorContext(\Operator $operator)
    {
        return new Context(array(
            'fio' => function () use ($operator) {
                return $operator->fullname;
            },
            'month' => '',
            'group' => $operator->getRelation('operatorGroup')->title,
            'rate_group' => '',

            'project.plan_poll_speed' => '',
            'project.poll_speed' => '',
            'project.perform_plan' => '',
            'project.bonus_desc' => '',
            'project.rate' => '',
        ));
    }

    /**
     * @return Context
     */
    private function getProjectContext()
    {
        return new Context(array(
            'fio' => 'Оператор',
            'project.pay_duration' => function (\Project $project) {
                return $project->id . ' ' . $project->title;
            }
        ));
    }
    
    private function getLabelStyle()
    {
        $style = CellStyle::create()
            ->setColor(Color::GRAY)
            ->setBorder(true);

        $style->addMerger(new Merger(['fio', 'month', 'group']));

        foreach ($this->getTemplate()->getProjects() as $project) {
            $columnIds = [
                Block::compoundColumnId(array('project' => $project->id), 'pay_duration'),
                Block::compoundColumnId(array('project' => $project->id), 'full_payment')
            ];
            $style->addMerger(new Merger($columnIds));
        }


        return $style;
    }


    private function getPeriodStyle()
    {
        return CellStyle::create()->setBorder(true);
    }

    private function getOperatorStyle()
    {
        return CellStyle::create()
            ->setColor(Color::GRAY)
            ->setBorder(true);
    }

    private function getTotalStyle()
    {
        return CellStyle::create()
            ->setColor(Color::GRAY)
            ->setBorder(true);
    }


    /**
     * @return \Operator[]
     */
    private function getOperators()
    {
        $builder = new OperatorRecordBuilder;
        return $builder->forModel(
            OperatorRecord::model()
                ->applySearch($this->_filter)
            )
            ->getOperators(
                Operator::model()
                    ->sortByOperatorGroup()
                    ->sort('surname')
            );
    }


    /**
     * @param \Operator $item
     * @return PeriodInterface[]
     */
    private function getPeriods(\Operator $item)
    {
        return  array_reverse(PeriodFactory::createSearcher()
            ->findPeriods(
                OperatorRecord::model()
                    ->applySearch($this->_filter)
                    ->forOperator($item->id),
                $this->_filter->getStartCarbon(),
                $this->_filter->getEndCarbon()
            ));
    }


    /**
     * getTitles
     *
     * @return string
     */
    protected function getTitles()
    {
        return  array(
        );
    }



    /**
     * @return GenPaymentTemplate|Template\RowTemplate
     */
    protected function getTemplate()
    {
        return new GenPaymentTemplate($this->_filter);
    }
    
    /**
     * getTotalFormatters
     *
     * @return array
     */
    private function getTotalFormatters()
    {
        $formatters = $this->getFormatters();
        unset($formatters['project.perform_plan']);
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
            'project.pay_duration' => array('DurationHelper', 'formatHours'),
            'project.plan_poll_speed' => array('DecimalHelper', 'formatRound'),
            'project.poll_speed' => array('DecimalHelper', 'formatRound'),
            'project.poll' => array('DecimalHelper', 'formatDecimal'),
            'project.perform_plan' => function ($value) {
                return \DecimalHelper::formatRound($value*100) . '%';
            },
            'project.rate' => function ($value) {
                return \DecimalHelper::formatRound($value);
            },
            'project.payment' => array('DecimalHelper', 'formatRound'),
            'project.avail_duration' => array('DurationHelper', 'formatHours'),
            'project.bonus' => array('DecimalHelper', 'formatRound'),
            'project.full_payment' => array('DecimalHelper', 'formatRound'),


            'pay_duration' => array('DurationHelper', 'formatHours'),
            'payment' => array('DecimalHelper', 'formatRound'),
            'bonus' => array('DecimalHelper', 'formatRound'),
            'full_payment' => array('DecimalHelper', 'formatRound'),
            'rate_perform_plan' => function ($value) {
                return $value . '%';
            },
            'rate_pay_duration' => array('DecimalHelper', 'formatDecimal')
        );
    }






}
