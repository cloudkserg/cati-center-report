<?php
namespace Report\GenPollComment\Template;

use PollHistoryComment;
use Report\Row\Row;
use PollStatus;
use Report\Template\RowTemplate;

/**
 * ControllerTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ControllerTemplate extends RowTemplate
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
        $row->addColumn($this->createColumn('login', 'Логин', 'getLogin'));
        $row->addColumn($this->createColumn('phone', 'Тел. номер анкеты', 'getPhone'));
        $row->addColumn($this->createColumn('id', 'ID анкеты', 'getId'));
        $row->addColumn($this->createColumn('project', 'Проект', 'getProject'));
        $row->addColumn($this->createColumn('created', 'Время сохранения анкеты', 'getCreated'));
        $row->addColumn($this->createColumn('duration', 'Время разговора, мин', 'getDuration'));
        $row->addColumn($this->createColumn('prevStatus', 'Пред. статус анкеты', 'getPrevStatus'));
        $row->addColumn($this->createColumn('status', 'Статус анкеты', 'getStatus'));
        $row->addColumn($this->createColumn('comment', 'Комментарий пользователя', 'getComment'));

        return $row;

    }





    public function getLogin(PollHistoryComment $row)
    {
        return $row->user->login;
    }

    public function getPhone(PollHistoryComment $row)
    {
        return $row->getRelation('poll')->getRelation('client')->phone;
    }

    public function getId(PollHistoryComment $row)
    {
        return $row->poll_id;
    }

    public function getProject(PollHistoryComment $row)
    {
        if (!isset($row->poll->project)) {
            return '';
        }
        return $row->getRelation('poll')->project->id . '-' . $row->getRelation('poll')->project->title; 
    }

    public function getCreated(PollHistoryComment $row)
    {
        return $row->created; 
    }

    public function getDuration(PollHistoryComment $row)
    {
        return $row->getRelation('poll')->sumCallDuration; 
    }
    
    public function getStatus(PollHistoryComment $row)
    {
        return PollStatus::model()->getTitle($row->poll_status); 
    }
    
    public function getPrevStatus(PollHistoryComment $row, $prevModel = null)
    {
        if (!isset($prevModel)) {
            return '';
        }
        return PollStatus::model()->getTitle($prevModel->poll_status); 
    }

    public function getComment(PollHistoryComment $row)
    {
        return $row->text;
    }


        

}


