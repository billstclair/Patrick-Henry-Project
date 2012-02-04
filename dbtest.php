<?php

// Test code for db.php

require_once "db.php";

$db = new db();

function putit($x) {
  global $db;
  $db->putinfo($x, $x);
}

function put3($x) {
  putit($x);
  putit($x+1);
  putit($x+2);
}

function put3s($nums) {
  foreach ($nums as $num) {
    put3($num);
  }
}

function echogetinfo($x) {
  global $db;
  $info = $db->getinfo($x);
  echo "$x: $info\n";
}

function get1($num) {
  global $db;
  $x = $db->getinfo($num);
  if ($x != $num) echo "getinfo($num) != $x\n";
}

function get3($num) {
  get1($num);
  get1($num+1);
  get1($num+2);
}

function get3s($nums) {
  foreach ($nums as $num) {
    get3($num);
  }
}

$putinfonums =
  array(      1,     101,     201,     301,
          10001,   10101,   10201,   10301,
          20001,   20101,   20201,   20301,
          30001,   30101,   30201,   30301,
        1000001, 1000101, 1000201, 1000301,
        1010001, 1010101, 1010201, 1010301,
        1020001, 1020101, 1020201, 1020301,
        2000001, 2000101, 2000201, 2000301,
        2010001, 2010101, 2010201, 2010301,
        2020001, 2020101, 2020201, 2020301,
        3000001, 3000101, 3000201, 3000301,
        3010001, 3010101, 3010201, 3010301,
        3020001, 3020101, 3020201, 3020301);

function test_putinfo($get=TRUE) {
  global $db, $putinfonums;
               
  put3s($putinfonums);

  if (!$get) return;

  get3s($putinfonums);
}

function test_nextpostnum() {
  global $db;

  $posts = array();
  for ($i=0; $i<20; $i++) {
    $posts[$i] = $db->nextpostnum();
  }
  print_r($posts);
  //exit();
  $db->freepostnum($posts[19]);
  $db->freepostnum($posts[18]);
  $db->freepostnum($posts[3]);
  $db->freepostnum($posts[1]);
  $count = $db->getcount();
  $freelist = $db->getfreelist();
  echo "count: $count, freelist: $freelist\n";
}

function test_putemailpost() {
  global $db;

  $db->putemailpost("bill@billstclair.com", 1);
  $db->putemailpost("billstclair@gmail.com", 2);
  $db->putemailpost("wws@clozure.com", 3);
  echo $db->getemailpost("bill@billstclair.com") . ', ' .
    $db->getemailpost("billstclair@gmail.com") . ', ' .
    $db->getemailpost("wws@clozure.com") . "\n";
}

function test_infomapper() {
  global $db;

  test_putinfo(FALSE);
  $m = $db->infomapper();
  while (!$m->isempty()) {
    $next = $m->next();
    echo "$next\n";
  }
}

function test1map($m, $num) {
  $val = $m->next();
  if ($num != $val) echo "$num != $val\n";
  //else echo "$num\n";
}

function test_infomapper_start($idx) {
  global $db, $putinfonums;

  $start = $putinfonums[$idx];
  echo "start: $start\n";
  $nums = array_slice($putinfonums, $idx);

  $m = $db->infomapper($start);
  //print_r($m);
  foreach ($nums as $num) {
    test1map($m, $num);
    test1map($m, $num+1);
    test1map($m, $num+2);
  }
  if (!$m->isempty()) {
    print_r($m);
    echo "Not empty\n";
  }
}

test_putinfo();
test_infomapper_start(0);
test_infomapper_start(10);
test_infomapper_start(20);

/*
$start = $putinfonums[10];
echo "start: $start\n";
$m = $db->infomapper($start);
print_r($m);
*/