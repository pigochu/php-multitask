<?php

namespace multitask;

use multitask\ExecuteException;
use multitask\Event;

class CommandTask {

    private static $_autoId = 1;
    private $_taskId;
    protected $_pipes;
    private $_windowsPipes;
    protected $_process;
    private $_command;
    private $_error;
    private $_events = array();
    private $_exitCode;
    private $_isWindows = false;
    private $_windowsOffset = array(0,0,0);
    private $_windowsFilesize = array(0,0,0);

    /**
     * Construct
     * 
     * @param string $command command you want to run
     * @param string $taskTd you can assign a custome task ID
     */
    public function __construct($command, $taskId = null) {
        $this->_command = $command;
        if ($taskId === null) {
            $this->_taskId = static::$_autoId++;
        }


        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->_isWindows = true;
        }

    }

    /**
     * execute command in background
     * @throws ExecuteException
     */
    public function execute() {
        $cwd = $env = null;
        // $other_options = array('bypass_shell' => true, 'suppress_errors' => false);
        $other_options = null;
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        if($this->_isWindows) {
            // windows can not use pipe
            $this->_windowsPipes = array(
                tmpfile(),
                tmpfile(),
                tmpfile(),
            );

            $meta0 = stream_get_meta_data($this->_windowsPipes[0]);
            $meta1 = stream_get_meta_data($this->_windowsPipes[1]);
            $meta2 = stream_get_meta_data($this->_windowsPipes[2]);

            $descriptorspec = array(
                0 => array("file", $meta0['uri'] , "r"),
                1 => array("file", $meta1['uri'] , "w"),
                2 => array("file", $meta2['uri'] ,"w"),
            );
        }


        $process = proc_open($this->_command, $descriptorspec, $this->_pipes, $cwd, $env, $other_options);
        
        if (false === $process) {
            $error = error_get_last();
            $this->_process = null;
            throw new ExecuteException($error);
        } else {
            if($this->_isWindows) {
                $this->_pipes = $this->_windowsPipes;
            } else {
                foreach($this->_pipes as $stream) {
                    stream_set_blocking($stream, 0);
                }
            }
            $this->_process = $process;
        }
    }

    /**
     * Send message to child's STDIN
     * @param type $message
     * @return int|false
     */
    public function sendMessage($message) {
        return @fwrite($this->_pipes[0] , $message);
    }
    
    /**
     * Wait Task Event
     * If any Event , it will call your callback from CommandTask::on()
     * 
     * @param int $micro_seconds
     * @return boolean if process still running return true , else return false
     */
    public function wait($micro_seconds = 20000) {
        
        if($this->_isWindows) {
            $wait_seconds = 0;
            while(!$this->_selectWindows() && $wait_seconds < $micro_seconds) {
                usleep(1000);
                $wait_seconds+=1000;
            }
        } else {
            $this->select($micro_seconds);
        }
        return $this->isRunning();
    }

    /**
     * Simulate select for windows
     * Because windows has bug
     */ 
    private function _selectWindows() {
        $triggered = false;
        if (isset($this->_events[Event::CHILD_OUTPUT_MESSAGE])) {
            if(!$this->_feof(Event::CHILD_OUTPUT_MESSAGE)) {
                    $this->_triggerEvent(Event::CHILD_OUTPUT_MESSAGE);
                    $triggered = true;
            }
        }
        if (isset($this->_events[Event::CHILD_OUTPUT_ERROR])) {
            if(!$this->_feof(Event::CHILD_OUTPUT_ERROR)) {
                    $this->_triggerEvent(Event::CHILD_OUTPUT_ERROR);
                    $triggered = true;
            }
        }
        return $triggered;
    }
    
    /**
     * select all pipes and it will trigger bind events
     * @param int $sec
     * @param int $usec
     */
    private function select($micro_seconds) {

        
        $write = [];
        $read = [];
        $except = null;

        if (isset($this->_events[Event::CHILD_READY_RECEIVE])) {
            $write[] = $this->_pipes[Event::CHILD_READY_RECEIVE];
        }
        if (isset($this->_events[Event::CHILD_OUTPUT_MESSAGE])) {
            $read[] = $this->_pipes[Event::CHILD_OUTPUT_MESSAGE];
        }
        if (isset($this->_events[Event::CHILD_OUTPUT_ERROR])) {
            $read[] = $this->_pipes[Event::CHILD_OUTPUT_ERROR];
        }

        if (!isset($read[0])) {
            $read = null;
        }
        if (!isset($write[0])) {
            $write = null;
        }

        $ret = @stream_select($read, $write, $except, 0, $micro_seconds);
        
        
        if ($ret > 0) {
            if (count($read) > 0) {
                foreach ($read as $s) {
                    $key = array_search($s, $this->_pipes);
                    if ($key !== false) {
                        return $this->_triggerEvent($key);
                    }
                }
            }
            if (count($write) > 0) {
                $this->_triggerEvent(Event::CHILD_READY_RECEIVE);
            }
        }

        return $ret;
    }

    private function _triggerEvent($event) {
        
        $data = null;
        if(isset($this->_events[$event])) {
            if($event !== Event::CHILD_EXIT) {
                if($this->_isWindows) {
                    $data = $this->_read($event);
                } else {
                    $data = stream_get_contents($this->_pipes[$event]);
                    if(!isset($data[0])) {
                        return;
                    }
                }
            }
            call_user_func($this->_events[$event], new Event($event, $this, $data));
        }
    }

    /**
     * Bind Event
     * @param int $event Event::CHILD_OUTPUT_MESSAGE or Event::CHILD_OUTPUT_ERROR
     * @param mixed $callback
     * @see multitask\Event
     */
    public function on($event, $callback) {
        
        if (!in_array($event, array(1,2,99)) ) {
            throw new Exception('First paramater must be Event::CHILD_OUTPUT_MESSAGE or Event::CHILD_OUTPUT_ERROR or Event::CHILD_EXIT.');
        }
        $this->_events[$event] = $callback;
    }

    /**
     * Get this command running status
     * @return boolean
     */
    public function isRunning() {
        if (is_resource($this->_process)) {
            $status = proc_get_status($this->_process);

            if (false === $status['running']) {
                if($this->_isWindows) {
                    $this->_selectWindows();
                } else {
                    $this->select(0);
                }
                @proc_close($this->_process);
                $this->_process = null;
                $this->_exitCode = $status['exitcode'];
                $this->_triggerEvent(Event::CHILD_EXIT);
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Get command string
     * @return string
     */
    public function getCommand() {
        return $this->_command;
    }

    /**
     * Get process exit code
     * @return int
     */
    public function getExitCode() {
        return $this->_exitCode;
    }

    /**
     * Get Task ID
     * @return string
     */
    public function getTaskId() {
        return $this->_taskId;
    }
    
    /**
     * Read data
     * 
     * This method only for windows , because windows has bug :p
     * @param int $event
     * @return string|false
     */
    private function _read($event) {
        $fd = $this->_pipes[$event];
        fseek($fd, $this->_windowsOffset[$event]);
        $read_length = $this->_windowsFilesize[$event] - $this->_windowsOffset[$event];
        $data = fread($fd , $read_length);
        $this->_windowsOffset[$event] += $read_length;
        return $data;
    }
    
    /**
     * Check file is eof
     * This method only for windows , because windows has bug :p
     * @param int $event
     * @return string|false
     */
    private function _feof($event) {
        $meta = stream_get_meta_data($this->_pipes[$event]);
        clearstatcache(true , $meta['uri']);
        $stat = stat($meta['uri']);
        $this->_windowsFilesize[$event] = $stat['size'];

        
        if($this->_windowsFilesize[$event] === $this->_windowsOffset[$event]) {
            return true;
        }
        
        return false;
    }
    
}
