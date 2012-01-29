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
