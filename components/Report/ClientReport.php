<?php
namespace Report;

use ClientReportFilter, Client;
use Report\Template\ClientTemplate;

/**
 * ClientReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ClientReport extends Report
{
    const PAGE_LIMIT = 3000;

    /**
     * _count
     *
     * @var int
     */
    private $_count;


    /**
     * createFilter
     *
     * @return ClientReportFilter
     *
     */
    protected function createFilter()
    {
        return new ClientReportFilter();
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
            $client = $this->createClient();
            $client->getDbCriteria()->limit = self::PAGE_LIMIT;
            $client->getDbCriteria()->offset = $offset;
            $clients = $client->findAll();
            foreach ($clients as $item) {
                yield $item;
            }

            $offset += self::PAGE_LIMIT;
        
        }
    }


    /**
     * @return \Client
     */
    private function createClient()
    {
        return Client::model()
            ->applySearch($this->_filter);
    } 

    /**
     * getCountModels
     *
     * @return mixed
     */
    protected function getCountModels()
    {
        if (!isset($this->_count)) {
            $this->_count = $this->createClient()->count();
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
            "Отчет по клиентам",
            "Всего " . $this->getCountModels() . " клиентов."
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
        );
    }


    /**
     * @inheritdoc
     */
    protected function getTemplate()
    {
        return new ClientTemplate($this->_filter);
    }







}
