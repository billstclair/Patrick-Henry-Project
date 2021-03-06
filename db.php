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
  var $scrambler = NULL;

  function db() {
    global $datadir, $infodir, $modinfodir, $emaildir;
    $this->datadb = new FSDB($datadir);
    $this->infodb = new FSDB($infodir);
    $this->modinfodb = new FSDB($modinfodir);
    $this->emaildb = new FSDB($emaildir);
    $this->rand = new LoomRandom();
  }

  function getscrambler() {
    global $scramblerkey;

    $res = $this->scrambler;
    if ($res) return $res;

    $datadb = $this->datadb;
    $res = $datadb->get($scramblerkey);
    if (!$res) {
      $res = sha1($this->rand->urandom_bytes(20));
      $datadb->put($scramblerkey, $res);
    }
    $this->scrambler = $res;
    return $res;
  }

  function infopath($str, $levels=0, $finalprefix='f') {
    if ($str == 0 && $levels==0) return "0/$finalprefix"."00";
    $split = splitinfopath($str, $levels);
    $path = "";
    $cnt = count($split);
    for ($i=0; $i<$cnt-1; $i++) {
      $path .= $split[$i] . '/';
    }
    $path .= $finalprefix . $split[$cnt-1];
    if ($levels == 0) $path = $path ? "$cnt/$path" : $cnt;
    return $path;
  }

  function getinfo_internal($fsdb, $postnum) {
    splitpostnum($postnum, $prefix, $idx);
    $path = $this->infopath($prefix);
    $str = $fsdb->get($path);
    if (!$str) return NULL;
    $arr = unserialize($str);
    return @$arr[$idx];
  }

  
  function getinfo($postnum) {
    return $this->getinfo_internal($this->infodb, $postnum);
  }

  function getmodinfo($postnum) {
    return $this->getinfo_internal($this->modinfodb, $postnum);
  }

  function putinfo_internal($fsdb, $postnum, $info) {
    splitpostnum($postnum, $prefix, $idx);
    $path = $this->infopath($prefix);
    $lock = $fsdb->lock($path, true);
    $str = $fsdb->get($path);
    $arr = $str ? unserialize($str) : array();
    if ($info) $arr[$idx] = $info;
    else unset($arr[$idx]);
    if (count($arr) > 0) {
      ksort($arr);
      $str = serialize($arr);
    } else {
      $str = '';
    }
    $fsdb->put($path, '');    // Work around fsdb bug
    if ($str) $fsdb->put($path, $str);
    if ($lock) $fsdb->unlock($lock);
  }

  function putinfo($postnum, $info) {
    $this->putinfo_internal($this->infodb, $postnum, $info);
  }

  function putmodinfo($postnum, $info) {
    $this->putinfo_internal($this->modinfodb, $postnum, $info);
  }

  function infomapper($start=FALSE) {
    return new infomapper($this->infodb, $start);
  }

  function modinfomapper($start=FALSE) {
    return new infomapper($this->modinfodb, $start);
  }

  function getemailpost($email) {
    $emaildb = $this->emaildb;
    $hash = sha1($email);
    $path = $this->infopath($hash, 2, '');
    return $emaildb->get($path);
  }

  function putemailpost($email, $postnum) {
    $this->putemailhashpost(sha1($email), $postnum);
  }

  function putemailhashpost($hash, $postnum) {
    $emaildb = $this->emaildb;
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

  function getcountlock() {
    global $countkey;

    $datadb = $this->datadb;
    return $datadb->lock($countkey, true);
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
    $datadb->put($freelistkey, '');
    $datadb->put($freelistkey, $freelist);
  }

  function nextpostnum() {
    $lock = $this->getcountlock();
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
    if ($lock) $this->datadb->unlock($lock);
    return $res;
  }

  function freepostnum($postnum) {
    $lock = $this->getcountlock();
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
    if ($lock) $this->datadb->unlock($lock);
  }

  function passwordhash($password, &$salt) {
    $salt = sha1($this->rand->urandom_bytes(20));
    return sha1(sha1($password) ^ $salt);
  }

  function verify_password($password, $salt, $passwordhash) {
    return $passwordhash == sha1(sha1($password) ^ $salt);
  }

  function verify_post_password($postnum, $password) {
    $info = $this->getinfo($postnum);
    if (!$info) $info = $this->getmodinfo($postnum);
    if (!$info) return false;
    return $this->verify_password($password, $info['salt'], $info['passwordhash']);
  }

  function verify_email_password($email, $password) {
    $postnum = $this->getemailpost($email);
    if (!$postnum) return FALSE;
    return $this->verify_post_password($postnum, $password);
  }

}

class infomapper {
  var $fsdb;
  var $dirstack = array();
  var $contentsliststack = array();
  var $dir = '';
  var $contentslist = null;
  var $contents = array();

  function infomapper($fsdb, $start=FALSE) {
    $this->fsdb = $fsdb;
    if ($start) $this->initstart($start);
    else {
      $contents = $fsdb->contents('');
      $this->contentslist = $contents;
    }
  }

  // This function made me wonder for a minute why I'm not just using MySQL.
  // But then I remembered the joys of administering a MySQL db, and
  // the problems I've always had with the server getting overloaded.
  function initstart($start) {
    $fsdb = $this->fsdb;
    $dirstack = array();
    $contentsliststack = array();
    splitpostnum($start, $prefix, $idx);
    $path = splitinfopath($prefix);
    $lendir = $prefix ? count($path) : 0;
    $path = array_merge(array($lendir), $path);
    if (!$prefix) $path = array_merge($path, array('00'));
    $dir = '';
    $pathlen = count($path);
    $maxi = $pathlen-1;
    $contentslist = array();
    $contents = array();
    for ($i=0; $i<$pathlen; $i++) {
      $pathelt = $path[$i];
      $contentslist = $fsdb->contents($dir);
      if ($i == $maxi) {
        $pathelt = 'f'.$pathelt;
        $len = count($contentslist);
        for ($j=0; $j<$len; $j++) {
          $elt = $contentslist[$j];
          //echo "pathelt: $pathelt, elt: $elt\n";
          if ($pathelt <= $elt) {
            $contentslist = array_slice($contentslist, $j+1);
            //echo "contentslist 2:\n"; print_r($contentslist);
            if ($pathelt == $elt) {
              $path = ($dir === '') ? $pathelt : "$dir/$pathelt";
              $contents = unserialize($fsdb->get($path));
              $k = 0;
              foreach ($contents as $key => $value) {
                //echo "idx: $idx, key: $key\n";
                if ($idx <= $key) break;
                $k++;
              }
              $contents = array_slice($contents, $k);
            } else {
              $contents = array();
            }
            //echo "contentslist:\n"; print_r($contentslist);
            break 2;
          }
        }
      } else {
        $len = count($contentslist);
        for ($j=0; $j<$len; $j++) {
          $elt = $contentslist[$j];
          //echo "pathelt 2: $pathelt, elt: $elt\n";
          if ($pathelt <= $elt) {
            $contentslist = array_slice($contentslist, $j+1);
            if ($pathelt == $elt) {
              if (count($contentslist) > 0) {
                $dirstack[] = $dir;
                $contentsliststack[] = $contentslist;
              }
              if (!($dir === '')) $dir .= '/';
              $dir .= $elt;
              break;
            } else {
              $contentslist = $contentslist;
              $contents = array();
              break 2;
            }
          }
        }
      }
    }
    if (count($contentslist) == 0) {
      if (count($dirstack) > 0) {
        $dir = array_pop($dirstack);
        $contentslist = array_pop($contentsliststack);
      }
    }
    $this->dirstack = $dirstack;
    $this->contentsliststack = $contentsliststack;
    $this->dir = $dir;
    $this->contentslist = $contentslist;
    $this->contents = $contents;
  }

  function findex($contents) {
    $len = count($contents);
    for ($i=0; $i<$len; $i++) {
      $name = $contents[$i];
      if (substr($name, 0, 1) == 'f') return $i;
    }
    return FALSE;
  }

  function isempty() {
    return (count($this->contents)==0) && (count($this->contentslist)==0);
  }

  function next() {
    while (true) {
      $res = $this->next_internal();
      if ($res && (!is_array($res) || count($res) > 0)) return $res;
      if ($this->isempty()) return NULL;
    }
  }

  function next_internal() {
    $fsdb = $this->fsdb;
    while (count($this->contents) > 0) return array_shift($this->contents);
    while (true) {
      if ($this->isempty()) return null;
      //print_r($this);
      $next = array_shift($this->contentslist);
      $key = $this->dir;
      if (!($key === '')) $key .= '/';
      $key .= $next;
      if (substr($next, 0, 1) == 'f') {
        $res = $fsdb->get($key);
        //echo "key: $key, res: $res\n";
        if ($this->isempty()) {
          if (count($this->dirstack) > 0) {
            $this->dir = array_pop($this->dirstack);
            $this->contentslist = array_pop($this->contentsliststack);
          }
        }
        $contents = unserialize($res);
        if (!is_array($contents)) return $contents;
        $res = array_shift($contents);
        $this->contents = $contents;
        return $res;
      }
      $contentslist = $fsdb->contents($key);
      if (count($contentslist) == 0) {
        if (count($this->contentslist) == 0) {
          if (count($this->dirstack) == 0) return null;
          $this->dir = array_pop($this->dirstack);
          $this->contentslist = array_pop($this->contentsliststack);
        }
        next;
      }
      if (count($this->contentslist) > 0) {
        $this->dirstack[] = $this->dir;
        $this->contentsliststack[] = $this->contentslist;
      }
      $this->dir = $key;
      $this->contentslist = $contentslist;
    }
  }
}

function splitinfopath($string, $levels=0) {
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

function splitpostnum($postnum, &$start, &$end) {
  $postnum = "$postnum";
  $len = strlen($postnum);
  if ($len < 2) {
    $start = '';
    if (!$postnum) $postnum = '00';
    if (strlen($postnum) == 1) $postnum = "0$postnum";
    $end = $postnum;
  } else {
    $start = substr($postnum, 0, $len-2);
    $end = substr($postnum, $len-2);
  }
}



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
