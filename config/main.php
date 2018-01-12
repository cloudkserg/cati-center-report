<?php
Yii::setPathOfAlias('Report', Yii::getPathOfAlias('admin.modules.report') . '/components/Report');
$coreDir = 'admin.modules.report';
return array(
    'import' => array(
        $coreDir . '.models.*',
        $coreDir . '.models.filter.*',
        $coreDir . '.components.*'
    )
);
