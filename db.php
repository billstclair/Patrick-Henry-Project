<?php

  // Implement the "database" documented in db.txt

require_once "settings.php";
require_once "lib.php";

class db {

  var $datadb = NULL;
  var $infodb = NULL;
  var $modinfodb = NULL;
  var $emaildb = NULL;
  var $rand = NULL;

  function db() {
    global $datadir;
    global $infodir;
    global $emaildir;
    $this->datadb = new FSDB($datadir);
    $this->infodb = new FSDB($infodir);
    $this->modinfodb = new FSDB($modinfodir);
    $this->emaildb = new FSDB($emaildir);
    $this->rand = new LoomRandom();
  }

  function getscrambler() {
    global $scramblerkey;
    $datadb = $this->datadb;
    $res = $datadb->get($scramblerkey);
    if (!$res) {
      $res = sha1($this->rand->urandom_bytes(20));
      $datadb->put($scramblerkey, $res);
    }
    return $res;
  }

  function split($string, $levels) {
    $len = strlen($string);
    if ($len % 2 == 1) {
      $string = "0$string";
      $len++;
    }
    $idx = 0;
    $res = array();
    for ($i=0; $idx<$len; $i++) {
      $sub = substr($string, $idx, 2);
      $res[$i] = $sub;
      $idx += 2;
      if ($i+1 == $levels) {
        $res[$i+1] = substr($string, $idx);
        break;
      }
    }
    return $res;
  }

  function infopath($str, $levels=0, $finalprefix='f') {
    $split = $this->split($str, $levels);
    $path = "";
    $cnt = count($split);
    for ($i=0; $i<$cnt-1; $i++) {
      $path .= $split[$i] . '/';
    }
    $path .= $finalprefix . $split[$cnt-1];
    return $path;
  }

  function splitend($postnum, &$start, &$end) {
    $postnum = $postnum . "";
    $len = strlen($postnum);
    if ($len < 2) {
      $start = '00';
      if (!$postnum) $postnum = '00';
      if (strlen($postnum) == 1) $postnum = "0$postnum";
      $end = $postnum;
    } else {
      $start = substr($postnum, 0, $len-2);
      $end = substr($postnum, $len-2);
    }
  }

  function getinfo_internal($fsdb, $postnum) {
    $this->splitend($postnum, $prefix, $idx);
    $path = $this->infopath($prefix);
    $str = $fsdb->get($path);
    $arr = unserialize($str);
    return $arr[$idx];
  }

  
  function getinfo($postnum) {
    return $this->getinfo_internal($this->infodb, $postnum);
  }

  function getmodinfo($postnum) {
    return $this->getinfo_internal($this->modinfodb, $postnum);
  }

  function putinfo_internal($fsdb, $postnum, $info) {
    $this->splitend($postnum, $prefix, $idx);
    $path = $this->infopath($prefix);
    $str = $fsdb->get($path);
    $arr = $str ? unserialize($str) : array();
    $arr[$idx] = $info;
    $str = serialize($arr);
    $fsdb->put($path, '');    // Work around fsdb bug
    $fsdb->put($path, $str);
  }

  function putinfo($postnum, $info) {
    $this->putinfo_internal($this->infodb, $postnum, $info);
  }

  function putmodinfo($postnum, $info) {
    $this->putinfo_internal($this->modinfodb, $postnum, $info);
  }

  function getemailpost($email) {
    $emaildb = $this->emaildb;
    $hash = sha1($email);
    $path = $this->infopath($hash, 2, '');
    return $emaildb->get($path);
  }

  function putemailpost($email, $postnum) {
    $emaildb = $this->emaildb;
    $hash = sha1($email);
    $path = $this->infopath($hash, 2, '');
    $emaildb->put($path, $postnum);
  }

  function getcount() {
    global $countkey;

    $datadb = $this->datadb;
    $res = $datadb->get($countkey);
    if (!$res) $res = 0;
    return $res;
  }

  function putcount($count) {
    global $countkey;

    $datadb = $this->datadb;
    // This works around a bug I don't understand.
    // When the count gets n+1 chars in size, PHP only reads the first n chars.
    $datadb->put($countkey, "");
    $datadb->put($countkey, $count);
  }

  function getfreelist() {
    global $freelistkey;

    $datadb = $this->datadb;
    return $datadb->get($freelistkey);
  }

  function putfreelist($freelist) {
    global $freelistkey;

    $datadb = $this->datadb;
    $datadb->put($freelistkey, $freelist);
  }

  function nextpostnum() {
    $freelist = $this->getfreelist();
    if ($freelist) {
      $pos = strpos($freelist, ',');
      if ($pos) {
        $res = substr($freelist, 0, $pos);
        $freelist = substr($freelist, $pos+1);
      } else {
        $res = $freelist;
        $freelist = "";
      }
      $this->putfreelist($freelist);
    } else {
      $res = $this->getcount() + 1;
      $this->putcount($res);
    }
    return $res;
  }

  function freepostnum($postnum) {
    $count = $this->getcount() + 0;
    if ($postnum == $count) {
      $this->putcount($count-1);
    } else {
      $freelist = $this->getfreelist();
      if ($freelist) $freelist .= ',';
      $freelist .= $postnum;
      $freelist = explode(',', $freelist);
      sort($freelist, SORT_NUMERIC);
      $freelist = implode($freelist, ',');
      $this->putfreelist($freelist);
    }
  }
}

class infomapper {
  var $fsdb;
  var $dirstack = array();
  var $contentliststack = array();
  var $dir = '';
  var $contentlist = null;

  function infomapper($fsdb, $start=false) {
    $this->fsdb = $fsdb;
    $contents = $fsdb->contents('');
    $this->contentlist = $this->sortcontents($contents);
  }

  function sortcontents($contents) {
    $len = count($contents);
    $fpos = false;
    for ($i=0; $i<$len; $i++) {
      $name = $contents[$i];
      if (substr($name, 0, 1) == 'f') {
        return array_merge(array_slice($contents, $i),
                           array_slice($contents, 0, $i));
      }
    }
    return $contents;
  }

  function isempty() {
    return count($this->contentlist)==0;
  }

  function next() {
    if ($this->isempty()) return null;
    $fsdb = $this->fsdb;
    while (true) {
      $next = array_shift($this->contentlist);
      $key = $this->dir;
      if ($key) $key .= '/';
      $key .= $next;
      if (substr($next, 0, 1) == 'f') {
        $res = $fsdb->get($key);
        if ($this->isempty()) {
          if (count($this->dirstack) > 0) {
            $this->dir = array_pop($this->dirstack);
            $this->contentlist = array_pop($this->contentliststack);
          }
        }
        return $res;
      }
      $contentlist = $this->sortcontents($fsdb->contents($key));
      if (count($contentlist) == 0) {
        if (count($this->contentlist) == 0) {
          if (count($this->dirstack) == 0) return null;
          $this->dir = array_pop($this->dirstack);
          $this->contentlist = array_pop($this->contentliststack);
        }
        next;
      }
      if (count($this->contentlist) > 0) {
        $this->dirstack[] = $this->$dir;
        $this->contentliststack[] = $this->contentlist;
        $this->dir = $key;
        $this->contentlist = $contentlist;
      }
    }
  }
}

// Test code

/*
$db = new db();

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

function getit($x) {
  global $db;
  $info = $db->getinfo($x);
  echo "$x: $info\n";
}

getit(1);
getit(2);
getit(3);
getit(101);
getit(102);
getit(103);
getit(10001);
getit(10002);
getit(10003);
getit(20010001);
getit(20010002);
getit(20010003);

*/

/*

$db = new db();

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

*/

/*
$db = new db();

$db->putemailpost("bill@billstclair.com", 1);
$db->putemailpost("billstclair@gmail.com", 2);
$db->putemailpost("wws@clozure.com", 3);
echo $db->getemailpost("bill@billstclair.com") . ', ' .
$db->getemailpost("billstclair@gmail.com") . ', ' .
$db->getemailpost("wws@clozure.com") . "\n";
*/

/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1/Apache 2.0
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is The Patrick Henry Project, patrickhenryproject.org
 *
 * The Initial Developer of the Original Code is
 * Bill St. Clair.
 * Portions created by the Initial Developer are Copyright (C) 2012
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Bill St. Clair <bill@billstclair.com>
 *
 * Alternatively, the contents of this file may be used under the
 * terms of the GNU General Public License Version 2 or later (the
 * "GPL"), the GNU Lesser General Public License Version 2.1 or later
 * (the "LGPL"), or The Apache License Version 2.0 (the "AL"), in
 * which case the provisions of the GPL, LGPL, or AL are applicable
 * instead of those above. If you wish to allow use of your version of
 * this file only under the terms of the GPL, the LGPL, or the AL, and
 * not to allow others to use your version of this file under the
 * terms of the MPL, indicate your decision by deleting the provisions
 * above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the
 * provisions above, a recipient may use your version of this file
 * under the terms of any one of the MPL, the GPL the LGPL, or the AL.
 ****** END LICENSE BLOCK ***** */
