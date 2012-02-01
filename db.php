<?php

  // Implement the "database" documented in db.txt

require_once "settings.php";
require_once "lib.php";

class db {

  var $datadb = NULL;
  var $infodb = NULL;
  var $emaildb = NULL;
  var $rand = NULL;

  function db() {
    global $datadir;
    global $infodir;
    global $emaildir;
    $this->datadb = new FSDB($datadir);
    $this->infodb = new FSDB($infodir);
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

  function getinfo($postnum) {
    $split = $this->split($postnum, 0);
  }

}

// Test code
/*

$db = new db();
$res = $db->split("x0x1x2x3", 0);
$res2 = $db->split("x1x2x3x4", 2);
print_r($res);
print_r($res2);

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
