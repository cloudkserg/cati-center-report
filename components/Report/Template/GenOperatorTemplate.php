<?php
namespace Report\Template;

use Export\Style\CellStyle;
use Export\Style\Color;
use Operator, Call, Poll, Project;
use Report\Row\Block;
use Report\Row\Row;
use Stat\Helper\PollHelper;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Stat\Payment\Operator\OperatorPeriodStrategy;
use Stat\Payment\PaymentSearcher;
use OperatorRecord, OperatorPeriod;
use Stat\Payment\Period\PeriodFactory;
use Stat\Payment\Period\PeriodInterface;
use Stat\Searcher\CallSearcher;
use Stat\Searcher\OperatorPeriodSearcher;
use Stat\Searcher\PollSearcher;
use Stat\Searcher\ProjectPeriodSearcher;
use GenOperatorFilter;

/**
 * GenOperatorTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class GenOperatorTemplate extends RowTemplate
{


    /**
     * @var GenOperatorFilter
     */
    protected $_filter;



    protected function getDefaultLabelStyle()
    {
        return new CellStyle(null, null, null, true);
    }


    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('fio', 'Фио', 'getEmpty'));
        $row->addColumn($this->createColumn('project', 'Проект', 'getProjectTitle'));
        $row->addColumn($this->createColumn('group', 'Группа', 'getEmpty'),
            CellStyle::create()->setWidth(13.57)->setBorder(true));
        $row->addColumn($this->createColumn('project_rate', 'Ставка по проекту', 'getProjectRate'));
        $row->addColumn($this->createColumn('project_status', 'Статус проекта', 'getProjectStatus',
            CellStyle::create()->setWidth(14.43)->setBorder(true)));
        $row->addColumn($this->createColumn('delim', '', 'getEmpty'));

        $block = new Block('month', array($this, 'getPeriods'), array('Report\Helper\ItemsHelper', 'getPeriodId'));

        $block->addColumn($this->createColumn('plan_poll_speed', 'План, анкет в час', 'getPlanPollSpeed',
            CellStyle::create(Color::YELLOW)->setBorder(true)));
        $block->addColumn($this->createColumn('poll_speed', 'Факт, анкет в час', 'getPollSpeed',
            CellStyle::create(Color::YELLOW)->setBorder(true)));
        $block->addColumn($this->createColumn('perform_plan', 'Выполнение плана, %', 'getPerformPlan',
            CellStyle::create(Color::YELLOW)->setBorder(true)));
        $block->addColumn($this->createColumn('relative_perform_plan', '', 'getRelativePerformPlan'));
        $block->addColumn($this->createColumn('relative_perform_plan_formula', '', 'getRelativePerformPlanFormula'));
        $block->addColumn($this->createColumn('rate', 'Расчетная ставка, руб.', 'getRate',
            CellStyle::create(Color::YELLOW)->setBorder(true)));
        $block->addColumn($this->createColumn('rate_title', 'Расчетная ставка, название', 'getRateTitle'));
        $block->addColumn($this->createColumn('rate_group', 'Группа для расчетной ставки', 'getGroupForRate'));
        $block->addColumn($this->createColumn('pay_duration', 'Время к оплате, час', 'getPayDuration',
            CellStyle::create(Color::YELLOW)->setBorder(true)));
        $block->addColumn($this->createColumn('relative_pay_duration', 'Доля', 'getRelativePayDuration'));
        $block->addColumn($this->createColumn('payment', 'Оплата, руб.', 'getPayment',
            CellStyle::create(Color::YELLOW)->setBorder(true)));

        $block->addColumn($this->createColumn('complete_payment', '.', 'getCompletePayment'));
        $block->addColumn($this->createColumn('delim', '', 'getEmpty'));
        $row->addBlock($block);


        $row->addColumn($this->createTotalColumn($block, 'payment', 'Итого к оплате, руб.',
            CellStyle::create(Color::YELLOW)->setBorder(true)));
        $row->addColumn($this->createTotalColumn($block, 'complete_payment', 'Итого по завершенным проектам, руб.',
            CellStyle::create(Color::YELLOW)->setBorder(true)));

        return $row;

    }




    public function getProjectTitle(Operator $operator, Project $project)
    {
        return $project->id .' ' . $project->title;
    }

    public function getProjectStatus(Operator $operator, Project $project)
    {
        return \ProjectStatus::model()->getTitle($project->status);
    }

    public function getProjectRate(Operator $operator, Project $project)
    {
        return $project->poll_rate;
    }

    /**
     * @return \Stat\Payment\Period\PeriodInterface[]
     */
    public function getPeriods()
    {
        return PeriodFactory::createSearcher()
            ->findPeriods(
                OperatorRecord::model()
                    ->applySearch($this->_filter),
                $this->_filter->getStartCarbon(),
                $this->_filter->getEndCarbon()
            );
    }



    public function getPlanPollSpeed(Operator $operator, Project $project, PeriodInterface $period)
    {
        $period = $this->createProjectPeriod($project, $period)
            ->sort()
            ->find();
        if (!isset($period)) {
            return 0;
        }
        return $period->poll_rate;
    }

    public function getPollSpeed(Operator $operator, Project $project, PeriodInterface $period)
    {
        return PollHelper::getPollSpeedBySearcher(
            new PollSearcher(function () use ($operator, $project, $period) {
                return $this->createPoll($operator, $project, $period);
            }),
            $this->getPayDuration($operator, $project, $period)
        );
    }

    public function getPerformPlan(Operator $operator, Project $project, PeriodInterface $period)
    {
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getProjectStrategy($project->id)
            ->getPerformPlan();
    }

    public function getRelativePerformPlan(Operator $operator, \Project $project, PeriodInterface $period)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $period);
        $value = $strategy
            ->getProjectStrategy($project->id)
            ->getRelativePerformPlan($strategy->getPayDuration());

        return $value;
    }

    public function getRelativePerformPlanFormula(Operator $operator, \Project $project, PeriodInterface $period)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $period);
        return $strategy
            ->getProjectStrategy($project->id)
            ->getRelativePerformPlan($strategy->getPayDuration(), false);
    }

    public function getRate(Operator $operator, Project $project, PeriodInterface $period)
    {
        $periodStrategy = $this->getOperatorPeriodStrategy($operator, $period);
        return $periodStrategy
            ->getProjectStrategy($project->id)
            ->getHourRate($periodStrategy->getOperatorGroupRate($project));
    }

    public function getPayDuration(Operator $operator, Project $project, PeriodInterface $period)
    {
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getProjectStrategy($project->id)
            ->getPayDuration();
    }

    public function getRateTitle(Operator $operator, Project $project, PeriodInterface $period)
    {
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getOperatorGroupRate($project)->title;
    }
    
    public function getGroupForRate(Operator $operator, Project $project, PeriodInterface $period)
    {
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getOperatorGroupRate($project)->getRelation('operatorGroup')->title;
    }

    public function getRelativePayDuration(Operator $operator, Project $project, PeriodInterface $period)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $period);
        return $strategy
            ->getProjectStrategy($project->id)
            ->getRelativePayDuration($strategy->getPayDuration());
    }

    public function getPayment(Operator $operator, Project $project, PeriodInterface $period)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $period);
        return $strategy
            ->getProjectStrategy($project->id)
            ->getPayment($strategy->getOperatorGroupRate($project));
    }

    public function getCompletePayment(Operator $operator, Project $project, PeriodInterface $period)
    {
        if ($project->status == \ProjectStatus::WORK) {
            return '';
        }
        return $this->getPayment($operator, $project, $period);
    }





    /**
     * @param Operator $operator
     * @param PeriodInterface $period
     * @return OperatorPeriodStrategy
     */
    private function getOperatorPeriodStrategy(Operator $operator, PeriodInterface $period)
    {
        $key = 'operatorPeriodStrategy_' . $operator->id . '_' . $period->getId();
        return $this->_cache->cacheValue($key, function () use ($operator, $period) {
            return new OperatorPeriodStrategy(
                $operator,
                $this->getProjects($operator),
                $period,
                $this->getPaymentSearcher($operator, $period)
            );
        });
    }

    /**
     * @param Operator $operator
     * @param PeriodInterface $period
     * @return PaymentSearcher
     */
    private function getPaymentSearcher(Operator $operator, PeriodInterface $period)
    {
        return new PaymentSearcher(
            new CallSearcher(function () use ($operator, $period) {
                return $this->createCall($operator, null, $period);
            }),
            new PollSearcher(function () use ($operator, $period) {
                return $this->createPoll($operator, null, $period);
            }),
            new ProjectPeriodSearcher(function () use ($operator, $period) {
                return $this->createProjectPeriod(null, $period);
            }),
            new OperatorPeriodSearcher(function () use ($operator, $period) {
                return $this->createOperatorPeriod($operator, null, $period);
            })
        );
    }

    /**
     * @param Operator $operator
     * @return Project[]
     */
    private function getProjects(Operator $operator)
    {
        return $this->_cache->cacheValue('projects_' . $operator->id, function () use ($operator) {
            return OperatorRecordBuilder::create()
                ->forModel(
                    OperatorRecord::model()
                        ->applySearch($this->_filter)
                        ->forOperator($operator->id)
                )->getProjects();
        });
    }

    /**
     * createPoll
     *
     * @param Operator $operator
     * @param Project $project
     * @param PeriodInterface $period
     * @return Poll
     */
    private function createPoll(Operator $operator, Project $project = null, PeriodInterface $period)
    {
        $item =  Poll::model()
            ->applySearch($this->_filter)
            ->forOperator($operator->id)
            ->forStart($period->getStartDate()->timestamp)
            ->forEnd($period->getEndDate()->timestamp);
        if (!isset($project)) {
            return $item;
        }
        return $item->forProject($project->id);
    }


    /**
     * createCall
     *
     * @param Operator $operator
     * @param Project $project
     * @param PeriodInterface $period
     * @return Call
     */
    private function createCall(Operator $operator, Project $project = null, PeriodInterface $period)
    {
        $item =  Call::model()
            ->applySearch($this->_filter)
            ->forOperator($operator->id)
            ->forStart($period->getStartDate()->timestamp)
            ->forEnd($period->getEndDate()->timestamp);
        if (!isset($project)) {
            return $item;
        }
        return $item->forProject($project->id);
    }




    /**
     * createOperatorPeriod
     *
     * @param Operator $operator
     * @param Project $project
     * @param PeriodInterface $period
     * @return OperatorPeriod
     */
    private function createOperatorPeriod(Operator $operator, Project $project = null, PeriodInterface $period)
    {
        $item =  OperatorPeriod::model()
            ->applySearch($this->_filter)
            ->forOperator($operator->id)
            ->forStartDate($period->getStartDate())
            ->forEndDate($period->getEndDate());
        if (!isset($project)) {
            return $item;
        }
        return $item->forProject($project->id);
    }

    /**
     * createProjectPeriod
     *
     * @param Project $project
     * @param PeriodInterface $period
     * @return \ProjectPeriod
     */
    private function createProjectPeriod(Project $project = null, PeriodInterface $period)
    {
        $item =  \ProjectPeriod::model()
            ->applySearch($this->_filter)
            ->forStartDate($period->getStartDate())
            ->forEndDate($period->getEndDate());
        if (!isset($project)) {
            return $item;
        }
        return $item->forProject($project->id);
    }


}


