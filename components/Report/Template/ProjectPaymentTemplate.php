<?php
namespace Report\Template;
use Operator, Call, Poll;
use Project;
use ProjectPeriod;
use Carbon\Carbon;
use Report\Row\Row;
use Stat\Cache\ArrayCache;
use Stat\Helper\PayDurationHelper;
use Stat\Helper\PollHelper;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Stat\Call\CallBuilder;
use Stat\Payment\Operator\OperatorPeriodStrategy;
use Stat\Payment\Operator\OperatorStrategy;
use Stat\Payment\Operator\Rate\RateStrategyFactory;
use Stat\Payment\Operator\RateStrategy;
use Stat\Payment\PaymentSearcher;
use Stat\Payment\Period\Month\MonthPeriod;
use Stat\Payment\Period\PeriodFactory;
use Stat\Payment\Period\PeriodInterface;
use Stat\Poll\PollBuilder;
use OperatorRecord, OperatorPeriod;
use Stat\Searcher\CallSearcher;
use Stat\Searcher\OperatorPeriodSearcher;
use Stat\Searcher\PollSearcher;
use Stat\Searcher\ProjectPeriodSearcher;

/**
 * OperatorData
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectPaymentTemplate extends RowTemplate
{




    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();

        $row->addColumn($this->createColumn('date', 'Дата', 'getDate'));
        $row->addColumn($this->createColumn('poll', 'Анкет,шт', 'getPoll'));
        $row->addColumn($this->createColumn('payDuration', 'Часов к оплате', 'getPayDuration'));
        $row->addColumn($this->createColumn('callPrice', 'Расходы на связь, руб.', 'getCallPrice'));
        $row->addColumn($this->createColumn('payment', 'Зарплата операторам', 'getPayment'));
        $row->addColumn($this->createColumn('companyPrice', 'Лицензионный платеж', 'getCompanyPrice'));
        $row->addColumn($this->createColumn('totalPrice', 'Итого расходы', 'getTotalPrice'));

        return $row;

    }

    public function getDate(Carbon $date)
    {
        return $date->format('d.m.Y');
    }


    public function getPoll(Carbon $date)
    {
        return PollHelper::getPollCount(new PollSearcher(function () use ($date) {
            return $this->createPoll($date);
        }));
    }


    public function getCallPrice(Carbon $date)
    {
        return $this->_cache->cacheValue('callPrice_' . $date, function () use ($date) {
            return CallBuilder::create()
                ->forModel(
                    $this->createCall($date)->forStatus(\CallStatus::FINISH)
                )->sumPriceDuration();
        });
    }

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return mixed
     */
    public function getCompanyPrice(Carbon $date)
    {
        return $this->getProject()->getCompany()->price * ($this->getCallDuration($date) / 60);
    }
    
    private function getCallDuration(Carbon $date)
    {
        return $this->_cache->cacheValue('getCallDuration_' . $date->timestamp, function () use ($date) {
            return CallBuilder::create()
                ->forModel(
                    $this->createCall($date)->forStatus(\CallStatus::FINISH)
                )->sumDuration();
        });
    }


    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    public function getPayDuration(Carbon $date)
    {
        return PayDurationHelper::getPayDuration(
            $this->createOperatorPeriod($date),
            $this->createCall($date),
            $this->createPoll($date),
            $this->getProject()
        );
    }


    /**
     * @param Carbon|null $date
     * @return int
     */
    public function getPayment(Carbon $date)
    {
        return $this->_cache->cacheValue('payment_' . $date, function () use ($date) {
                return array_reduce(
                    $this->getOperators($date),
                    function ($sum, Operator $operator) use ($date) {
                        return $sum + $this->getPaymentForOperator($operator, $date);
                    },
                    0
                );
            });
    }

    /**
     * @param Carbon $date
     * @return mixed
     */
    public function getTotalPrice(Carbon $date)
    {
        return $this->getCallPrice($date) +
            $this->getPayment($date) +
            $this->getCompanyPrice($date);
    }

    /**
     * @param Carbon $date
     * @return Poll
     */
    private function createPoll(Carbon $date)
    {
        return Poll::model()
            ->applySearch($this->_filter)
            ->forDate($date->timestamp);
    }

    /**
     * @param Carbon $date
     * @return Call
     */
    private function createCall(Carbon $date)
    {
        return Call::model()
            ->applySearch($this->_filter)
            ->forDate($date->timestamp);
    }

    /**
     * @param Carbon $date
     * @return OperatorRecord
     */
    private function createOperatorRecord(Carbon $date)
    {
        return OperatorRecord::model()
            ->applySearch($this->_filter)
            ->forDate($date);
    }

    /**
     * @param Carbon $date
     * @return OperatorPeriod
     */
    private function createOperatorPeriod(Carbon $date)
    {
        return OperatorPeriod::model()
            ->applySearch($this->_filter)
            ->forDate($date);
    }


    /**
     * @param Operator $operator
     * @param Carbon $date
     * @return float|string
     */
    private function getPaymentForOperator(Operator $operator, Carbon $date)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $date);
        return $strategy
            ->getProjectStrategy($this->getProject()->id)
            ->getPayment(
                $this->getOperatorGroupRate($operator, $date, $this->getProject())
            );
    }

    private function createPeriod(Carbon $date)
    {
        return PeriodFactory::createSearcher()->getPeriod($date->copy()->startOfDay(), $date->copy()->endOfDay());
    }

    /**
     * @param Operator $operator
     * @param PeriodInterface $period
     * @return \Stat\Payment\Operator\Rate\RateStrategyInterface
     * @throws \Exception
     */
    private function getRateStrategy(\Operator $operator, PeriodInterface $period)
    {
        return RateStrategyFactory::createTotalInstance(
            $operator,
            $period
        );
    }

    /**
     * @param Operator $operator
     * @param Carbon $date
     * @param Project $project
     * @return \OperatorGroupRate
     * @throws \Exception
     */
    private function getOperatorGroupRate(Operator $operator, Carbon $date, Project $project)
    {
        $strategy = $this->getOperatorPeriodStrategy($operator, $date);
        $projectStrategy = $strategy->getProjectStrategy($project->id);
        return $strategy->getOperatorGroupRate($project);
    }


    /**
     * @param Operator $operator
     * @param Carbon $date
     * @return OperatorPeriodStrategy
     */
    protected function getOperatorPeriodStrategy(Operator $operator, Carbon $date)
    {
        return $this->_cache->cacheValue('operatorStrategy' . $operator->id . '_' . $date->timestamp,
            function () use ($operator, $date) {
                $period = $this->createPeriod($date);
                return new OperatorPeriodStrategy(
                    $operator,
                    $this->getProjects($date),
                    $period,
                    $this->getPaymentSearcher($date, $operator),
                    $this->getRateStrategy($operator, $period)
                );
            }
        );
    }


    /**
     * @param Carbon $date
     * @return PaymentSearcher
     */
    private function getPaymentSearcher(Carbon $date)
    {
        return new PaymentSearcher(
            new CallSearcher(function () use ($date) {
                return $this->createCall($date);
            }),
            new PollSearcher(function () use ($date) {
                return $this->createPoll($date);
            }),
            new ProjectPeriodSearcher(function () use ($date) {
                return ProjectPeriod::model()->applySearch($this->_filter)
                    ->forDate($date);
            }),
            new OperatorPeriodSearcher(function () use ($date) {
                return $this->createOperatorPeriod($date);
            })
        );
    }

    /**
     * @param Carbon $date
     * @return Project[]
     */
    private function getProjects(Carbon $date)
    {
        return $this->_cache->cacheValue('projects' . $date->timestamp, function () use ($date) {
            return OperatorRecordBuilder::create()
                ->forModel(
                    $this->createOperatorRecord($date)
                )->getProjects();
        });
    }

    /**
     * @param Carbon|null $date
     * @return \Operator[]
     */
    private function getOperators(Carbon $date)
    {
       return OperatorRecordBuilder::create()
            ->forModel(
                $this->createOperatorRecord($date)
            )->getOperators();
    }



}


