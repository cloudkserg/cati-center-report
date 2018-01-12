<?php

/**
 * Description of CallModule
 *
 * @author art3mk4 <Art3mk4@gmail.com>
 */
class ReportModule extends AModule
{

    public function init()
    {
        if (!Yii::app() instanceof CConsoleApplication) {
            throw new CHttpException('500', "Данный модуль только для консольных комманд и дополнительных классов");
        }
        $this->addCommands(__DIR__ . '/commands');

        return parent::init();
    
    }

}
