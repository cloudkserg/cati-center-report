<?php
namespace Report\GenProject;

use Report\GenProject\Template\ProjectDaysTemplate;

/**
 * ProjectReport
 *
 * @version 1.0.0
 * @copyright Copyright 2011 by Kirya <cloudkserg11@gmail.com>
 * @author Kirya <cloudkserg11@gmail.com>
 */
class ProjectDaysReport extends ProjectReport
{


    protected function getTemplate()
    {
        return new ProjectDaysTemplate($this->_filter);
    }



}
