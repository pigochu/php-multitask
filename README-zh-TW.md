php-multitask
=============

這個專案主要是為了執行多個命令列程式(Console Command)，可在背景執行，並且是以事件驅動方式來獲取子命令(子行程)所拋出的 STDOUT 及 STDERR 訊息。

其運作原理是使用 php 內建的 proc_open 達成的，Windows 和 Linux 皆能運行，但運行的方式有一些不同。

在 Windows 中，不使用 pipes 的方式來獲取 STDOUT 及 STDERR ，而改用 php 內建的 tmpfile() 建立暫存檔才得以實現。


## 環境需求 ##

- PHP 5.4 以上，PHP 5.3 沒測過搞不好也可以
- OS : Windows 及 Linux ，其它 Unix 類的搞不好可以沒測試過就是了

## 範例 ##

可以下載整個專案後，於命令列模式執行以下兩個範例

- 單一背景程式   : php sample/main_single_task.php
- 多程式同時執行 : php sample/main_multi_task.php

## Composer 安裝法 ##

請參考 composer.json 自行加入對應資料於您的專案中

## 類別說明 ##

- CommandTask : 可以建立一個命令列模式的任務
- MultiTask : 可以將多個 CommandTask 加入至 MultiTask 同時執行並且獲取事件


## 這專案有甚麼屁用 ##

沒甚麼屁用，本來是為了解決要大量 INSERT 到 MySQL 發現效能太差，必須同時執行多個背景程式才開發的。

文件很簡陋，大概看下 sample 的範例應該不難懂。