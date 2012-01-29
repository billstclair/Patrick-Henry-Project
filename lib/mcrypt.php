<?php

  // mcrypt.php - simple encrypt and decrypt functions

class mcrypt {
  var $td;

  function mcrypt() {
  }

  function td() {
    if (!$this->td) {
      $this->td = mcrypt_module_open('rijndael-256', '', 'ofb', '');
    }
    return $this->td;
  }

  function encrypt($message, $key) {
    $td = $this->td();
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
    $ks = mcrypt_enc_get_key_size($td);
    $key = substr(sha1($key), 0, $ks);
    mcrypt_generic_init($td, $key, $iv);
    $res = mcrypt_generic($td, $message);
    mcrypt_generic_deinit($td);
    return base64_encode($iv . $res);
  }

  function decrypt($ciphertext, $key) {
    $td = $this->td();
    $ivsize = mcrypt_enc_get_iv_size($td);
    $ciphertext = base64_decode($ciphertext);
    $iv = substr($ciphertext, 0, $ivsize);
    $ciphertext = substr($ciphertext, $ivsize);
    $ks = mcrypt_enc_get_key_size($td);
    $key = substr(sha1($key), 0, $ks);
    mcrypt_generic_init($td, $key, $iv);
    $res = mdecrypt_generic($td, $ciphertext);
    mcrypt_generic_deinit($td);
    return $res;
  }

  function close() {
    if ($this->td) {
      mcrypt_module_close($this->td);
      $this->td = null;
    }
  }
}
 
// Test code
/*

$plaintext = "four score and seven years ago";
$key = "the best password ever!";
$mc = new mcrypt();
$ciphertext = $mc->encrypt($plaintext, $key);
$text = htmlspecialchars($ciphertext);
echo "ciphertext: $text<br/>\n";
$noncipher = $mc->decrypt($ciphertext, $key);
$text = htmlspecialchars($noncipher);
echo "noncipher: $text<br/>\n";

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
 * The Original Code is LoomClient PHP library
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
?>
