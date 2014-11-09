<?php
require("../src/Exception.php");
require("../src/ExecuteException.php");
require("../src/Event.php");
require("../src/CommandTask.php");


use multitask\CommandTask;
use multitask\Event;



$cmd = dirname(__FILE__) . DIRECTORY_SEPARATOR . "child.php";
echo "Main Process Start ..." . PHP_EOL;

$task = new CommandTask("php $cmd");
$task->on(Event::CHILD_OUTPUT_MESSAGE , function(Event $e) {
    echo "Task " . $e->task->getTaskId() . " > write message : " . $e->data;
    flush();
});

$task->on(Event::CHILD_OUTPUT_ERROR , function(Event $e) {
    echo "Task " . $e->task->getTaskId() . " > write error : " . $e->data;
    flush();
});

$task->execute();
while($task->wait(200000)) {
    echo "Task " . $task->getTaskId() . " > still running" . PHP_EOL;
}
