<?php
namespace Report\Template;
use PollReportFilter;
use Report\Template\Poll\Question\DeleteQuestion;
use Report\Template\Poll\Question\QuestionFabric;
use Report\Helper\ItemsHelper;
use Report\Row\Row;
use HandlerResult;
use Report\Template\Poll\Handler\ReportHandler;
use Handler\UseHandlerResultInterface;

class PollTemplate extends RowTemplate
{

    /**
     * _filter
     *
     * @var PollReportFilter
     */
    protected $_filter;


    /**
     *
     */
    protected function buildRow()
    {
        $row = new Row();

        $row->addColumn($this->createColumn('id', 'Ид', 'getId'));
        $row->addColumn($this->createColumn('created', 'Создано', 'getCreated'));
        $row->addColumn($this->createColumn('finished', 'Завершено', 'getFinished'));
        $row->addColumn($this->createColumn('phone', 'Телефон', 'getPhone'));

        
        $clientBlock = new \Report\Row\Block('client', array($this, 'getClientInfoSchemes'),
            array('Report\Helper\ItemsHelper', 'getId')
        );
        $clientBlock->addColumn($this->createColumn('info', array($this, 'getLabelInfo'), 'getInfo'));

        $row->addBlock($clientBlock);

        $row->addColumn($this->createColumn('operator_fio', 'Фио оператора', 'getFio'));
        $row->addColumn($this->createColumn('call_duration', 'Длительность разговора, сек', 'getCallDuration'));
        $row->addColumn($this->createColumn('poll_duration', 'Длительность заполнения анкеты, сек', 'getPollDuration'));
        $row->addColumn($this->createColumn('city', 'База', 'getCity'));
        $row->addColumn($this->createColumn('type', 'Тип анкеты', 'getType'));
        $row->addColumn($this->createColumn('status', 'Статус анкеты', 'getStatus'));
        $row->addColumn($this->createColumn('client_status', 'Статус телефона', 'getClientStatus'));
        $row->addColumn($this->createColumn('comment', 'Комментарий', 'getComment'));
        $row->addColumn($this->createColumn('checkComment', 'Проверка', 'getCheckComment'));


        $row->addColumns($this->getQuestionColumns());
    
        array_walk($this->getReportHandlers(), function (ReportHandler $handler) use ($row) {
            $row->addColumn($this->createClosureColumn(
                $handler->getId(),
                $handler->getTitle(),
                array($handler, 'getValue')
            ));
        });

        if ($this->_filter->useDeleted) {
            array_walk(DeleteQuestion::findAll($this->getProject()), function (DeleteQuestion $question) use ($row) {
               $row->addColumns($question->getColumns());
            });
        }

        return $row;

    }

    /**
     * @return \type
     */
    public function getClientInfoSchemes()
    {
        return $this->getProject()->clientInfoSchemes;
    }


    /**
     * getReportHandlers
     *
     * @return ReportHandler[]
     */
    private function getReportHandlers()
    {
        return array_map(
            function (UseHandlerResultInterface $handlerClass) {
                return new ReportHandler($handlerClass);
            },
            HandlerResult::model()
                ->forPollTemplate($this->getProject()->poll_template_id)
                ->getHandlers()
        );
    }


    /**
     * getQuestionColumns
     *
     * @return array
     */
    private function getQuestionColumns()
    {
        $columns = array();

        $questions = \Question::model()
            ->forPollTemplate($this->getProject()->poll_template_id)
            ->forNotSubtype()
            ->sort()
            ->findAll();
        foreach ($questions as $question) {
            $reportQuestion = QuestionFabric::create($question, $this->_filter->showAnswerText, $this->_filter->showControl);
            $columns = array_merge($columns, $reportQuestion->getColumns());
        }

        return $columns;
    }

    public function getId(\Poll $row)
    {
        return $row->id;
    }

    public function getCreated(\Poll $row)
    {
        return \Yii::app()->datetimeHelper->formatWeb($row->created);
    }
    
    public function getFinished(\Poll $row)
    {
        return \Yii::app()->datetimeHelper->formatWeb($row->finished);
    }

    public function getPhone(\Poll $row)
    {
        return $row->client_phone;
    }

    public function getFio(\Poll $row)
    {
        return $row->getRelation('operator')->abbrFullname;
    }

    public function getCallDuration(\Poll $row)
    {
        return $row->sumCallDuration;
    }

    public function getPollDuration(\Poll $row)
    {
        return $row->fullDuration;
    }

    public function getCity(\Poll $row)
    {
        return $row->getRelation('city')->title;
    }

    public function getType(\Poll $row)
    {
        return \PollType::model()->getTitle($row->type);
    }
    
    public function getClientStatus(\Poll $row)
    {
        return $row->getRelation('client')->statusTitle;
    }

    public function getStatus(\Poll $row)
    {
        return $row->statusTitle;
    }

    public function getComment(\Poll $poll)
    {
        if (!isset($poll->client)) {
            return '';
        }
        $comments = $poll->client->getCallComments();
        $texts = array();
        foreach($comments as $comment) {
            $texts[] = $comment->time. ':' . $comment->text;
        }
        return implode(', ', $texts);
    }

    public function getCheckComment(\Poll $row)
    {
        return $row->comment;
    }

    public function getLabelInfo(\ClientInfoScheme $scheme)
    {
        return $scheme->name;
    }

    public function getInfo(\Poll $row, \ClientInfoScheme $scheme)
    {
        $info = $row->getRelation('client')->getInfo($scheme);
        if (!isset($info)) {
            return '';
        }
        return $info->value;
    }




}
