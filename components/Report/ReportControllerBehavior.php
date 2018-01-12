<?php
namespace Report;
use ReportJob;
/**
 * ReportBehavior
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ReportControllerBehavior extends \TemplateProcessControllerBehavior
{

    /**
     * moduleName
     *
     * @var string
     */
    public $moduleName = 'report';

    /**
     * commandName
     *
     * @var string
     */
    public $commandName = 'report';

    /**
     * reportClass
     *
     * @var string
     */
    public $reportClass;

    /**
     * jobModelName
     *
     * @var string
     */
    public $jobModelName = 'ReportJob';

    /**
     * filter
     *
     * @var callable
     */
    public $filter;

    /**
     * @var callable - func(ReportJob $job)
     */
    public $jobInit;


    /**
     * actionStart
     * 
     * @throws CHttpException
     */
    public function actionStart()
    {
        if (isset($this->filter)) {
            $filterModel = call_user_func($this->filter);
            if (!$filterModel->validate()) {
                throw new \CHttpException('404', 'Неверные параметры фильтра');
            }
            return $this->start($filterModel->attributes);
        } 
        
        return $this->start();
    }


    /**
     * actionInfo
     * 
     * @param int $job_id
     */
    public function actionInfo($job_id)
    {
        $job = $this->getJob($job_id);

        $this->getProcessManager()->checkError($job);

        \Yii::app()->ajax->sendRespond(
            true,
            'Идёт выполнение команды',
            array(
                'rows' => $job->result,
                'finish' => ($job->status != \ProcessJobStatus::PROCESS),
                'progress' => $job->progress,
                 'extension' => $job->getExportType()
            )
        );
    }

    /**
     * actionFile
     * 
     * @param unknown $job_id
     * @throws CHttpException
     */
    public function actionFile($job_id)
    {
        $job = $this->getJob($job_id);

        $filePath = $job->getFileFullname();
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \CHttpException('404', 'Файл не существует или не доступен для чтения');
        }

        \Yii::app()->request->sendFile(basename($filePath), file_get_contents($filePath));
    }

    /**
     * @param array $params
     * @param array $processParams
     * @param callable|null $jobInit
     * @throws \CHttpException
     */
    public function start($params = array(), $processParams = array(), callable $jobInit = null)
    {
        if (!isset($jobInit)) {
            $jobInit = $this->jobInit;
        }

        $reportClass = $this->generateReportClass($params);

        $params = array_merge($params, array(ReportJob::REPORT_CLASS => $reportClass));
        return parent::start($params, $processParams, $jobInit);
    }

    /**
     * @param array $filterParams
     * @return string
     */
    private function generateReportClass(array $filterParams)
    {
        if (is_callable($this->reportClass)) {
            return call_user_func($this->reportClass, $filterParams);
        }

        return $this->reportClass;
    }
}


