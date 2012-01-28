<?php

class Mathcap {

  function generate($scrambler) {
    $x = mt_rand(1, 9);
    $y = mt_rand(1, 9);
    $plus = mt_rand(0, 1);
    if ($plus == 0) {
      $input = $x + $y;
      $str = "$x + $y";
    } else {
      $input = $x * $y;
      $str = "$x x $y";
    }
    $time = time();
    $hash = sha1($input ^ $time ^ $scrambler);
    $res = array('string' => $str, 'input' => $input,
                 'time' => $time, 'hash' => $hash);
    // echo "<pre>"; print_r($res); echo "</pre><br>\n";
    return $res;
  }

  function verify($input, $time, $hash, $scrambler) {
    // Not sure why I have to convert from string, but I do.
    $input = (int)$input;
    $time = (int)$time;

    // echo "input: $input, time: $time, hash: $hash<br>\n";
    // echo "Calculated hash: " . sha1($input ^ $time ^ $captcha_scrambler) . "<br>\n";
    return sha1($input ^ $time ^ $scrambler) == $hash;
  }

}

// Test code
/*
$cap = new Mathcap();
$scrambler = sha1(12345);
$gen = $cap->generate($scrambler);
$input = $gen['input'];
$inputplus1 = $input + 1;
$time = $gen['time'];
$hash = $gen['hash'];
print_r($gen);
echo "verify($input, $time, $hash, $scrambler) = " .
     $cap->verify($input, $time, $hash, $scrambler) . "\n";
echo "verify($inputplus1, $time, $hash, $scrambler) = " .
     $cap->verify($inputplus1, $time, $hash, $scrambler) . "\n";
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
