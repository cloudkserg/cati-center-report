<?php
namespace Report\Template;

use Export\Style\CellStyle;
use Export\Style\Color;
use Operator, Call, Poll, Project;
use Report\Row\Block;
use Report\Row\Row;
use Stat\Helper\PollHelper;
use Stat\OperatorRecord\OperatorRecordBuilder;
use Stat\Payment\Operator\OperatorPeriodStrategy;
use Stat\Payment\PaymentSearcher;
use OperatorRecord, OperatorPeriod;
use Stat\Payment\Period\PeriodFactory;
use Stat\Payment\Period\PeriodInterface;
use Stat\Searcher\CallSearcher;
use Stat\Searcher\OperatorPeriodSearcher;
use Stat\Searcher\PollSearcher;
use Stat\Searcher\ProjectPeriodSearcher;
use OperatorGroupRecord;

/**
 * ControlOperatorTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ControlOperatorTemplate extends RowTemplate
{


    /**
     * @var \OperatorControlOperatorFilter
     */
    protected $_filter;



    protected function getDefaultLabelStyle()
    {
        return new CellStyle(null, null, null, true);
    }


    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('id', 'Фио', 'getId'));
        $row->addColumn($this->createColumn('fio', 'ФИО польностью', 'getFio'));
        $row->addColumn($this->createColumn('login', 'Логин', 'getLogin'));
        $row->addColumn($this->createColumn('group', 'Группа', 'getGroup'));
        $row->addColumn($this->createColumn('groupHistory', 'История смены группы', 'getGroupHistory'));
        $row->addColumn($this->createColumn('phone', 'Телефон', 'getPhone'));
        $row->addColumn($this->createColumn('created', 'Дата регистрации', 'getCreated'));
        $row->addColumn($this->createColumn('activeCountDays', 'Последняя активность, дней', 'getActiveCountDays'));

        return $row;

    }

    public function getId(Operator $operator)
    {
        return $operator->id;
    }

    public function getLogin(Operator $operator)
    {
        return $operator->login;
    }

    public function getGroup(Operator $operator)
    {
        return $operator->getRelation('operatorGroup')->title;
    }

    public function getFio(Operator $operator)
    {
        return $operator->fullname;
    }

    public function getPhone(Operator $operator)
    {
        return $operator->phone;
    }

    public function getCreated(Operator $operator)
    {
        return \Yii::app()->datetimeHelper->formatWeb($operator->created);
    }

    public function getActiveCountDays(Operator $operator)
    {
        return $operator->getCountDays();
    }

    public function getGroupHistory(Operator $operator)
    {
        return array_reduce($operator->operatorGroupRecords, function ($output, OperatorGroupRecord $record) {
            if (!empty($output)) {
                $output .= ";\n ";
            }
            $dtHelper = \Yii::app()->datetimeHelper;
            $output .= $record->getRelation('operatorGroup')->title . ' с ' .
                $dtHelper->formatWeb($record->time);
            if (!empty($record->end)) {
                $output .= ' по ' . $dtHelper->formatWeb($record->end);
            }
            return $output;
        }, '');
    }
}


