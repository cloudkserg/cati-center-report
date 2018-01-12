<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 06.06.16
 * Time: 15:44
 */

namespace Report\GenPollComment\ModelBuilder\Operator;

use Export\Style\CellStyle;
use Project;
use Report\Row\Context;
use Report\Row\RowInterface;
use Report\Row\RowModel;
use Report\Row\EmptyRowModel;
use Report\Row\TotalRowModel;

class OperatorBuilder
{
    /**
     * @var RowInterface
     */
    private $_row;

    /**
     * @param RowInterface $row
     */
    public function __construct(RowInterface $row)
    {
        $this->_row = $row;
    }


    /**
     * @param Project $project
     * @param \CModel $filter
     * @return EmptyRowModel
     */
    public function createProjectModel(Project $project, $filter)
    {
        $builder = new ProjectBuilder($this->_row);
        return $builder->createModel($project, $filter);

    }

    /**
     * @return EmptyRowModel
     */
    public function createGroupHeaderModel()
    {
        $builder = new GroupHeaderBuilder($this->_row);
        return $builder->createModel();
    }

    /**
     * @return EmptyRowModel
     */
    public function createHeaderModel()
    {
        $builder = new HeaderBuilder($this->_row);
        return $builder->createModel();

    }

    /**
     * @return TotalRowModel
     */
    public function createTotalModel()
    {
        $builder = new TotalBuilder($this->_row);
        return $builder->createModel();
    }

    /**
     * @param Project $project
     * @param \Operator $operator
     * @param $key
     * @return RowModel
     */
    public function createOperatorModel(Project $project, \Operator $operator, $key)
    {
        $model = new RowModel($this->_row, array($project, $operator, $key));
        $model->setDefaultStyle(CellStyle::create()->setBorder(true));
        return $model;
    }

    /**
     * @return EmptyRowModel
     */
    public function createEmptyModel()
    {
        $model = new EmptyRowModel($this->_row, new Context());
        $model->setFormatters(array());
        return $model;
    }

}