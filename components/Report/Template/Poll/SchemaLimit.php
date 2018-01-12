<?php
namespace Report\Template\Poll;
/**
 * PollLimit
 *
 * denied column names in report Poll
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class SchemaLimit
{


    private function getColumnNames()
    {
        return array(
            'id', 'created', 'phone',
            'info', 'operator_fio',
            'call_duration', 'poll_duration', 'city',
               'type', 'status', 'client_status', 'comment', 'checkComment' 
        );
    }

    /**
     * checkColumnName
     *
     * @param mixed $name
     * @return boolean
     */
    public function checkColumnName($inputName)
    {
        $name = strtolower($inputName);
        if (in_array($name, $this->getColumnNames())) {
            return false;
        }

        return true;
    }


}
