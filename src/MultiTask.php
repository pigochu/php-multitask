<?php

namespace multitask;
use multitask\CommandTask;
use multitask\Exception;
/**
 * 
 * @author pigo
 */
class MultiTask {

    /**
     * @var \common\components\task\CommandTask[]
     */
    private $_tasks = [];
    private $_executed = false;
    

    public function __construct() {
        
    }

    /**
     * Add Command Task
     * @param CommandTask $task
     */
    public function addTask(CommandTask $task) {
        $this->_tasks[] = $task;
    }

    /**
     * Run all Task
     * 
     * @param int $wait if $wait is -1 , it will return until all process finished
     * @return int how many task is still running
     * @throws Exception
     */
    public function run($wait = -1) {
        if (0 === count($this->_tasks)) {
            throw new Exception('No task , please add some tasks by MultiTask::addTask().');
        }
        if($this->_executed === false) {
            foreach ($this->_tasks as $task) {
                $task->execute();
            }
            $this->_executed = true;
        }


    }
    
    public function wait($wait = -1) {
        $wait_micro_seconds = 20000;
        $wait_per_process = (int)($wait_micro_seconds / count($this->_tasks));


        $running_time = 0;
        $start_time = microtime(true);
        $runningTasks = 0;
        $running = true;
        while ($running) {
            $runningTasks = 0;
            
            foreach ($this->_tasks as $task) {
                if ($task->isRunning()) {
                    $task->wait($wait_per_process);
                    $runningTasks++;
                }
            }
            if ($runningTasks == 0) {
                $running = false;
            }
            if($wait > -1) {
                $running_time = microtime(true) - $start_time;
                if($running_time >= $wait/1000000) {
                    $running = false;
                }
            }
        }
        return $runningTasks;
    }

}
