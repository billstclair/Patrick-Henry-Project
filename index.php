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

// Read-only page display
$page = mqreq('page');

// Form parameters
$cmd = mqreq('cmd');
$youtube = mqreq('youtube');
$name = mqreq('name');
$email = mqreq('email');
$url = mqreq('url');
$password = mqreq('password');
$verify = mqreq('verify');
$newpass = mqreq('newpass');
$input = mqreq('input');
$time = mqreq('time');
$hash = mqreq('hash');
$submit = mqreq('submit');
$forgot = mqreq('forgot');
$changepass = mqreq('changepass');

$datadb = new fsdb($datadir);
$infodb = new fsdb($infodir);
$rand = new LoomRandom();
$cap = new Mathcap();

?>
<html>
<head>
<title>The Patrick Henry Project</title>
</head>
<body>
<div style="width: 60em;">
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
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="image" src="btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
</form>
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
  global $page, $submit;
  if ($submit) submit();
  elseif ($page == 'videos') videos();
  elseif ($page == 'post') post();
  elseif ($page == 'edit') edit();
  else require "index.inc";
}

function submit() {
?>
<p>Submission code not yet written.</p>
<?php
}

function post() {
  global $youtube, $name, $url, $email, $password, $verify;
  global $cap, $rand;

  $gen = $cap->generate(sha1($rand->urandom_bytes(20)));
  $string = $gen['string'];
  $time = $gen['time'];
  $hash = $gen['hash'];
?>
<p>
<form method='post' action='./'>
<input type='hidden' name='cmd' value='post'/>
<input type='hidden' name='time' value='<?php echo $time; ?>'>
<input type='hidden' name='hash' value='<?php echo $hash; ?>'>
<table>
<tr>
<td></td>
<td style="color: red;">Required</td>
</tr><tr>
<td>YouTube Video:</td>
<td><input type='text' name='youtube' size='40' value='<?php echo $youtube; ?>'/></td>
</tr><tr>
<td style='text-align: right;'><?php echo $string; ?> =</td>
<td><input type='text' name='input' size='2'/></td>
</tr><tr>
<td></td>
<td style="color: red;">Fill in if you want to be able to edit or delete your post</td>
</tr><tr>
<td style="text-align: right;">Email:</td>
<td><input type='text' name='email' size='40' value='<?php echo $email; ?>'/></td>
</tr><tr>
<td style="text-align: right;">Password:</td>
<td><input type='password' name='password' size='20' value='<?php echo $password; ?>'/></td>
</tr><tr>
<td style="text-align: right;">Password Again:</td>
<td><input type='password' name='verify' size='20' value='<?php echo $verify; ?>'/></td>
</tr><tr>
<td></td>
<td style="color: red;">Optional</td>
</tr><tr>
<td style="text-align: right;">Name:</td>
<td><input type='text' name='name' size='40' value='<?php echo $name; ?>'/></td>
</tr><tr>
<td style="text-align: right;">Web Site:</td>
<td><input type='text' name='url' size='40' value='<?php echo $url; ?>'/></td>
</tr><tr>
<td></td>
<td><input type='submit' name='submit' value='Submit'/></td>
</tr>
</table>
</form>
</p>
<p>
The only fields required above are the "YouTube video" field and the answer to the simple arithmetic problem. For "Youtube video", copy the address from your browser's address bar, or from YouTube's "Share" pop-up, and paste it here. It should look something like:
</p>
<p style='margin-left: 2em;'>
<code>http://www.youtube.com/watch?v=Cup9CgSjr9g</code>
</p>
<p>
or:
</p>
<p style='margin-left: 2em;'>
<code>http://youtu.be/Cup9CgSjr9g</code>
</p>
<p>
If you want to be able to edit your name and URL, or change or delete your video, you need to enter your "Email" address and a "Password". This site stores only a cryptographic hash of your email, not the email itself, so it will be impossible for us to send you emails unless you tell us your email again if you forget your password.
</p>
<p>
If you want a "Name" and/or "Web Site" to be associated with your video, enter those fields, and that will show up in your entry on the Videos pages.
</p>
<?php
}

function edit() {
  global $email, $password, $newpass, $verify;
  global $cap, $rand;

  $gen = $cap->generate(sha1($rand->urandom_bytes(20)));
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
<td style="color: red;">Required</td>
</tr><tr>
<td style="text-align: right;">Email:</td>
<td><input type='text' name='email' size='40' value='<?php echo $email; ?>'/></td>
</tr><tr>
<td></td>
<td style="color: red;">Required for "Lookup" and "Change Password"</td>
</tr><tr>
<td style="text-align: right;">Password:</td>
<td><input type='password' name='password' size='20' value='<?php echo $password; ?>'/></td>
</tr><tr>
<td></td>
<td style="color: red;">Required for "Change Password"</td>
</tr><tr>
<td style="text-align: right;">New Password:</td>
<td><input type='password' name='newpass' size='20' value='<?php echo $newpass; ?>'/></td>
</tr><tr>
<td style="text-align: right;">Again:</td>
<td><input type='password' name='verify' size='20' value='<?php echo $verify; ?>'/></td>
</tr><tr>
<td></td>
<td style="color: red;">Required for "Forgot Password"</td>
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
<p>Videos go here</p>
<?php
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
