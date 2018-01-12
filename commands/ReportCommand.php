<?php
/**
 * ReportCommand
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ReportCommand extends TemplateProcessJobCommand
{
    /**
     * nameClassJob
     *
     * @var string
     */
    public $nameClassJob = 'ReportJob';


    /**
     * actionIndex
     *
     * @return void
     */
    public function actionIndex()
    {
        $this->job->setProcess();
        $this->checkFileNonExist();

        $report = $this->getReport(
            $this->job->reportClass, 
            $this->getExport(),
            $this->updateProgressFunction()
        );

        $report->generateHeaders();
        $this->job->result = $report->generateRows();
        $this->job->update(array('result'));

        $report->saveFile($this->job->getFilePath(), $this->job->getFileName());

        $this->job->setStop();
    
    }


    /**
     * getReport
     *
     * @param mixed $reportClass
     * @param \ExportComponent $export
     * @param Callable $tick
     * @return \Report\Report
     */
    private function getReport($reportClass, \ExportComponent $export, $tick)
    {
        if (!class_exists($reportClass)) {
            $this->error('Нет такого класса для отчета - ' . $reportClass);
        }

        $params = $this->job->params;
        unset($params[ReportJob::REPORT_CLASS]);
        return $reportClass::create($params, $export, $tick);
    }

    /**
     * updateProgressFunction
     *
     * @return Closure
     */
    public function updateProgressFunction()
    {
        $job = $this->job;
        return function ($index, $count) use ($job) {
            if ($count == 0) {
                return 0;
            }
            $newProgress = round($index / $count * 100);

            print $index . "/" . $count . "\n";
            if ($newProgress - $job->progress > 1) {
                $job->updateProgress($newProgress);
            }
        };
    }

    /**
     * getExport
     *
     * @return Export
     */
    private function getExport()
    {
        $export = ExportComponent::create($this->job->exportType);
        $export->charset = (isset(\Yii::app()->params['reportCharset']) ?
            \Yii::app()->params['reportCharset'] : 'CP1251');
        return $export;
    }

    /**
     * checkFileNonExist
     */
    private function checkFileNonExist()
    {
        $filePath = $this->job->getFileFullname();
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $this->error('Не удалось удалить существующий файл');
            }
        }
    }
}


