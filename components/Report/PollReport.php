<?php
namespace Report;

use Poll, PollReportFilter;
use Report\Template\PollTemplate;

/**
 * PollReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class PollReport extends Report
{
    const PAGE_LIMIT = 5000;

    /**
     * _count
     *
     * @var int
     */
    private $_count;

    /**
     * createFilter
     *
     * @return PollReportFilter
     *
     */
    protected function createFilter()
    {
        return new PollReportFilter();
    }

    /**
     * getModels
     *
     * @return array
     */
    protected function getModels()
    {
        $offset = 0;
        $count = $this->getCountModels();
        while ($offset <= $count) {

            $poll = $this->createPoll();
            $poll->getDbCriteria()->limit = self::PAGE_LIMIT;
            $poll->getDbCriteria()->offset = $offset;
            $items = $poll->sort('old')->findAll();

            foreach ($items as $item) {
                yield $item;
            }

            $offset += self::PAGE_LIMIT;
        
        }
    }

    /**
     * @return \Poll
     */
    private function createPoll()
    {
        return Poll::model()
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
            $this->_count = $this->createPoll()->count();
        }
        return $this->_count;
    }


    /**
     * getTitles
     *
     * @return array
     */
    protected function getTitles()
    {
        return  array(
            "Отчет по анкетам",
            "Всего " . $this->getCountModels() . " анкет."
        );
    }




    protected function getTemplate()
    {
        return new PollTemplate($this->_filter);
    }


    /**
     * getFormatters
     *
     * @return array
     */
    protected function getFormatters()
    {
        return array(
        );
    }




}
