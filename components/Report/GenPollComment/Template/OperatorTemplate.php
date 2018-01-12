<?php
namespace Report\GenPollComment\Template;

use Report\Row\Row;
use Report\Template\RowTemplate;
use Project, Operator, Poll;
use Export\Style\CellStyle;
use Export\Cell\Cell;
use Export\Style\Color;
use Stat\Poll\PollBuilder;
use ListenStatus;

/**
 * OperatorTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class OperatorTemplate extends RowTemplate
{

    /**
     * @var \GenPollCommentFilter
     */
    protected $_filter;


    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('number', '№', 'getNumber'));
        $row->addColumn($this->createColumn('class', 'Класс', 'getEmpty'));
        $row->addColumn($this->createColumn('fio', 'Оператор', 'getFio'));
        $row->addColumn($this->createColumn('poll', 'Анкет', 'getPoll'));
        $row->addColumn($this->createColumn('full_listen', 'Полный', 'getFullListen'));
        $row->addColumn($this->createColumn('partial_listen', 'Часть', 'getPartialListen'));
        $row->addColumn($this->createColumn('total_listen', 'Итого', 'getTotalListen'));
        $row->addColumn($this->createColumn('listen_percent', '%', 'getTotalListenPercent'));
        $row->addColumn($this->createColumn('defect', 'Принято с браком', 'getDefect'));
        $row->addColumn($this->createColumn('reject', 'Не принято', 'getReject'));
        $row->addColumn($this->createColumn('total_defect', 'Итого с браком', 'getTotalDefect'));
        $row->addColumn($this->createColumn('defect_percent', '%', 'getColorDefectPercent'));
        $row->addColumn($this->createColumn('full_listen_plan', 'Полный', 'getEmpty'));
        $row->addColumn($this->createColumn('partial_listen_plan', 'Часть', 'getEmpty'));
        $row->addColumn($this->createColumn('time', 'Время', 'getEmpty'));

        return $row;

    }

    public function getNumber(Project $project, Operator $operator, $key)
    {
        return $key +1 ;
    }

    public function getFio(Project $project, Operator $operator)
    {
        return $operator->abbrFullname;
    }


    private function createPoll(Project $project, Operator $operator)
    {
        return Poll::model()
            ->forOperator($operator->id)
            ->forProject($project->id);
    }

    public function getPoll(Project $project, Operator $operator)
    {
        return $this->getCacheValue('poll', [$project, $operator], function () use ($project, $operator) {
            return PollBuilder::create()
                ->forModel(
                    $this->createPoll($project, $operator)
                    ->forStatus(Poll::STATUS_SUCCESS)
                )
                ->count();
        });
    }

    public function getFullListen(Project $project, Operator $operator)
    {
        return $this->getCacheValue('fullListen', [$project, $operator], function () use ($project, $operator) {
            return PollBuilder::create()
                ->forModel(
                $this->createPoll($project, $operator)
                    ->forListenStatus(ListenStatus::FULL)
                )
                ->count();
        });
    }

    public function getPartialListen(Project $project, Operator $operator)
    {
        return $this->getCacheValue('fullListen', [$project, $operator], function () use ($project, $operator) {
            return PollBuilder::create()
                ->forModel(
                $this->createPoll($project, $operator)
                ->forListenStatus(ListenStatus::PARTIAL)
                )
                ->count();
        });
    }

    public function getTotalListen(Project $project, Operator $operator)
    {
        return $this->getFullListen($project, $operator) +  $this->getPartialListen($project, $operator);
    }


    public function getTotalListenPercent(Project $project, Operator $operator)
    {
        $all = $this->getPoll($project, $operator);
        if ($all == 0) {
            return 0;
        }
        return $this->getTotalListen($project, $operator) / $all;
    }

    public function getDefect(Project $project, Operator $operator)
    {
        return $this->getCacheValue('fullListen', [$project, $operator], function () use ($project, $operator) {
            return PollBuilder::create()
                ->forModel(
                $this->createPoll($project, $operator)
                ->forStatus(\PollStatus::DEFECT)
                )
                ->count();
        });
    }

    public function getReject(Project $project, Operator $operator)
    {
        return $this->getCacheValue('fullListen', [$project, $operator], function () use ($project, $operator) {
            return PollBuilder::create()
                ->forModel(
                $this->createPoll($project, $operator)
                ->forStatus(\PollStatus::REJECT)
                )
                ->count();
        });
    }

    public function getTotalDefect(Project $project, Operator $operator)
    {
        return $this->getDefect($project, $operator) + $this->getReject($project, $operator);
    }

    public function getDefectPercent(Project $project, Operator $operator)
    {
        $listen = $this->getTotalListen($project, $operator);
        if ($listen == 0) {
            return 0;
        }
        return $this->getTotalDefect($project, $operator) / $listen ;
    }

    public function getColorDefectPercent(Project $project, Operator $operator)
    {
        return Cell::create($this->getDefectPercent($project, $operator))->setStyle(
            CellStyle::create()->setBorder(true)->setColor(Color::YELLOW)
        );
    }

        

}


