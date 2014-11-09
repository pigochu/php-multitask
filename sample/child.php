<?php

$out = fopen("php://stdout" , "w");
$err = fopen("php://stderr" , "w");

for($i=0 ; $i<5; $i++) {
    fputs($out , "stdout message $i" . PHP_EOL);
    fputs($err , "stderr message $i" . PHP_EOL);
    fflush($out);
    fflush($err);
    flush();
    sleep(1);
}
echo "finish" . PHP_EOL;
exit(33);