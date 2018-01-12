<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 16:48
 */

namespace Report\Helper;

use Carbon\Carbon;
use Stat\Payment\Period\PeriodInterface;

class ItemsHelper
{

    /**
     * @param $startTime
     * @param $endTime
     * @return array
     */
    public static function getDates($startTime, $endTime)
    {
        $startDate = Carbon::createFromTimestamp($startTime);
        $endDate = Carbon::createFromTimestamp($endTime);

        $dates = array();
        for ($date = $startDate; $date->lt($endDate); $date->addDay()) {
            $dates[] = $date->copy();
        }

        return $dates;
    }

    /**
     * @param PeriodInterface $item
     * @return string
     */
    public static function getPeriodId(PeriodInterface $item)
    {
        return $item->getId();
    }

    /**
     * @param $item
     * @return mixed
     */
    public static function getId($item)
    {
        return $item->getPrimaryKey();
    }

    /**
     * @param Carbon $item
     * @return int
     */
    public static function getDateId(Carbon $item)
    {
        return $item->toDateString();
    }

    /**
     * @param  mixed $args,...
     * @return mixed
     */
    public static function sum($args = array())
    {
        if (!is_array($args)) {
            $args = func_get_args();
        }
        return array_reduce($args, function ($sum, $item) {
            return $sum + $item;
        }, 0);
    }


}