<?php
/**
 * ReportJob
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ReportJob extends FileProcessJob
{
    const UID = 'report';
    const REPORT_CLASS = 'report_class';
    const NAME_TYPE = 'export_type';
    const DEFAULT_TYPE = 'csv';

    /**
     * getFilePath
     *
     * @return string
     */
    public function getFilePath()
    {
        return Yii::getPathOfAlias('admin.runtime');
    }

    /**
     * getFileName
     *
     * @return string
     */
    public function getFileName()
    {
        $companyId = Yii::app()->user->workCompany->id;
        return "report_{$companyId}_{$this->id}." . $this->getExportType();
    }

    /**
     * getReportClass
     *
     * @return string
     */
    public function getReportClass()
    {
        $reportClass = $this->getParam(self::REPORT_CLASS);
        if (!isset($reportClass)) {
            throw new \Exception('Не задано название отчета');
        }

        return $reportClass;
    }

    /**
     * setReportClass
     *
     * @param string $name
     * @return void
     */
    public function setReportClass($name)
    {
        $this->_params[self::REPORT_CLASS] = $name;
    }


    /**
     * getExportType
     *
     * @return string
     */
    public function getExportType()
    {
        $value = $this->getParam(self::NAME_TYPE);
        if (!isset($value)) {
            return self::DEFAULT_TYPE;
        }

        return $value;
    }

    /**
     * setExportType
     *
     * @param string $value
     * @return void
     */
    public function setExportType($value)
    {
        $this->_params[self::NAME_TYPE] = $value;
    }
}


