<?php
namespace Report\Template\Poll\Handler;
use Handler\UseHandlerResultInterface;
class ReportHandler
{

    private $_handler;


    public function __construct(UseHandlerResultInterface $handler)
    {
        $this->_handler = $handler;
    }


    public function getId()
    {
        return 'handler_' . $this->_handler->getId();
    }

    public function getTitle()
    {
        return $this->_handler->getResultTitle();
    }

    public function getValue(\Poll $poll)
    {
        $handlerResult = \HandlerResult::model()
            ->forPoll($poll->id)
            ->forHandler($this->_handler->getId())
            ->find();
        if (!isset($handlerResult)) {
            return '';
        }
        return $this->_handler->getResult($handlerResult);
    }


}


