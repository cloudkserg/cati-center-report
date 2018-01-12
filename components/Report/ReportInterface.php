<?php
/**
 * Created by PhpStorm.
 * User: abba
 * Date: 26.06.15
 * Time: 14:06
 */

namespace Report;

use ExportComponent;

interface ReportInterface
{

    /**
     * @param array $attributes
     * @param ExportComponent $export
     * @param callable $tick
     */
    public function __construct(array $attributes, ExportComponent $export, callable $tick);

    /**
     * @return void
     */
    public function generateHeaders();

    /**
     * @return int
     */
    public function generateRows();


    /**
     * @param $filePath
     * @param $fileName
     * @return void
     */
    public function saveFile($filePath, $fileName);

} 