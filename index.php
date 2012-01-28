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
$submit = mqreq('submit');
$forgot = mqreq('forgot');

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
?>
<p>
<form method='post' action='./'>
<input type='hidden' name='cmd' value='post'/>
<table>
<tr>
<td>YouTube Video:</td>
<td><input type='text' name='youtube' size='40' value='<?php echo $youtube; ?>'/></td>
</tr><tr>
<td>Email:</td>
<td><input type='text' name='email' size='40' value='<?php echo $email; ?>'/></td>
</tr><tr>
<td>Password:</td>
<td><input type='password' name='password' size='20' value='<?php echo $password; ?>'/></td>
</tr><tr>
<td>Password Again:</td>
<td><input type='password' name='verify' size='20' value='<?php echo $verify; ?>'/></td>
</tr><tr>
<td>Name:</td>
<td><input type='text' name='name' size='40' value='<?php echo $name; ?>'/></td>
</tr><tr>
<td>Web Site:</td>
<td><input type='text' name='url' size='40' value='<?php echo $url; ?>'/></td>
</tr><tr>
<td></td>
<td><input type='submit' name='submit' value='Submit'/></td>
</tr>
</table>
</p>
<p>
The only field required above is the "YouTube video" field. Copy the address from your browser's address bar, or from YouTube's "Share" pop-up, and paste it here. It should look something like:
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
   global $email, $password;
?>
<p>
<table>
<form method='post' action='./'>
<input type='hidden' name='cmd' value='edit'/>
<tr>
<td>Email:</td>
<td><input type='text' name='email' size='40' value='<?php echo $email; ?>'/></td>
</tr><tr>
<td>Password:</td>
<td><input type='password' name='password' size='20' value='<?php echo $password; ?>'/></td>
</tr><tr>
<td></td>
<td>
  <input type='submit' name='submit' value='Lookup'/>
  <input type='submit' name='forgot' value='Forgot Password'/>
</table>
</p>
<p>
To look up your video, type your "Email" address and your "Password", and click the "Lookup" button. If you've forgotten your password, enter your "Email" address, click the "Forgot Password" button, and a link will be sent to you allowing you to enter a new password.
</p>
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
