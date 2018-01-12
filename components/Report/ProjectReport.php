<?php
namespace Report;

use ExportComponent;
use Report\Row\RowModel;
use ProjectReportFilter;
use Report\Template\ProjectTemplate;
use Stat\Call\CallBuilder;
use Stat\Helper\PayDurationHelper;
use Stat\Helper\PollHelper;
use Stat\OperatorPeriod\PeriodBuilder;
use Stat\Poll\PollBuilder;
use Project;
use Stat\Searcher\PollSearcher;

/**
 * ProjectReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectReport extends Report
{


    /**
     * _project
     *
     * @var \Project
     */
    private $_project;


    /**
     * createFilter
     *
     * @return ProjectReportFilter
     *
     */
    protected function createFilter()
    {
        return new ProjectReportFilter();
    }

    /**
     * getProject
     *
     * @return \Project
     */
    protected function getProject()
    {
        if (!isset($this->_project)) {
            $this->_project = Project::model()->findByPk($this->_filter->project_id);
        }
        return $this->_project;
    }


    /**
     * @param array $models
     */
    private function createHeaders(array &$models)
    {
        $project = $this->getProject();

        $models[] = "Отчет по проекту от {$this->_filter->startDate} по {$this->_filter->endDate}";
        $models[] = "Название проекта: {$project->title}";
        $models[] = "Тип оплаты: {". \PaymentType::model()->getTitle($project->payment_type) . "}";
		$models[] = "Расходы, стоимость успешной анкеты для оператора, руб.: [" . $project->poll_cost . "]";
        $models[] = "Расходы, стоимость часа работы оператора, руб.: [" . $project->hour_cost . "]";
    }

    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $models = [];

        $this->createHeaders($models);

        $statuses = \PollStatus::model()->getValues();
        foreach ($statuses as $status) {
            $models['poll_' . $status] = new RowModel(
                $this->_row,
                array(
                    $this->getCountPollLabel($status),
                    function ($status) {
                        return \DecimalHelper::formatDecimal($this->getCountPoll($status));
                    },
                    [$status]
                )
            );
        }

        $clientStatuses = \ClientStatus::model()->getValues();
        foreach ($clientStatuses as $clientStatus) {
            $models['client_' . $clientStatus] = new RowModel(
                $this->_row,
                array(
                    $this->getCountClientLabel($clientStatus),
                    array($this, 'getCountClient'),
                    [$clientStatus]
                )
            );
        }

        $models[] = new RowModel(
            $this->_row,
            array(
                'Время заполнения анкет (ограничено/всего), мин.',
                function () {
                    return \DurationHelper::formatMinutes($this->getPollLimitFinishDuration()) . '/' .
                    \DurationHelper::formatMinutes($this->getPollFinishDuration());
                },

            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Время заполнения анкет + время обработки звонков без анкет(ограничено/всего), мин.',
                function () {
                    return \DurationHelper::formatMinutes($this->getLimitFinishDuration()) . '/' .
                    \DurationHelper::formatMinutes($this->getFinishDuration());
                }
            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Связь, вся(минуты)',
                function () {
                    return \DurationHelper::formatMinutes($this->getDuration());
                }
            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Связь, только успешные дозвоны, мин.',
                function () {
                    return \DurationHelper::formatMinutes($this->getSuccessDuration());
                }
            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Стоимость Связи, только успешные дозвоны, руб',
                function () {
                    return \DecimalHelper::formatDecimal($this->getCallPrice());
                }
            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Время ожидания (на операторов не было звоноков), час',
                function () {
                    return \DurationHelper::formatHours($this->getAvailDuration());
                }
            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Итого часов в статусе активен, час.',
                function () {
                    return \DurationHelper::formatHours($this->getActiveDuration());
                }
            ));
        $models[] = new RowModel(
            $this->_row,
            array(
                'Итого анкет в час в режиме активен',
                function () {
                    return \DecimalHelper::formatDecimal($this->getPollSpeed());
                }
            ));

        $models[] = new RowModel(
            $this->_row,
            array(
                'Итого часов к оплате',
                function () {
                    return \DurationHelper::formatHours($this->getPayDuration());
                }
            ));

        return $models;
    }


    /**
     * getTitles
     *
     * @return array
     */
    protected function getTitles()
    {

        $dateHelper = \Yii::app()->datetimeHelper;
        /**
         * var \TimezoneDateHelper $dateHelper
         */
        return array(
            "Отчет по проекту "
            . "{$this->getProject()->title} с "
            . "{$dateHelper->formatWeb($this->_filter->startTimestamp)} по "
            . "{$dateHelper->formatWeb($this->_filter->endTimestamp)}"
        );
    }


    protected function getTemplate()
    {
        return new ProjectTemplate();
    }


    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array();
    }

    /**
     * @return \OperatorPeriod
     */
    private function createOperatorPeriod()
    {
        return \OperatorPeriod::model()
            ->forProject($this->getProject()->id)
            ->applySearch($this->_filter);
    }

    /**
     * @return \Poll
     */
    private function createPoll()
    {
        return \Poll::model()
            ->forProject($this->getProject()->id)
            ->applySearch($this->_filter);
    }

    /**
     * @return \Client
     */
    private function createClient()
    {
        return \Client::model()
            ->forProject($this->getProject()->id)
            ->applySearch($this->_filter);
    }

    /**
     * @return \Call
     */
    private function createCall()
    {
        return \Call::model()
            ->forProject($this->getProject()->id)
            ->applySearch($this->_filter);
    }


    private function getCountPollLabel($status)
    {
        return 'Количество Анкет (' . \PollStatus::model()->getTitle($status) . ')';
    }

    public function getCountPoll($status)
    {
        return PollBuilder::create()->forModel($this->createPoll()->forStatus($status))->count();
    }


    private function getCountClientLabel($status)
    {
        return 'Количество телефонов (' . \ClientStatus::model()->getTitle($status) . ')';
    }

    public function getCountClient($status)
    {
        return $this->createClient()->forStatus($status)->count();
    }

    private function getPollLimitFinishDuration()
    {
        return PollBuilder::create()->forModel(
            $this->createPoll()
        )->sumLimitFinishDuration($this->getProject()->maxFinished);
    }

    private function getCallLimitFinishDuration()
    {
        return PayDurationHelper::getCallLimitFinishDuration(
          $this->createCall(), $this->createPoll(), $this->getProject()
        );
    }

    private function getPollFinishDuration()
    {
        return PollBuilder::create()->forModel(
            $this->createPoll()
        )->sumFinishDuration();
    }

    private function getCallFinishDuration()
    {
        return CallBuilder::create()->forModel(
            $this->createCall()->withoutPoll()
        )->sumFinishDuration();
    }

    private function getLimitFinishDuration()
    {
        $pollDuration = $this->getPollLimitFinishDuration();
        $callDuration = $this->getCallLimitFinishDuration();

        return $pollDuration + $callDuration;
    }

    private function getFinishDuration()
    {
        $pollDuration = $this->getPollFinishDuration();
        $callDuration = $this->getCallFinishDuration();

        return $pollDuration + $callDuration;
    }

    private function getDuration()
    {
        return CallBuilder::create()->forModel(
                $this->createCall()
            )->sumDuration();
    }

    private function getSuccessDuration()
    {
        return CallBuilder::create()->forModel(
            $this->createCall()->forStatus(\CallStatus::FINISH)
        )->sumDuration();

    }


    private function getAvailDuration()
    {
        return PayDurationHelper::getAvailDuration(
            $this->createOperatorPeriod()
        );
    }

    private function getActiveDuration()
    {
        return PeriodBuilder::create()->forModel(
            $this->createOperatorPeriod()
                ->forStatus(\OperatorStatus::WORK)
        )->sumDuration();
    }

    private function getPollSpeed()
    {
        $pollSearcher = new PollSearcher(function () {
            return $this->createPoll();
        });
        return PollHelper::getPollSpeed(
            PollHelper::getPollCount($pollSearcher),
            $this->getPayDuration()
        );
    }

    private function getPayDuration()
    {
        return PayDurationHelper::getPayDuration(
            $this->createOperatorPeriod(),
            $this->createCall(),
            $this->createPoll(),
            $this->getProject()
        );
    }

    private function getCallPrice()
    {
        return CallBuilder::create()->forModel(
            $this->createCall()->forStatus(\CallStatus::FINISH)
        )->sumPriceDuration();
    }


}
