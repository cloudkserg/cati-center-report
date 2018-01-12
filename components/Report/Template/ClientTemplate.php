<?php
namespace Report\Template;
use Client;
use Report\Row\Row;
use Call;

/**
 * ClientTemplate
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ClientTemplate extends RowTemplate
{

    /**
     * @return Row
     */
    protected function buildRow()
    {
        $row = new Row();
        $row->addColumn($this->createColumn('phone', 'Тел. номер', 'getPhone'));


        $clientBlock = new \Report\Row\Block('client', array($this, 'getClientInfoSchemes'),
            array('Report\Helper\ItemsHelper', 'getId')
        );
        $clientBlock->addColumn($this->createColumn('info', array($this, 'getLabelInfo'), 'getInfo'));
        $row->addBlock($clientBlock);

        $row->addColumn($this->createColumn('city', 'База', 'getCity'));
        $row->addColumn($this->createColumn('lastCallStart', 'Время последнего звонка', 'getLastCallStart'));
        $row->addColumn($this->createColumn('statusTitle', 'Статус', 'getStatusTitle'));
        $row->addColumn($this->createColumn('redialTime', 'Когда перезвонить', 'getRedialTime'));
        $row->addColumn($this->createColumn('tries', 'Сколько раз звонили', 'getTries'));
        
        if ($this->_filter->useCallHistory) {
            $callBlock = new \Report\Row\Block('call', function () { return range(1, 20); });
            $callBlock->addColumn($this->createColumn('callDate', array($this, 'getLabelCallDate'), 'getCallDate'));
            $callBlock->addColumn($this->createColumn('callStatus', array($this, 'getLabelCallStatus'), 'getCallStatus'));
            $row->addBlock($callBlock);
        }

        return $row;
    }

    public function getLabelInfo(\ClientInfoScheme $scheme)
    {
        return $scheme->name;
    }
    
    /**
     * @return \type
     */
    public function getClientInfoSchemes()
    {
        return $this->getProject()->clientInfoSchemes;
    }

    public function getInfo(\Client $row, \ClientInfoScheme $scheme)
    {
        $info = $row->getInfo($scheme);
        if (!isset($info)) {
            return '';
        }
        return $info->value;
    }


    public function getLabelCallDate($order)
    {
        return $order . ' звонок дата';
    }
    
    public function getLabelCallStatus($order)
    {
        return $order . ' звонок статус';
    }

    private function getCall(Client $row, $order)
    {
        $cacheKey = $row->id . '_' . $order;
        return $this->_cache->cacheValue($cacheKey, function () use ($row, $order) {
            $criteria = new \CDbCriteria();
            $criteria->offset = $order - 1;
            $criteria->limit = 1;
            return Call::model()
                ->forClient($row->id)
                ->sort()
                ->find($criteria);
        });
    }

    
    public function getCallDate(Client $row, $order)
    {
        $call = $this->getCall($row, $order);
        if (!isset($call)) {
            return '';
        }
        return \Yii::app()->datetimeHelper->formatWeb($call->start);
    }
    
    public function getCallStatus(Client $row, $order)
    {
        $call = $this->getCall($row, $order);
        if (!isset($call)) {
            return '';
        }
        return $call->statusTitle;
    }

    public function getPhone(Client $row)
    {
        return $row->phone;
    }

    public function getCity(Client $row)
    {
        return $row->getRelation('city')->title;
    }

    public function getLastCallStart(Client $row)
    {
        $start = $row->getRelation('lastCall')->start;
        if (empty($start)) {
            return '';
        }
        return \Yii::app()->datetimeHelper->formatWeb($start);
    }

    public function getStatusTitle(Client $row)
    {
        if ($row->status == \ClientStatus::SUCCESS) {
            return $row->getRelation('lastPoll')->statusTitle;
        }
        return \ClientStatus::model()->getTitle($row->status);
    }

    public function getRedialTime(Client $row)
    {
        $time = $row->redialTime;
        if (empty($time)) {
            return '';
        }
        return \Yii::app()->datetimeHelper->formatWeb($time);
    }

    public function getTries(Client $row)
    {
        return $row->tries;
    }


}


