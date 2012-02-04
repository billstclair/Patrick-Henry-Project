<?php

// Test code for db.php

require_once "db.php";

$db = new db();

function test_putinfo() {
  global $db;

  $db->putinfo(1, "one");
  $db->putinfo(2, "two");
  $db->putinfo(3, "three");
  $db->putinfo(101, "one oh one");
  $db->putinfo(102, "one oh two");
  $db->putinfo(103, "one oh three");
  $db->putinfo(10001, "ten thousand one");
  $db->putinfo(10002, "ten thousand two");
  $db->putinfo(10003, "ten thousand three");
  $db->putinfo(20010001, "twenty million 10 thousand one");
  $db->putinfo(20010002, "twenty million 10 thousand two");
  $db->putinfo(20010003, "twenty million 10 thousand three");

  echogetinfo(1);
  echogetinfo(2);
  echogetinfo(3);
  echogetinfo(101);
  echogetinfo(102);
  echogetinfo(103);
  echogetinfo(10001);
  echogetinfo(10002);
  echogetinfo(10003);
  echogetinfo(20010001);
  echogetinfo(20010002);
  echogetinfo(20010003);
}

function echogetinfo($x) {
  global $db;
  $info = $db->getinfo($x);
  echo "$x: $info\n";
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

  test_putinfo();
  $m = new infomapper($db->infodb);
  while (!$m->isempty()) {
    $next = $m->next();
    echo "$next\n";
  }
}

test_infomapper();