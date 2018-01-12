<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 15:05
 */

namespace Report\Row;

interface PartInterface
{


    /**
     * @param Context $context
     * @return ColumnInterface[]
     */
    public function getColumns(Context $context);

}