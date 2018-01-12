<?php
/**
 * Created by PhpStorm.
 * User: kirya
 * Date: 01.07.15
 * Time: 12:50
 */

namespace Report\Row;

use Export\Schema\Column as ExportColumn;

interface ColumnInterface
{

    /**
     * @return ExportColumn
     */
    public function getExportColumn();

    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param ModelInterface $model
     * @return int|string|array
     */
    public function computeValue(ModelInterface $model);

    /**
     * @return Context
     */
    public function getContext();


    /**
     * @param $id
     * @return void
     */
    public function setId($id);


    /**
     * @param ExportColumn $column
     * @return void
     */
    public function setExportColumn(ExportColumn $column);


}