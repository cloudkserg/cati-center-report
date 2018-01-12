<?php
namespace Report\GenPollComment;

use Report\GenPollComment\Template\ControllerTemplate;
use Report\Row\RowModel;
use Report\Row\TotalRowModel;
use Report\Row\Context;
use PollHistoryComment;
use PageDataProvider;
use Report\Report;

/**
 * ControllerReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ControllerReport extends Report
{

    const PAGE_LIMIT = 1000;

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




    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $models = array();

        $totalModel = new TotalRowModel($this->_row, $this->getTotalContext());
        $totalModel->setFormatters($this->getTotalFormatters());

        $prevComment = null;
        foreach ($this->getComments() as $comment) {
            $model = new RowModel($this->_row, array($comment, $prevComment));
            $totalModel->addModel($model);
            $prevModel = $model;
            yield $model;

            $prevComment = $comment;
        }

        yield $totalModel;
    }

    /**
     * @return Context
     */
    private function getTotalContext()
    {
        return new Context(array(
            'login' => 'Итого',
            'phone' => '',
            'project' => '',

            'created' => '',
            'prev_status' => '',
            'status' => '',
            'comment' => '',

            'id' => function (array $results) {
                return count(array_unique($results)) . ' анкет';
            },
        ));
    }
    

    /**
     * getComments
     *
     * @return array
     */
    protected function getComments()
    {
        $dataProvider = new PageDataProvider($this->createComment(), self::PAGE_LIMIT);
        while ($dataProvider->loadNextPage()) {
            foreach ($dataProvider->rows as $row) {
                yield $row;
            }
        }
    }

    /**
     * @return \PollCommenHistory
     */
    private function createComment()
    {
        return PollHistoryComment::model()
            ->applySearch($this->_filter);
    } 

    /**
     * getCountModels
     *
     * @return int
     */
    protected function getCountModels()
    {
        if (!isset($this->_count)) {
            $this->_count = $this->createComment()->count();
        }
        return $this->_count;
    }



    /**
     * getTitles
     *
     * @return string
     */
    protected function getTitles()
    {
        return  array(
            'Отчет контроль',
            'Всего ' . $this->getCountModels() . ' комментария.'
        );
    }



    /**
     * @return GenPollCommentTemplate
     */
    protected function getTemplate()
    {
        return new ControllerTemplate($this->_filter);
    }
    
    /**
     * getTotalFormatters
     *
     * @return array
     */
    private function getTotalFormatters()
    {
        return array(
            'duration' => array('DurationHelper', 'formatMinutes')
        );
    }

    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
            'duration' => array('DurationHelper', 'formatMinutes'),
            'created' => array(\Yii::app()->datetimeHelper, 'formatWeb')
        );
    }






}
