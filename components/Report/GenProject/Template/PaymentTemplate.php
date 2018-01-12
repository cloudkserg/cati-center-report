<?php
namespace Report\GenProject\Template;
use Export\Cell\Cell;
use Export\Style\CellStyle;
use Export\Style\Color;
use Operator, Call, Poll, Project;
use Carbon\Carbon;
use Report\Row\Row;
use Stat\Cache\ArrayCache;
use Stat\Helper\PayDurationHelper;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Stat\Call\CallBuilder;
use Report\Row\ModelInterface;
use Stat\Payment\Operator\OperatorPeriodStrategy;
use Stat\Payment\Operator\OperatorStrategy;
use Stat\Payment\Operator\RateStrategy;
use Stat\Payment\PaymentSearcher;
use Stat\Payment\Period\Month\MonthPeriod;
use Stat\Payment\Period\PeriodFactory;
use Stat\Payment\Period\PeriodInterface;
use Stat\Poll\PollBuilder;
use OperatorRecord, OperatorPeriod;
use ProjectPeriod;
use Stat\Searcher\CallSearcher;
use Stat\Searcher\OperatorPeriodSearcher;
use Stat\Searcher\PollSearcher;
use Stat\Searcher\ProjectPeriodSearcher;
use Stat\Profit\RevenueStrategy;
use Report\Helper\ReportHelper;
use Stat\Helper\PollHelper; 
use Report\Template\RowTemplate;

/**
 * PaymentTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class PaymentTemplate extends RowTemplate
{




    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();

        $row->addColumn($this->createColumn('title', 'Название проекта', 'getEmpty'));
        $row->addColumn($this->createColumn('paymentType', 'Тип оплаты', 'getEmpty'));
        $row->addColumn($this->createColumn('pollCost', 
            'Расходы, стоимость успешной анкеты для оператора, руб.', 'getEmpty'));
        $row->addColumn($this->createColumn('pollRevenue', 
            'Доходы, стоимость успешной анкеты, руб', 'getEmpty'));
        $row->addColumn($this->createColumn('hourCost', 
            'Расходы, стоимость часа работы оператора, руб.', 'getEmpty'));
        $row->addColumn($this->createColumn('date', 'Дата', 'getDate'));
        $row->addColumn($this->createColumn('pollRate', 'Норма по плану, анкет в час', 'getPollRate'));
        $row->addColumn($this->createColumn('poll', 'Анкет,шт', 'getPoll'));
        $row->addColumn($this->createColumn('pollSpeed', 'Анкет в час', 'getPollSpeed'));
        $row->addColumn($this->createColumn('mediumPerformPlan', 'Средний процент выполнения плана, %', 'getEmpty'));
        $row->addColumn($this->createColumn('recommendPollRate', 'Рекомендуемая норма анкет в час', 'getEmpty'));
        $row->addColumn($this->createColumn('payDuration', 'Часов к оплате', 'getPayDuration'));
        $row->addColumn($this->createColumn('callPrice', 'Расходы на связь, руб.', 'getCallPrice'));
        $row->addColumn($this->createColumn('payment', 'Зарплата операторам', 'getPayment'));
        $row->addColumn($this->createColumn('companyPrice', 'Лицензионный платеж', 'getCompanyPrice'));
        $row->addColumn($this->createColumn('totalPrice', 'Итого расходы', 'getTotalPrice'));
        $row->addColumn($this->createColumn('totalRevenue', 'Итого доходы', 'getTotalRevenue'));
        $row->addColumn($this->createColumn('totalProfit', 'Итого прибыль', 'getTotalProfit'));
        $row->addColumn($this->createColumn('totalProfitPercent', 'Процент прибыли', 'getTotalProfitPercent'));

        return $row;

    }


    protected function getDefaultLabelStyle()
    {
        return CellStyle::create()->setBorder(true);
    }


    public function getEmpty(Carbon $date, Project $project)
    {
        return '';
    }

    public function getDate(Carbon $date, Project $project)
    {
        return $date->format('d.m.Y');
    }


    public function getPollRate(Carbon $date, Project $project)
    {
        $periods = $this->getProjectPeriods($project, $date);
        if (count($periods) > 1 ) {
            return 'Смешанная';
        }
        if (empty($periods)) {
            return 'Нет значений';
        }
        return $periods[0]->poll_rate;
    }


    public function getPollSpeed(Carbon $date, Project $project)
    {
        return PollHelper::getPollSpeedBySearcher(
            new PollSearcher(function () use ($date, $project) {
                return $this->createPoll($date, $project);
            }),
            $this->getPayDuration($date, $project)
        );
    }


    public function getPoll(Carbon $date, Project $project)
    {
        return PollHelper::getPollCount(new PollSearcher(function () use ($date, $project) {
            return $this->createPoll($date, $project);
        }));
    }
    
    public function getDuration(Carbon $date, Project $row)
    {
        return CallBuilder::create()->forModel(
            $this->createCall($date, $row)->forStatus(\CallStatus::FINISH)
        )->sumDuration();

    }


    public function getCallPrice(Carbon $date, Project $project)
    {
        $builder = CallBuilder::create()
            ->forModel(
                $this->createCall($date, $project)
                    ->forStatus(\CallStatus::FINISH)
            );
        return $builder->sumPriceDuration();

    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return int
     */
    public function getCompanyPrice(Carbon $date, Project $project)
    {
        return ($project->getCompany()->price / 60) * $this->getDuration($date, $project);
    }


    /**
     * @param Carbon $date
     * @param Project $project
     * @return int
     */
    public function getPayDuration(Carbon $date, Project $project)
    {
        return PayDurationHelper::getPayDuration(
            $this->createOperatorPeriod($date, $project),
            $this->createCall($date, $project),
            $this->createPoll($date, $project),
            $project
        );
    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return mixed
     */
    public function getPaymentFormula(Carbon $date, Project $project)
    {
       return array_reduce(
            $this->getOperators($date, $project),
            function ($sum, Operator $operator) use ($date, $project) {
                return $sum . '+' . $this->getPaymentForOperatorFormula($operator, $date, $project);
            },
            0
        );

    }


    /**
     * @param Carbon $date
     * @param Project $project
     * @return mixed
     */
    public function getPayment(Carbon $date, Project $project)
    {
       return array_reduce(
            $this->getOperators($date, $project),
            function ($sum, Operator $operator) use ($date, $project) {
                return $sum + $this->getPaymentForOperator($operator, $date, $project);
            },
            0
        );

    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return mixed
     */
    public function getTotalPrice(Carbon $date, Project $project)
    {
        return $this->getCallPrice($date, $project) +
            $this->getPayment($date, $project) +
            $this->getCompanyPrice($date, $project);
    }


    public function getTotalRevenue(Carbon $date, Project $project)
    {
        $strategy = new RevenueStrategy();
        return $strategy->getValue(
            new PollSearcher(function () use ($date, $project) {
                return $this->createPoll($date, $project);
            }),
            new ProjectPeriodSearcher(function () use ($date, $project) {
                return ProjectPeriod::model()->applySearch($this->_filter)
                    ->forProject($project->id)
                    ->forDate($date);
            })
        );
    }

    public function getTotalProfit(Carbon $date, Project $project, ModelInterface $model)
    {
        $value =
            ReportHelper::getValue($model, 'totalRevenue') -
            ReportHelper::getValue($model, 'totalPrice');
        return $this->colorValue($value);

    }

    /**
     * colorValue
     *
     * @param int $value
     * @return Cell
     */
    private function colorValue($value)
    {
        $cell = Cell::create($value);
        $cell->setStyle(CellStyle::create()->setBorder(true));
        if ($value <= 0) {
            $cell->getStyle()->setColorText(Color::RED);
        }
        return $cell;
    }

    public function getTotalProfitPercent(Carbon $date, Project $project, ModelInterface $model)
    {
        $revenue =  ReportHelper::getValue($model, 'totalRevenue');
        if (empty($revenue)) {
            return 0;
        }
        $profit = ReportHelper::getValue($model, 'totalProfit');
        if ($profit < 0) {
            return 0;
        }
        return ($profit / $revenue);
    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return Poll
     */
    private function createPoll(Carbon $date, Project $project)
    {
        return Poll::model()
            ->applySearch($this->_filter)
            ->forProject($project->id)
            ->forDate($date->timestamp);
    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return Call
     */
    private function createCall(Carbon $date, Project $project)
    {
        return Call::model()
            ->applySearch($this->_filter)
            ->forProject($project->id)
            ->forDate($date->timestamp);
    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return OperatorRecord
     */
    private function createOperatorRecord(Carbon $date, Project $project)
    {
        return OperatorRecord::model()
            ->applySearch($this->_filter)
            ->forProject($project->id)
            ->forDate($date);
    }

    /**
     * @param Carbon $date
     * @param Project $project
     * @return OperatorPeriod
     */
    private function createOperatorPeriod(Carbon $date, Project $project)
    {
        return OperatorPeriod::model()
            ->applySearch($this->_filter)
            ->forProject($project->id)
            ->forDate($date);
    }

    /**
     * @param Operator $operator
     * @param Project $project
     * @param Carbon $date
     * @return float|string
     * @throws \Exception
     */
    private function getPaymentForOperator(Operator $operator, Carbon $date, Project $project)
    {
        $periodStrategy = $this->getOperatorPeriodStrategy($operator, $date);
        return $periodStrategy->getProjectStrategy($project->id)
            ->getPayment($periodStrategy->getOperatorGroupRate($project));
    }


    /**
     * @param Operator $operator
     * @param Project $project
     * @param Carbon $date
     * @return float|string
     * @throws \Exception
     */
    private function getPaymentForOperatorFormula(Operator $operator, Carbon $date, Project $project)
    {
        $periodStrategy = $this->getOperatorPeriodStrategy($operator, $date);
        return $periodStrategy
            ->getProjectStrategy($project->id)
            ->getPayment($periodStrategy->getOperatorGroupRate($project), false);
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
                    $this->getProjects($date),
                    $period,
                    $this->getPaymentSearcher($date, $operator)
                );
            }
        );
    }



    /**
     * @param Carbon $date
     * @return PaymentSearcher
     */
    private function getPaymentSearcher(Carbon $date, Operator $operator)
    {
        return new PaymentSearcher(
            new CallSearcher(function () use ($date, $operator) {
                return Call::model()
                    ->applySearch($this->_filter)
                    ->forDate($date->timestamp)
                    ->forOperator($operator->id);
            }),
            new PollSearcher(function () use ($date, $operator) {
                return Poll::model()
                    ->applySearch($this->_filter)
                    ->forDate($date->timestamp)
                    ->forOperator($operator->id);
            }),
            new ProjectPeriodSearcher(function () use ($date) {
                return ProjectPeriod::model()->applySearch($this->_filter)
                    ->forDate($date);
            }),
            new OperatorPeriodSearcher(function () use ($date, $operator) {
                return OperatorPeriod::model()->applySearch($this->_filter)
                    ->forOperator($operator->id)
                    ->forDate($date);
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
                    OperatorRecord::model()
                    ->forDate($date)
                )->getProjects();
        });
    }



    /**
     * @param Carbon $date
     * @param Project $project
     * @return \Operator[]
     */
    private function getOperators(Carbon $date, Project $project)
    {
       return OperatorRecordBuilder::create()
            ->forModel(
                $this->createOperatorRecord($date, $project)
            )->getOperators();
    }

    /**
     * @param Project $row
     * @param Carbon $date
     * @return \ProjectPeriod[]
     */
    private function getProjectPeriods(Project $row, Carbon $date)
    {
        return \ProjectPeriod::model()
            ->applySearch($this->_filter)
            ->forProject($row->id)
            ->forDate($date)
            ->setAscSort()
            ->findAll();
    }


}


