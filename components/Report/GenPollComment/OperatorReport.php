<?php
namespace Report\GenPollComment;

use Export\Schema\Schema;
use Report\GenPollComment\ModelBuilder\Operator\OperatorBuilder;
use Report\GenPollComment\Template\OperatorTemplate;
use Report\Row\EmptyRowModel;
use Report\Report;
use Project;
use Stat\OperatorRecord\OperatorRecordBuilder;

use OperatorRecord;


/**
 * OperatorReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class OperatorReport extends Report
{

    /**
     * @var \GenPollCommentFilter
     */
    protected $_filter;

    private $_count;


    /**
     * createFilter
     *
     * @return \GenPollCommentFilter
     *
     */
    protected function createFilter()
    {
        return new \GenPollCommentFilter();
    }

    public function generateHeaders()
    {
        $this->_export->setSchema(new Schema($this->_exportColumns));
    }

    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $builder = new OperatorBuilder($this->_row);
        $models = array();

        foreach ($this->getProjects() as $project) {
            $models = array_merge($models, array(
                $builder->createProjectModel($project, $this->_filter),
                $builder->createGroupHeaderModel(),
                $builder->createHeaderModel()
            ));

            $totalModel = $builder->createTotalModel();
            foreach ($this->getOperators($project) as $key => $operator) {
                $operatorModel = $builder->createOperatorModel($project, $operator, $key);
                $models[] = $operatorModel;

                $totalModel->addModel($operatorModel);
            }
            $models = array_merge($models, array(
                $totalModel,
                $builder->createEmptyModel(),
                $builder->createEmptyModel()
            ));
        }
        return $models;
    }



    /**
     * getProject s
     *
     * @return Project[]
     */
    private function getProjects()
    {
        return OperatorRecordBuilder::create()
            ->forModel(
                OperatorRecord::model()
                ->applySearch($this->_filter)
            )->getProjects();
    }

    private function getOperators(Project $project)
    {
        return OperatorRecordBuilder::create()
            ->forModel(
                OperatorRecord::model()
                ->applySearch($this->_filter)
                ->forProject($project->id)
            )->getOperators();
    }


    /**
     * getTitles
     *
     * @return string
     */
    protected function getTitles()
    {
        return  array(
            'Отчет контроль'
        );
    }



    /**
     * @return OperatorTemplate
     */
    protected function getTemplate()
    {
        return new OperatorTemplate($this->_filter);
    }
    
    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
            'listen_percent' => array('DecimalHelper', 'formatPercent'),
            'defect_percent' => array('DecimalHelper', 'formatPercent')
        );
    }






}
