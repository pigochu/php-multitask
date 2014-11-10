<?php
require("../src/Exception.php");
require("../src/ExecuteException.php");
require("../src/Event.php");
require("../src/CommandTask.php");
require("../src/MultiTask.php");

use multitask\Event;
use multitask\CommandTask;
use multitask\MultiTask;




$cmd = dirname(__FILE__) . DIRECTORY_SEPARATOR . "child.php";
echo "Sample create 4 tasks and wait all task finish." . PHP_EOL;

$multitask1 = new MultiTask();

for($i=0; $i< 4 ; $i++) {
    $task = new CommandTask("php $cmd");
    $task->on(Event::CHILD_OUTPUT_MESSAGE , function(Event $e) {
        echo "Task " . $e->task->getTaskId() . " > write message : " . $e->data;
        flush();
    });

    $task->on(Event::CHILD_OUTPUT_ERROR , function(Event $e) {
        echo "Task " . $e->task->getTaskId() . " > write error : " . $e->data;
        flush();
    });
    $multitask1->addTask($task);
}

$multitask1->run();
// Wait until all process exit
$multitask1->wait();


$multitask2 = new MultiTask();
echo "Sample create 4 tasks and wait 0.2 seconds we can do other thing." . PHP_EOL;
for($i=0; $i< 8 ; $i++) {
    $task = new CommandTask("php $cmd");
    $task->on(Event::CHILD_OUTPUT_MESSAGE , function(Event $e) {
        echo "Task " . $e->task->getTaskId() . " > write message : " . $e->data;
        flush();
    });

    $task->on(Event::CHILD_OUTPUT_ERROR , function(Event $e) {
        echo "Task " . $e->task->getTaskId() . " > write error : " . $e->data;
        flush();
    });
    
    $task->on(Event::CHILD_EXIT , function(Event $e) {
        echo "Task " . $e->task->getTaskId() . " > exit " . $e->task->getExitCode() . PHP_EOL;
        flush();
    });
    
    $multitask2->addTask($task);
}
$multitask2->run();
while($multitask2->wait(200000)) {
    echo "Main Process : hello" . PHP_EOL;
}


