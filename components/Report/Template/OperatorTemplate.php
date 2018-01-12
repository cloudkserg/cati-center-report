<?php
namespace Report\Template;
use Operator, Call, Poll, PollStatus;
use Carbon\Carbon;
use Report\Helper\ItemsHelper;
use Report\Helper\ReportHelper;
use Report\Row\Block;
use Report\Row\ModelInterface;
use Report\Row\Row;
use Stat\Cache\ArrayCache;
use Stat\Helper\PayDurationHelper;
use Stat\Helper\PollHelper;
use Stat\OperatorPeriod\PeriodBuilder;
use Stat\Call\CallBuilder;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Stat\Payment\Operator\OperatorPeriodStrategy;
use Stat\Payment\Operator\OperatorStrategy;
use Stat\Payment\Operator\RateStrategy;
use Stat\Payment\PaymentSearcher;
use Stat\Payment\Period\Month\MonthPeriod;
use Stat\Payment\Period\PeriodFactory;
use Stat\Payment\Period\PeriodInterface;
use Stat\Poll\PollBuilder;
use ProjectPeriod, OperatorStatus, OperatorPeriod, OperatorRecord;
use OperatorScheduleStatus;
use Stat\Searcher\CallSearcher;
use Stat\Searcher\OperatorPeriodSearcher;
use Stat\Searcher\PollSearcher;
use Project;
use Stat\Searcher\ProjectPeriodSearcher;

/**
 * OperatorData
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class OperatorTemplate extends RowTemplate
{

    const TEXT_FORMAT = 'd.m.Y';





    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();

        $row->addColumn($this->createColumn('fio', 'Фио оператора', 'getFio'));
        $row->addColumn($this->createColumn('login', 'Логин оператора', 'getLogin'));
        $row->addColumn($this->createColumn('group', 'Группа оператора', 'getGroup'));

        $block = new Block('date', array($this, 'getDates'), array('Report\Helper\ItemsHelper', 'getDateId'));
        $block->addColumn($this->createColumn('date', array($this, 'getDateLabel'), 'getDate'));
        $block->addColumn($this->createColumn('duration', 'Кол-во часов между входом и выходом из программы, час ', 'getDuration'));
        $block->addColumn($this->createColumn('activeDuration', 'Кол-во часов в статусе активен, час ', 'getActiveDuration'));
        $block->addColumn($this->createColumn('poll', 'Кол-во анкет, шт.', 'getPoll'));
        $block->addColumn($this->createColumn('call', 'Кол-во звонков, шт.', 'getCall'));
        $block->addColumn($this->createColumn('pollSpeed', 'Норма анкет в час, факт', 'getPollSpeed'));
        $block->addColumn($this->createColumn('payDuration', 'Кол-во часов к оплате, час', 'getPayDuration'));
        $block->addColumn($this->createColumn('performPlan', 'Выполнение нормы, %', 'getPerformPlan'));
        $block->addColumn($this->createColumn('rate', 'Расходы, стоимость часа работы оператора, руб.', 'getRate'));
        $block->addColumn($this->createColumn('availDuration', 'Время ожидания, час', 'getAvailDuration'));
        $block->addColumn($this->createColumn('rejectPoll', 'Не принятые анкеты, шт.', 'getRejectPoll'));
        $block->addColumn($this->createColumn('notActiveDuration', 'Перерыв, час', 'getNotActiveDuration'));

        $row->addColumn($this->createTotalColumn($block, 'duration', 'Итого  часов между входом и выходом из программы, час '));
        $row->addColumn($this->createTotalColumn($block, 'activeDuration', 'Итого  часов в статусе активен, час'));
        $row->addColumn($this->createTotalColumn($block, 'poll', 'Итого  анкет, шт.'));
        $row->addColumn($this->createTotalColumn($block, 'call', 'Итого  звонков, шт.'));
        $row->addColumn($this->createColumn('pollSpeed', 'Норма анкет в час, факт', 'getTotalPollSpeed'));
        $row->addColumn($this->createTotalColumn($block, 'payDuration', 'Итого Часов к оплате,час'));
        $row->addColumn($this->createTotalColumn($block, 'performPlan', 'Итого  Выполнение нормы,%'));
        $row->addColumn($this->createTotalColumn($block, 'availDuration', 'Итого  Время ожидания, час'));
        $row->addColumn($this->createTotalColumn($block, 'rejectPoll', 'Не принятые анкеты, шт.'));
        $row->addColumn($this->createTotalColumn($block, 'notActiveDuration', 'Перерыв, час'));

        $row->addBlock($block);

        return $row;

    }


    /**
     * getDates
     *
     * @return array or Carbon dates
     */
    public function getDates()
    {
        return ItemsHelper::getDates($this->_filter->startTimestamp, $this->_filter->endTimestamp);

    }



    public function getFio(Operator $operator)
    {
        return $operator->fullname;
    }

    public function getLogin(Operator $operator)
    {
        return $operator->login;
    }

    public function getGroup(Operator $row)
    {
        return $row->getRelation('operatorGroup')->title;
    }


    public function getDateLabel(Carbon $date)
    {
        return $date->format(self::TEXT_FORMAT);
    }

    public function getDate(Operator $operator, Carbon $date)
    {
        return $date->format(self::TEXT_FORMAT);
    }

    /**
     * getDuration
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getDuration(Operator $row, Carbon $date)
    {
        return  PeriodBuilder::create()
            ->forModel(
                $this->createOperatorPeriod($row)
                ->forDate($date)
            )
            ->sumDuration();
    }

    public function getActiveDuration(Operator $row, Carbon $date)
    {
        $sum = PeriodBuilder::create()
            ->forModel(
                $this->createOperatorPeriod($row)
                ->forDate($date)
                ->forStatus(OperatorStatus::WORK)
            )
            ->sumDuration();

        return $sum;
    }

    /**
     * getPayDuration
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getPayDuration(Operator $row, Carbon $date)
    {
        return PayDurationHelper::getPayDuration(
          $this->createOperatorPeriod($row)->forDate($date),
          $this->createCall($row)->forDate($date->timestamp),
          $this->createPoll($row)->forDate($date->timestamp),
          $this->getProject()
        );
    }

    public function getTotalPollSpeed(Operator $row, ModelInterface $model)
    {
        return PollHelper::getPollSpeed(
            ReportHelper::getValue($model, 'poll'),
            ReportHelper::getValue($model, 'payDuration')
        );
    }

    /**
     * getPollSpeed
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getPollSpeed(Operator $row, Carbon $date, ModelInterface $model)
    {
        return PollHelper::getPollSpeed(
            $this->getPoll($row, $date),
            $this->getPayDuration($row, $date)
        );
    }

    /**
     * getPoll
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getPoll(Operator $row, Carbon $date)
    {
        $pollSearcher = new PollSearcher(function () use ($row, $date) {
            return $this->createPoll($row)
                ->forDate($date->timestamp);
        });
        return PollHelper::getPollCount($pollSearcher);
    }

    /**
     * getCall
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getCall(Operator $row, Carbon $date)
    {
        return CallBuilder::create()
            ->forModel(
                $this->createCall($row)
                ->forDate($date->timestamp)
            )
            ->count();
    }





    /**
     * getAvailDuration
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getAvailDuration(Operator $row, Carbon $date)
    {

        return PayDurationHelper::getAvailDuration(
            $this->createOperatorPeriod($row)
            ->forDate($date)
        );
    }


    public function getNotActiveDuration(Operator $row, Carbon $date)
    {
        return PeriodBuilder::create()
            ->forModel(
                $this->createOperatorPeriod($row)
                    ->forDate($date)
                    ->forScheduleStatus(OperatorScheduleStatus::UNACTIVE)
                    ->forStatus(OperatorStatus::AVAIL)
            )->sumDuration();

    }

    /**
     * getRejectPoll
     *
     * @param Operator $row
     * @param Carbon $date
     * @return int
     */
    public function getRejectPoll(Operator $row, Carbon $date)
    {
        return PollBuilder::create()
            ->forModel(
                $this->createPoll($row)
                ->forDate($date->timestamp)
                ->forStatus(PollStatus::REJECT)
            )
            ->count();
    }

    /**
     * @param Operator $row
     * @param Carbon $date
     * @param ModelInterface $model
     * @return float
     */
    public function getPerformPlan(Operator $row, Carbon $date, ModelInterface $model)
    {
        return $this->getOperatorPeriodStrategy($row, $date)
            ->getPerformPlan();
    }


    /**
     * @param Operator $row
     * @param Carbon $date
     * @return mixed|null|void
     */
    public function getRate(Operator $row, Carbon $date)
    {
        $strategy = $this->getOperatorPeriodStrategy($row, $date);
        return $strategy->getProjectStrategy($this->getProject()->id)
            ->getHourRate(
                $strategy->getOperatorGroupRate($this->getProject())
            );
    }


    private function createPeriod(Carbon $date)
    {
        return PeriodFactory::createSearcher()->getPeriod($date->copy()->startOfDay(), $date->copy()->endOfDay());
    }





    /**
     * @param Operator $operator
     * @param PeriodInterface $period
     * @return OperatorPeriodStrategy
     */
    protected function getOperatorPeriodStrategy(Operator $operator, Carbon $date)
    {
        return $this->_cache->cacheValue('operatorStrategy' . $operator->id . '_' . $date->timestamp,
            function () use ($operator, $date) {
                $period = $this->createPeriod($date);
                return new OperatorPeriodStrategy(
                    $operator,
                    $this->getProjects(),
                    $period,
                    $this->getPaymentSearcher($operator, $date)
                );
            }
        );
    }


    /**
     * @param Operator $row
     * @param Carbon $date
     * @return PaymentSearcher
     */
    private function getPaymentSearcher(Operator $row, Carbon $date)
    {
        return new PaymentSearcher(
            new CallSearcher(function () use ($row, $date) {
                return $this->createCall($row)
                    ->forDate($date->timestamp);
            }),
            new PollSearcher(function () use ($row, $date) {
                return $this->createPoll($row)
                    ->forDate($date->timestamp);
            }),
            new ProjectPeriodSearcher(function () use ($date) {
                return ProjectPeriod::model()->applySearch($this->_filter)
                    ->forDate($date);
            }),
            new OperatorPeriodSearcher(function () use ($row, $date) {
                return $this->createOperatorPeriod($row)
                    ->forDate($date);
            })
        );
    }


    /**
     * @return Project[]
     */
    private function getProjects()
    {
        return $this->_cache->cacheValue('projects', function () {
            return OperatorRecordBuilder::create()
                ->forModel(
                    OperatorRecord::model()
                        ->applySearch($this->_filter)
                )->getProjects();
        });
    }



    /**
     * createPoll
     *
     * @param mixed $row
     * @return Poll
     */
    private function createPoll($row)
    {
        return Poll::model()
            ->applySearch($this->_filter)
            ->forOperator($row->id);
    }

    /**
     * createCall
     *
     * @param mixed $row
     * @return Call
     */
    private function createCall($row)
    {
        return Call::model()
            ->applySearch($this->_filter)
            ->forOperator($row->id);
    }


    /**
     * createOperatorPeriod
     *
     * @param Operator $row
     * @return OperatorPeriod
     */
    private function createOperatorPeriod(Operator $row)
    {
        return OperatorPeriod::model()
            ->applySearch($this->_filter)
            ->forOperator($row->id);
    }


}


