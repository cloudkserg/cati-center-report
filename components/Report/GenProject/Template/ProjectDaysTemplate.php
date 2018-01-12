<?php
namespace Report\GenProject\Template;
use Report\Helper\ReportHelper;
use Report\Helper\ItemsHelper;
use Report\Row\Block;
use Call, Poll, Project;
use Carbon\Carbon;
use Report\Row\ModelInterface;
use Report\Row\Row;
use Stat\Call\CallBuilder;
use Stat\Helper\PollHelper;
use Stat\Poll\PollBuilder;
use Stat\Searcher\PollSearcher;
use Report\Template\RowTemplate;

/**
 * ProjectDaysTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectDaysTemplate extends RowTemplate
{

    const TEXT_FORMAT = 'd.m.Y';

    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('id', '№ Проекта', 'getId'));
        $row->addColumn($this->createColumn('title', 'Проект', 'getTitle'));

        $block = new Block('date', array($this, 'getDates'), array('Report\Helper\ItemsHelper', 'getDateId'));
        $block->addColumn($this->createColumn('title', 'Дата', 'getDate'));
        $block->addColumn($this->createColumn('duration', 'Голосовой трафик, мин.', 'getDuration'));
        $block->addColumn($this->createColumn('callPrice', 'Расходы на связь, руб.', 'getCallPrice'));
        $block->addColumn($this->createColumn('poll', 'Анкеты, шт.', 'getPoll'));
        $block->addColumn($this->createColumn('price', 'Лицензионный платеж, руб.', 'getPrice'));

        $row->addColumn($this->createTotalColumn($block, 'duration', 'Итого голосовой трафик, мин'));
        $row->addColumn($this->createTotalColumn($block, 'callPrice', 'Расходы на связь, руб.'));
        $row->addColumn($this->createTotalColumn($block, 'poll', 'Анкеты, шт.'));
        $row->addColumn($this->createTotalColumn($block, 'price', 'Лицензионный платеж, руб.'));
        $row->addBlock($block);

        return $row;

    }


    /**
     * @return array
     */
    public function getDates()
    {
        return ItemsHelper::getDates($this->_filter->startTimestamp, $this->_filter->endTimestamp);
    }


    /**
     * @param Project $row
     * @param Carbon $date
     * @return string
     */
    public function getDate(Project $row, Carbon $date)
    {
        return $date->format(self::TEXT_FORMAT);
    }

    /**
     * @param Project $project
     * @return string
     */
    public function getId(Project $project)
    {
        return $project->id;
    }

    /**
     * @param Project $project
     * @return string
     */
    public function getTitle(Project $project)
    {
        return $project->title;
    }

    /**
     * @param Project $row
     * @param Carbon $date
     * @return int
     */
    public function getDuration(Project $row, Carbon $date)
    {
        return CallBuilder::create()->forModel(
            $this->createCall($row)
                ->forStatus(\CallStatus::FINISH)
                ->forDate($date->timestamp)
        )->sumDuration();

    }

    /**
     * @param Project $row
     * @param Carbon $date
     * @return int
     */
    public function getCallPrice(Project $row, Carbon $date)
    {
        return CallBuilder::create()->forModel(
            $this->createCall($row)
                ->forDate($date->timestamp)
        )->sum('duration*price');
    }

    /**
     * getPoll
     *
     * @param Project $row
     * @param Carbon $date
     * @return int
     */
    public function getPoll(Project $row, Carbon $date)
    {
        return PollHelper::getPollCount(new PollSearcher(function () use ($row, $date) {
            return $this->createPoll($row)
                ->forDate($date->timestamp);
        }));
    }

    /**
     * @param Project $row
     * @param Carbon $date
     * @param ModelInterface $model
     * @return mixed
     */
    public function getPrice(Project $row, Carbon $date, ModelInterface $model)
    {
        return \Yii::app()->user->company->price * ($this->getDuration($row, $date) / 60);
    }


    /**
     * createPoll
     *
     * @param Project $row
     * @return Poll
     */
    private function createPoll(Project $row)
    {
        return Poll::model()
            ->applySearch($this->_filter)
            ->forProject($row->id);
    }



    /**
     * createCall
     *
     * @param Project $row
     * @return Call
     */
    private function createCall(Project $row)
    {
        return Call::model()
            ->applySearch($this->_filter)
            ->forProject($row->id);
    }




}


