<?php
namespace Report\Template;

use Export\Style\CellStyle;
use Operator, Call, Poll, Project;
use Report\Row\Block;
use Report\Row\Row;
use Stat\Helper\PayDurationHelper;
use Stat\Helper\PollHelper;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Stat\PayDuration\PayDurationBuilder;
use Stat\Payment\Bonus\BonusPayment;
use Stat\Payment\Bonus\BonusSearcher;
use Stat\Payment\Operator\OperatorPeriodStrategy;
use Stat\Payment\PaymentSearcher;
use OperatorRecord, OperatorPeriod;
use Stat\Searcher\CallSearcher;
use Stat\Searcher\OperatorPeriodSearcher;
use Stat\Searcher\PollSearcher;
use Stat\Searcher\ProjectPeriodSearcher;
use Stat\Payment\Period\PeriodInterface;
use Stat\Payment\Bonus\LastPoll;
use Stat\Payment\Bonus\TopWork;
use Stat\Payment\Operator\Rate\RateType;
use Stat\Payment\Operator\Rate\RateStrategyFactory;

/**
 * GenPaymentTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class GenPaymentTemplate extends RowTemplate
{

    /**
     * @var \GenPaymentFilter
     */
    protected $_filter;


    protected function getDefaultLabelStyle()
    {
        $style = new CellStyle(null, null, null, true);
        $style->setFreeze(array('fio', 'month', 'group', 'rate_group'));
        return $style;
    }


    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('fio', 'Фио', 'getFio'));
        $row->addColumn($this->createColumn('month', 'Месяц', 'getMonth'));
        $row->addColumn($this->createColumn('group', 'Группа', 'getEmpty'));
        $row->addColumn($this->createColumn('rate_group', 'Группа для расчетной ставки', 'getGroupForRate'));
        $row->addColumn($this->createColumn('rate', 'Ставка', 'getRateTitle'));


        $block = new Block('project', array($this, 'getProjects'), array('Report\Helper\ItemsHelper', 'getId'));

        $block->addColumn($this->createColumn('div', '', 'getEmpty'));
        $block->addColumn($this->createColumn('pay_duration', 'Часы к оплате, час', 'getPayDuration'));
        $block->addColumn($this->createColumn('poll', 'Кол-во анкет', 'getPoll'));
        $block->addColumn($this->createColumn('plan_poll_speed', 'План, анкет в час', 'getPlanPollSpeed'));
        $block->addColumn($this->createColumn('poll_speed', 'Факт, анкет в час', 'getPollSpeed'));
        $block->addColumn($this->createColumn('perform_plan', 'Выполнение плана, %', 'getPerformPlan'));
        $block->addColumn($this->createColumn('rate', 'Расчетная ставка, руб.', 'getRate'));
        $block->addColumn($this->createColumn('payment', 'Оплата, руб.', 'getPayment'));
        $block->addColumn($this->createColumn('bonus', 'Бонусы, руб.', 'getBonus'));
        $block->addColumn($this->createColumn('bonus_desc', 'расшифровка бонусов', 'getBonusDesc'));
        $block->addColumn($this->createColumn('full_payment', 'Итого сумма, руб.', 'getFullPayment'));
        $row->addBlock($block);


        $row->addColumn($this->createTotalColumn($block, 'pay_duration', 'Итого часы к оплате, час'));
        if (\Yii::app()->params['rate_strategy_type'] == RateType::TOTAL) {
            $row->addColumn($this->createColumn('rate_perform_plan', 'Итого Выполнение плана, %', 'getRatePerformPlan'));
            $row->addColumn($this->createColumn('rate_pay_duration', 'Итого часов к оплате в месяце, час', 'getRatePayDuration'));
        }
        $row->addColumn($this->createTotalColumn($block, 'payment', 'Итого сумма без бонусов, руб.'));
        $row->addColumn($this->createTotalColumn($block, 'bonus', 'Итого бонусы, руб.'));
        $row->addColumn($this->createTotalColumn($block, 'full_payment', 'Итого сумма, руб.'));

        return $row;

    }





    public function getFio(Operator $operator)
    {
        return $operator->fullname;
    }

    public function getMonth(Operator $operator, PeriodInterface $period)
    {
        return \Yii::app()->dateFormatter->format(
            'LLLL', $period->getStartDate()->timestamp
        );
    }


    public function getGroup(Operator $operator)
    {
        return $operator->getRelation('operatorGroup')->title;
    }


    /**
     * @return \Project[]
     */
    public function getProjects()
    {
        return $this->_cache->cacheValue('projects', function () {
            return OperatorRecordBuilder::create()
                ->forModel(OperatorRecord::model()->applySearch($this->_filter))
                ->getProjects();
        });
    }

    private function getPayDurationFull(Operator $operator)
    {
        return PayDurationBuilder::create()
            ->forModel(
                \PayDuration::model()
                ->forOperator($operator->id)
            )
            ->sumDuration();
    }

    public function getPoll(Operator $operator, PeriodInterface $period, Project $project)
    {
        return PollHelper::getPollCount(new PollSearcher(function () use ($operator, $period, $project) {
            return $this->createPoll($operator, $period, $project);
        }));
    }


    public function getPayDuration(Operator $operator, PeriodInterface $period, Project $project)
    {
        $cacheKey = $this->createCacheKey('payDuration', $operator, $period, $project);
        return $this->_cache->cacheValue($cacheKey, function () use ($operator, $period, $project) {
            return PayDurationHelper::getPayDuration(
                $this->createOperatorPeriod($operator, $period, $project),
                $this->createCall($operator, $period, $project),
                $this->createPoll($operator, $period, $project),
                $project
            );
        });
    }
    
    public function getRate(Operator $operator, PeriodInterface $period, Project $project)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $period);
        return $strategy
            ->getProjectStrategy($project->id)
            ->getHourRate(
                $strategy->getOperatorGroupRate($project)
            );


    }
    
    public function getGroupForRate(Operator $operator, PeriodInterface $period)
    {
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getOperatorGroup()->title;
    }


    public function getRateTitle(Operator $operator, PeriodInterface $period)
    {
        $defaultProject = new Project;
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getOperatorGroupRate($defaultProject)
            ->title;
    }

    /**
     * getRatePerformPlan
     *
     * @param Operator $operator
     * @param PeriodInterface $period
     * @return void
     */
    public function getRatePerformPlan(Operator $operator, PeriodInterface $period)
    {
        return RateStrategyFactory::createTotalInstance($operator, $period)
            ->getPlanPercent();
    }


    public function getRatePayDuration(Operator $operator, PeriodInterface $period)
    {
        return RateStrategyFactory::createTotalInstance($operator, $period)
            ->getPayDurationHours(); 
    }

    public function getPlanPollSpeed(Operator $operator, PeriodInterface $period, Project $project)
    {
        $period = \ProjectPeriod::model()
            ->applySearch($this->_filter)
            ->forProject($project->id)
            ->forStartDate($period->getStartDate())
            ->forEndDate($period->getEndDate())
            ->sort()
            ->find();
        if (!isset($period)) {
            return 0;
        }
        return $period->poll_rate;
    }

    public function getPollSpeed(Operator $operator, PeriodInterface $period, Project $project)
    {
        return PollHelper::getPollSpeedBySearcher(
            new PollSearcher(function () use ($operator, $project, $period) {
                return $this->createPoll($operator, $period, $project);
            }),
            $this->getPayDuration($operator, $period, $project)
        );
    }

    public function getPerformPlan(Operator $operator, PeriodInterface $period, Project $project)
    {
        return $this->getOperatorPeriodStrategy($operator, $period)
            ->getProjectStrategy($project->id)
            ->getPerformPlan();
    }

    public function getPerformPlanFormula(Operator $operator, PeriodInterface $period, Project $project)
    {
        return 'formula = ' . $this->getOperatorPeriodStrategy($operator, $period)
            ->getProjectStrategy($project->id)
            ->getPerformPlan(false);
    }

    public function getPayment(Operator $operator, PeriodInterface $period, Project $project)
    {
        $cacheKey = $this->createCacheKey('payment', $operator, $period, $project);
        return $this->_cache->cacheValue($cacheKey, function () use ($operator, $period, $project) {

            $periodStrategy = $this->getOperatorPeriodStrategy($operator, $period);
            return $periodStrategy
                ->getProjectStrategy($project->id)
                ->getPayment(
                    $periodStrategy->getOperatorGroupRate($project)
                );
        });
    }


    /**
     * @param Operator $operator
     * @param PeriodInterface $period
     * @param Project $project
     * @return BonusPayment
     */
    private function getBonusPayment(Operator $operator, PeriodInterface $period, Project $project)
    {
        $cacheKey = $this->createCacheKey('bonusPayment', $operator, $period, $project);
        return $this->_cache->cacheValue($cacheKey, function () use ($operator, $project, $period) {
            $bonuses = BonusSearcher::createFull(
                $project, $operator,
                $period->getStartDate(),
                $period->getEndDate(),
                $this->getPayDurationFull($operator),
                $this->getPayDuration($operator, $period, $project),
                $this->getLastPoll($project),
                $this->getTopWork($project, $period)
            )->getBonuses();
            return new BonusPayment($bonuses);
        });
    }

    private function getTopWork(Project $project, PeriodInterface $period)
    {
        return $this->_cache->cacheValue(
            'getTopWork_' . $project->id . '_' . $period->getId(),
            function () use ($project, $period) {
                return new TopWork($project, $period);
            }
        );
    }

    private function getLastPoll(Project $project)
    {
        return $this->_cache->cacheValue(
            'getLastPoll_' . $project->id,
            function () use ($project) {
                return new LastPoll($project);
            }
        );
    }

    public function getBonus(Operator $operator, PeriodInterface $period, Project $project)
    {
        return $this->getBonusPayment($operator, $period, $project)->getBonusValue(
            $this->getPayment($operator, $period, $project)
        );

    }

    public function getBonusDesc(Operator $operator, PeriodInterface $period, Project $project)
    {
        $bonuses = $this->getBonusPayment($operator, $period, $project)->getBonuses();
        return array_reduce($bonuses, function ($output, \Bonus $bonus) {
            return $output . '| ' . $bonus->title;
        }, '');
    }

    public function getFullPayment(Operator $operator, PeriodInterface $period, Project $project)
    {
        return $this->getBonus($operator, $period, $project) + $this->getPayment($operator, $period, $project);
    }




    private function createCacheKey($name, Operator $operator, PeriodInterface $period, Project $project)
    {
        return $name . '_' . $operator->id . '_' . $project->id . '_' . $period->getId();
    }



    /**
     * @param Operator $operator
     * @param PeriodInterface $period
     * @return OperatorPeriodStrategy
     */
    protected function getOperatorPeriodStrategy(Operator $operator, PeriodInterface $period)
    {
        $key = 'operatorStrategy_' . $operator->id . '_' . $period->getId();
        return $this->_cache->cacheValue($key, function () use ($operator, $period) {
            return new OperatorPeriodStrategy(
                $operator,
                $this->getProjects(),
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
                return $this->createCall($operator, $period);
            }),
            new PollSearcher(function () use ($operator, $period) {
                return $this->createPoll($operator, $period);
            }),
            new ProjectPeriodSearcher(function () use ($operator, $period) {
                return $this->createProjectPeriod($period);
            }),
            new OperatorPeriodSearcher(function () use ($operator, $period) {
                return $this->createOperatorPeriod($operator, $period);
            })
        );
    }

    /**
     * createPoll
     *
     * @param Operator $operator
     * @param Project $project
     * @param PeriodInterface $period
     * @return Poll
     */
    private function createPoll(Operator $operator, PeriodInterface $period, Project $project = null)
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
    private function createCall(Operator $operator, PeriodInterface $period, Project $project = null)
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
    private function createOperatorPeriod(Operator $operator, PeriodInterface $period, Project $project = null)
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
     * @param PeriodInterface $period
     * @param Project $project
     * @return \ProjectPeriod
     */
    private function createProjectPeriod(PeriodInterface $period, Project $project = null)
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


