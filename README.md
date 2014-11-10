php-multitask
=============

This Project can run single or multipe console command in backgroud , and you can get child's message(stdout or stderr) by event handler.

中文說明請參考 [README-zh-TW.md](README-zh-TW.md)

## Requirement ##

- PHP 5.4 or above
- OS : Support Windows and Linux , maybe other Unix-Like OS can run , but I never test it.

## Sample ##

Download the project , then run those two sample.

- Single task sample : php sample/main_single_task.php
- Multi Task sample : php sample/main_multi_task.php

## Composer Install ##

Please refer composer.json add your own mapping data in your project

## Class ##

- CommandTask : You can create command task by this class and run it in background.
- MultiTask : You can add many CommandTask in this , and run it at the same time.

