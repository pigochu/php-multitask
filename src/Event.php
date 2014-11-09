<?php
namespace multitask;

class Event {

    /**
     * You can write message to child's stdin
     */
    const CHILD_READY_RECEIVE = 0;
    
    /**
     * Child process output to stdout
     */
    const CHILD_OUTPUT_MESSAGE = 1;
    
    /**
     * Child process output to stderr
     */
    const CHILD_OUTPUT_ERROR = 2;
    
    /**
     * alias Event::CHILD_CAN_WRITE
     */
    const STDIN = 0;
    /**
     * alias Event::CHILD_OUTPUT_MESSAGE
     */
    const STDOUT = 1;
    /**
     * alias Event::CHILD_OUTPUT_ERROR
     */
    const STDERR = 2;

    const CHILD_EXIT = 99;
    /**
     * Event Type
     * @var type 
     */
    public $type;
    
    /**
     * @var CommandTask reference to CommandTask
     */
    public $task;

    /**
     * @var string
     */
    public $data;

    public function __construct($type, $task, $data) {
        $this->type = $type;
        $this->task = $task;
        $this->data = $data;
    }

}
