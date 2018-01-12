<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 09.07.15
 * Time: 17:38
 */

namespace Report\Helper;

use Report\Row\ModelInterface;
use Report\Row\Block;
use Stat\Payment\Strategy;

class ReportHelper
{

    /**
     * @param ModelInterface $model
     * @param $columnId
     * @param array $blockValues
     * @return mixed
     */
    public static function getValue(ModelInterface $model, $columnId, $blockValues = array())
    {
        if (!empty($blockValues)) {
            $columnId = Block::compoundColumnId($blockValues, $columnId);
        }
        $column = $model->getColumn($columnId);
        return $model->getValue($column);
    }


    /**
     * @param Strategy $strategy
     * @return string|\type
     */
    public static function getOperatorGroupString(Strategy $strategy)
    {

        if ($strategy->isAuto()) {
            return $strategy->getOperatorGroup()->title . '(' .
            $strategy->getDefiner() . ')';
        }

        return $strategy->getOperatorGroup()->title;
    }
} 