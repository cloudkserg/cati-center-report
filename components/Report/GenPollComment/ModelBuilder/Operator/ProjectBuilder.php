<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 07.06.16
 * Time: 12:48
 */

namespace Report\GenPollComment\ModelBuilder\Operator;


use Export\Cell\Cell;
use Export\Style\CellStyle;
use Export\Style\Color;
use Export\Style\Merger;
use Report\Row\Context;
use Report\Row\EmptyRowModel;
use Report\Row\RowInterface;
use Stat\Call\CallBuilder;
use Project, Call;

class ProjectBuilder
{

    private $_row;

    public function __construct(RowInterface $row)
    {
        $this->_row = $row;
    }


    public function createModel(\Project $project, $filter)
    {
        $model = new EmptyRowModel($this->_row, $this->getContext($project, $filter));
        $model->setDefaultStyle($this->getStyle());
        $model->setFormatters(array());
        return $model;
    }

    private function getContext(Project $project, $filter)
    {
        return new Context(array(
            'number' => function () use ($project)  {
                return 'Проект №' . $project->id . ' ' . $project->title;
            },
            'full_listen' => function () {
                return 'Средняя длина анкеты';
            },
            'listen_percent' => function () use ($project, $filter)  {
                return \DecimalHelper::formatDecimal($this->getMediumDuration($project, $filter));
            }
        ));
    }

    private function getStyle()
    {
        $style = CellStyle::create();
        $style->addMerger(new Merger(array('number', 'fio')));
        $style->addMerger(new Merger(array('full_listen', 'total_listen')));
        return $style;
    }


    /**
     * getMediumDuration
     *
     * @param Project $project
     * @param $filter
     * @return int
     */
    private function getMediumDuration(Project $project, $filter)
    {
        return CallBuilder::create()
            ->forModel(
                Call::model()
                    ->applySearch($filter)
                    ->forProject($project->id)
            )->avgDuration();
    }

}