<?php
namespace Report\GenProject\Template;
use Call, Poll, Project;
use Report\Helper\ReportHelper;
use Report\Row\ModelInterface;
use Report\Row\Row;
use Stat\Call\CallBuilder;
use Stat\Helper\PollHelper;
use Stat\Poll\PollBuilder;
use Stat\Searcher\PollSearcher;
use Report\Template\RowTemplate;

/**
 * ProjectTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectTemplate extends RowTemplate
{


    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('id', '№ Проекта', 'getId'));
        $row->addColumn($this->createColumn('title', 'Проект', 'getTitle'));
        $row->addColumn($this->createColumn('duration', 'Голосовой трафик, мин.', 'getDuration'));
        $row->addColumn($this->createColumn('callPrice', 'Расходы на связь, руб.', 'getCallPrice'));
        $row->addColumn($this->createColumn('poll', 'Анкеты, шт.', 'getPoll'));
        $row->addColumn($this->createColumn('price', 'Лицензионный платеж, руб.', 'getPrice'));

        return $row;

    }


    public function getId(Project $project)
    {
        return $project->id;
    }

    public function getTitle(Project $project)
    {
        return $project->title;
    }

    public function getDuration(Project $row)
    {
        return CallBuilder::create()->forModel(
            $this->createCall($row)->forStatus(\CallStatus::FINISH)
        )->sumDuration();

    }

    public function getCallPrice(Project $row)
    {
        return CallBuilder::create()->forModel(
            $this->createCall($row)
            ->forStatus(\CallStatus::FINISH)
        )->sumPriceDuration();
    }

    /**
     * getPoll
     *
     * @param Project $row
     * @return int
     */
    public function getPoll(Project $row)
    {
        return PollHelper::getPollCount(new PollSearcher(function () use ($row) {
            return $this->createPoll($row);
        }));
    }

    /**
     * @param Project $row
     * @param ModelInterface $model
     * @return int
     */
    public function getPrice(Project $row, ModelInterface $model)
    {
        return \Yii::app()->user->company->price * (ReportHelper::getValue($model, 'duration') / 60);
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
            ->forProject($row->id);
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
            ->forProject($row->id);
    }




}


