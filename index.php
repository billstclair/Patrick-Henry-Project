<?php
require_once "settings.php";
require_once "lib.php";

function mq($x) {
  if (get_magic_quotes_gpc()) return stripslashes($x);
  else return $x;
}

function mqreq($x) {
  return mq(@$_REQUEST[$x]);
}

function hsc($x) {
  return htmlspecialchars($x);
}

// During development
$default_error = "Play as you will, but posts are not yet persistent";

// Read-only page display
$page = mqreq('page');

// Form parameters
$cmd = mqreq('cmd');
$youtube = mqreq('youtube');
$video = '';
$name = mqreq('name');
$email = mqreq('email');
$url = mqreq('url');
$password = mqreq('password');
$verify = mqreq('verify');
$newpass = mqreq('newpass');
$string = mqreq('string');
$input = mqreq('input');
$time = mqreq('time');
$hash = mqreq('hash');
$submit = mqreq('submit');
$edit = mqreq('edit');
$forgot = mqreq('forgot');
$changepass = mqreq('changepass');

$datadb = new fsdb($datadir);
$infodb = new fsdb($infodir);
$rand = new LoomRandom();
$cap = new Mathcap();

// True to not generate a new captcha
$keepcap = FALSE;

?>
<html>
<head>
<title>The Patrick Henry Project</title>
</head>
<body background='background.png'>
<div style="width: 60em; margin: 4em auto 4em auto; border: 1px solid blue; padding: 1em; background-color: white;">
<p style="text-align: center; font-weight: bold; font-size: 125%;">
The Patrick Henry Project</p>
<table>
<tr>
<td valign="top">
<div>
<?php left_column(); ?>
</div>
</td>
<td valign="top">
<div style="margin-left: 2em;">
<?php content(); ?>
</div>
</td>
</tr>
</table>
<p style="text-align:center; font-size: 80%;">
Copyright &copy 2012 Bill St. Clair &lt;bill at billstclair dot com>
</p>
<div style="text-align: center">
<p style="font-size: 80%">
Donations support this site and <a href="http://freedomoutlaws.com/">FreedomOutlaws.com</a>
</p>
<?php require "paypal.inc"; ?>
</div>
</div>
</html>
<?php
function left_column() {
?>
<p>
<a href="./">Home</a><br/>
<a href="./?page=post">Post&nbsp;Your&nbsp;Video</a><br/>
<a href="./?page=edit">Edit&nbsp;Your&nbsp;Video</a><br/>
<a href="./?page=videos">Videos</a><br/>
</p>
<?php
}

function content() {
  global $page, $cmd;
  //  echo "<pre>"; print_r($_REQUEST); echo "</pre>\n";
  if ($cmd == 'post') dopost();
  elseif ($cmd == 'finishpost') finishpost();
  elseif ($cmd == 'edit') doedit();
  elseif ($page == 'videos') videos();
  elseif ($page == 'post') post();
  elseif ($page == 'edit') edit();
  else require "index.inc";
}

function dopost() {
   global $youtube, $video, $name, $email, $url, $password, $verify;
   global $input, $time, $hash;
   global $datadb, $infodb, $cap, $keepcap;

   // Validate captcha
   $keepcap = FALSE;
   if (!$cap->verify($input, $time, $hash, getscrambler())) {
     return post('Wrong answer to simple arithmetic problem');
   }
   $keepcap = TRUE;

   // validate YouTube URL
   $yt = parse_url($youtube);
   $host = @$yt['host'];
   if ($host=='youtube.com' || $host=='www.youtube.com') {
     $query = $yt['query'];
     $pos = strpos($query, 'v=');
     if ($pos==0 && !($pos===FALSE)) $video = substr($query, 2);
     else {
       $pos = strpos($query, '&v=');
       if ($pos === FALSE) return post('Malformed URL');
       $video = substr($query, $pos+3);
     }
     $pos = strpos($video, '&');
     if (!($pos === false)) $video = substr($video, 0, $pos);
   } elseif ($host == 'youtu.be') {
     $video = @$yt['path'];
     if (strlen($video) > 0) $video = substr($video, 1);
   } else {
     return post('Malformed URL');
   }

   // Validate email and password
   if (!$email) return post('Email address is required');
   if (!$password) return post('Password is required');
   if ($password != $verify) return post('Passwords do not match');

   if ($url && strpos($url, "http://")===FALSE && strpos($url, "https://")===FALSE) {
     $url = "http://$url";      // Probably right, but make user verify
     return post('Prefixed Web Site with "http://". Verify and resubmit.');
   }

   displayPost();
}

function displayPost() {
   global $youtube, $video, $name, $email, $url, $password, $verify;
   global $string, $input, $time, $hash;

?>
<center>
<p>
<iframe width="560" height="315" src="<?php echo "http://www.youtube.com/embed/$video"; ?>" frameborder="0" allowfullscreen></iframe></p>
<a href="<?php echo "http://youtu.be/$video"; ?>">
<?php echo "youtu.be/$video"; ?></a><br/>
<?php if ($url) {
  echo "<a href='$url'>";
  if ($name) echo hsc($name);
  else echo hsc($url);
  echo "</a>\n";
} elseif ($name) echo hsc($name);
?>
<form method='post' action='./'>
<input type='hidden' name='cmd' value='finishpost'/>
<input type='hidden' name='string' value='<?php echo $string; ?>'/>
<input type='hidden' name='input' value='<?php echo $input; ?>'/>
<input type='hidden' name='time' value='<?php echo $time; ?>'/>
<input type='hidden' name='hash' value='<?php echo $hash; ?>'/>
<input type='hidden' name='youtube' value='<?php echo hsc($youtube); ?>'/>
<input type='hidden' name='video' value='<?php echo hsc($video); ?>'/>
<input type='hidden' name='email' value='<?php echo hsc($email); ?>'/>
<input type='hidden' name='password' value='<?php echo hsc($password); ?>'/>
<input type='hidden' name='name' value='<?php echo hsc($name); ?>'/>
<input type='hidden' name='url' value='<?php echo hsc($url); ?>'/>
<br/>
<input type='submit' name='submit' value='Post'/>
<input type='submit' name='edit' value='Edit'/>
</form>
</center>
<p>
Click the "Post" button to verify your video and information. Click on the "Edit" button to change something before posting.
</p>
<?php
}

function finishpost() {
   global $youtube, $video, $email, $password, $verify, $name, $url;
   global $keepcap, $string, $input, $time, $hash;
   global $submit, $edit;

   if ($edit) {
     $keepcap = TRUE;
     $verify = $password;
     post();
   } else {
     $reginfo = array('video' => $video,
                      'email' => $email,
                      'password' => $password);
     if ($name) $reginfo['name'] = $name;
     if ($url) $reginfo['url'] = $url;
   }
}

function doedit() {
?>
<p>Editing videos doesn't work yet.</p>
<?php
}

function post($error=null) {
  global $youtube, $name, $url, $email, $password, $verify;
  global $cap, $keepcap, $rand, $default_error;

  if (!$error) $error = $default_error;

  $gen = gencap();
  $string = $gen['string'];
  $time = $gen['time'];
  $hash = $gen['hash'];
  $input = '';
  if ($keepcap) $input = $gen['input'];
?>
<p>
Use this form to submit or change your video. Videos should be recitations of Patrick Henry's speech, ending with "Give me liberty or give me death!" If you want to use just the end of the speech, instead of the whole thing, that's OK, but videos of anything other than the speech will not be approved.
</p>
<p>
This site saves only a cryptographic hash of your email address. That allows us to recognize your email when you type it again, but does not allow anybody, even the site adminstrators, to get your email address. It IS possible for somebody to check if your email address is in the database, but to do that, they have to know it. You will receive a confirmation email from the site. If that's a problem for you, don't post a video here.
</p>
<p>
<form method='post' action='./'>
<input type='hidden' name='cmd' value='post'/>
<input type='hidden' name='string' value='<?php echo $string; ?>'/>
<input type='hidden' name='time' value='<?php echo $time; ?>'/>
<input type='hidden' name='hash' value='<?php echo $hash; ?>'/>
<table>
<tr>
<td></td>
<td><span style='color: red;'><?php echo $error; ?></span></td>
</tr><tr>
<td></td>
<td style="color: blue;">Required</td>
</tr><tr>
<td>YouTube Video:</td>
<td><input type='text' name='youtube' id='youtube' size='40' value='<?php echo $youtube; ?>'/></td>
</tr><tr>
<td style='text-align: right;'><?php echo $string; ?> =</td>
<td><input type='text' name='input' size='2' value='<?php echo $input; ?>'/></td>
</tr><tr>
<td style="text-align: right;">Email:</td>
<td><input type='text' name='email' size='40' value='<?php echo $email; ?>'/></td>
</tr><tr>
<td style="text-align: right;">Password:</td>
<td><input type='password' name='password' size='20' value='<?php echo hsc($password); ?>'/></td>
</tr><tr>
<td style="text-align: right;">Password Again:</td>
<td><input type='password' name='verify' size='20' value='<?php echo hsc($verify); ?>'/></td>
</tr><tr>
<td></td>
<td style="color: blue;">Optional</td>
</tr><tr>
<td style="text-align: right;">Name:</td>
<td><input type='text' name='name' size='40' value='<?php echo hsc($name); ?>'/></td>
</tr><tr>
<td style="text-align: right;">Web Site:</td>
<td><input type='text' name='url' size='40' value='<?php echo hsc($url); ?>'/></td>
</tr><tr>
<td></td>
<td><input type='submit' name='submit' value='Submit'/></td>
</tr>
</table>
</form>
</p>
<p>
Fill in the fields in the "Required" section. For "Youtube Video", copy the address from your browser's address bar, or from YouTube's "Share" pop-up, and paste it here. It should look something like one of these two examples::
</p>
<p style='margin-left: 2em;'>
<code>http://www.youtube.com/watch?v=Cup9CgSjr9g</code><br/>
<code>http://youtu.be/Cup9CgSjr9g</code>
</p>
<p>
Enter the answer to the simple arithmetic problem. This reduces spam submissions.
</p>
<p>
Enter your "Email" address, a "Password", and the "Password Again". If this is a change to an existing entry, you can leave the "Password Again" field blank.
</p>
<p>
If you want a "Name" and/or "Web Site" to be associated with your video, enter those fields, and that will show up in your entry on the Videos pages. The "Web Site" address must begin with "http://" or "https://".
</p>
<p>
Finally, press the "Submit" button.
<?php
}

function edit() {
  global $email, $password, $newpass, $verify;
  global $cap, $rand;

  $gen = gencap();
  $string = $gen['string'];
  $time = $gen['time'];
  $hash = $gen['hash'];
?>
<form method='post' action='./'>
<input type='hidden' name='cmd' value='edit'/>
<input type='hidden' name='time' value='<?php echo $time; ?>'>
<input type='hidden' name='hash' value='<?php echo $hash; ?>'>
<p>
<table>
<tr>
<td></td>
<td style="color: blue;">Required</td>
</tr><tr>
<td style="text-align: right;">Email:</td>
<td><input type='text' name='email' size='40' value='<?php echo $email; ?>'/></td>
</tr><tr>
<td></td>
<td style="color: blue;">Required for "Lookup" and "Change Password"</td>
</tr><tr>
<td style="text-align: right;">Password:</td>
<td><input type='password' name='password' size='20' value='<?php echo hsc($password); ?>'/></td>
</tr><tr>
<td></td>
<td style="color: blue;">Required for "Change Password"</td>
</tr><tr>
<td style="text-align: right;">New Password:</td>
<td><input type='password' name='newpass' size='20' value='<?php echo hsc($newpass); ?>'/></td>
</tr><tr>
<td style="text-align: right;">Again:</td>
<td><input type='password' name='verify' size='20' value='<?php echo hsc($verify); ?>'/></td>
</tr><tr>
<td></td>
<td style="color: blue;">Required for "Forgot Password"</td>
</tr><tr>
<td style='text-align: right;'><?php echo $string; ?> =</td>
<td><input type='text' name='input' size='2'/></td>
</tr><tr>
<td></td>
<td>
  <input type='submit' name='submit' value='Lookup'/>
  <input type='submit' name='changepass' value='Change Password'/>
  <input type='submit' name='forgot' value='Forgot Password'/>
</td>
</tr>
</table>
</p>
<p>
To look up your video, enter your "Email" address and your "Password", and click the "Lookup" button. To change your password, enter your "Email" address, your old "Password", the "New Password" and the new password "Again", and click the "Change Password" button. If you've forgotten your password, enter your "Email" address and the answer to the simple arithmetic problem, click the "Forgot Password" button, and a link will be sent to you allowing you to enter a new password.
</p>
</form>
<?php
}

function videos() {
?>
<p>Videos will go here</p>
<?php
}

function gencap() {
  global $cap, $keepcap, $string, $time, $hash, $input;
  if ($keepcap) return $cap->newtime($string, $input, getscrambler());
  return $cap->generate(getscrambler());
}

function getscrambler() {
  global $scrambler_file, $datadb, $rand;
  $res = $datadb->get($scrambler_file);
  if (!$res) {
    $res = sha1($rand->urandom_bytes(20));
    $datadb->put($scrambler_file, $res);
  }
  return $res;
}

/* Getting youtube header

telnet www.youtube.com 80
HEAD /watch?v=<number> HTTP/1.0
Host: www.youtube.com

Will get a 404 if the video doesn't exist:
  HTTP/1.0 404 Not Found
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
