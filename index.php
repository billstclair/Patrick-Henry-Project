<?php

  // Home page for patrickhenryproject.org

require_once "settings.php";
require_once "lib.php";
require_once "db.php";

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
$default_error = ''; //"Play as you will, but posts are not yet persistent";

// Read-only page display
$page = mqreq('page');

// Form parameters
$cmd = mqreq('cmd');
$postnum = mqreq('postnum');
$youtube = mqreq('youtube');
$video = mqreq('video');
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
$info = mqreq('info');

// Submission buttons
$submit = mqreq('submit');
$edit = mqreq('edit');
$forgot = mqreq('forgot');
$changepass = mqreq('changepass');
$delete = mqreq('delete');
$cancel = mqreq('cancel');

$db = new db();
$cap = new Mathcap();
$mcrypt = new mcrypt();

// True to not generate a new captcha
$keepcap = FALSE;

// The id of the field to focus
$onloadid = NULL;

// True if the viewed video is awaiting moderation
$modp = FALSE;

// Sessions are used for the administrator
session_start();
$adminpost = @$_SESSION['adminpost'];
$newadmin = FALSE;
if ($adminpost) {
  if ($page == 'logout') {
    kill_session();
    $adminpost = NULL;
  }
} else {
  // It's a little more time to check for admin here,
  // but it gets the logout item up right away, and it
  // only happens on email/password pages
  if ($email && $password) checkadmin($email, $password);
}

function checkadmin($email, $password) {
  global $db, $adminpost, $admin_email, $newadmin;
  $isadmin = ($email == $admin_email);
  $postnum = $db->getemailpost($email);
  if ($postnum) {
    $info = getinfo($postnum, $ignore);
    if ($info) {
      if ($db->verify_password($password, $info['salt'], $info['passwordhash'])) {
        if (!$isadmin) $isadmin = @$info['adminp'];
        if ($isadmin) {
          $adminpost = $postnum;
          $newadmin = TRUE;
          $_SESSION['adminpost'] = @$info['postnum'];
        }
      }
    }
  }
}

?>
<html>
  <head>
    <title>The Patrick Henry Project</title>
    <script type="text/javascript">
      var onloadid = null;
      function doonload() {
        if (onloadid) document.getElementById(onloadid).focus();
      }
    </script>
  </head>
  <body background='background.png' onload='doonload();'>
    <div style="width: 60em; margin: 4em auto 4em auto; border: 1px solid blue; padding: 1em; background-color: white;">
      <p style="text-align: center; font-weight: bold; font-size: 125%;">
        The Patrick Henry Project</p>
      <table style="width: 100%">
        <tr>
          <td valign="top">
            <div>
<?php left_column(); ?>
            </div>
          </td>
          <td valign="top">
            <div style="margin-left: 2em; margin-right: 2em;">
<?php content(); ?>
            </div>
          </td>
        </tr>
      </table>
      <p style="text-align:center; font-size: 80%;">
        Copyright &copy 2012 Bill St. Clair &lt;bill at billstclair dot com>
      </p>
      <div style="text-align: center;">
        <p style="font-size: 80%">
          Donations support this site and <a href="http://freedomoutlaws.com/">FreedomOutlaws.com</a>
        </p>
<?php require "paypal.inc"; ?>
      </div>
    </div>
<?php if ($onloadid) {
?>
    <script type="text/javascript">
      onloadid = '<?php echo $onloadid; ?>';
    </script>
<?php
}
?>
  </body>
</html>
<?php

function left_column() {
  global $adminpost;
?>
              <p>
                <a href="./">Home</a><br/>
                <a href="./?page=post">Post&nbsp;Your&nbsp;Video</a><br/>
                <a href="./?page=edit">Edit&nbsp;Your&nbsp;Video</a><br/>
                <a href="./?page=videos">Videos</a><br/>
<?php
  if ($adminpost) {
?>
                <a href="./?page=moderate">Moderate</a><br/>
                <a href="./?page=logout">Admin Logout</a><br/>
<?php
  }
?>
              </p>
<?php
}

function content() {
  global $page, $cmd, $postnum;
  //  echo "<pre>"; print_r($_REQUEST); echo "</pre>\n";
  if ($cmd == 'post') dopost();
  elseif ($cmd == 'finishpost') finishpost();
  elseif ($cmd == 'register') register();
  elseif ($cmd == 'edit') doedit();
  elseif ($cmd == 'delete') dodelete();
  elseif ($cmd == 'forgot') forgot(TRUE);
  elseif ($page == 'view') view();
  elseif ($page == 'videos') videos();
  elseif ($page == 'post') post();
  elseif ($page == 'edit') edit();
  elseif ($page == 'forgot') forgot();
  elseif ($page == 'logout') notelogout();
  else require "index.inc";
}

function view($post=NULL) {
  global $postnum, $db;
  global $video, $name, $url, $modp;

  if (!$post) $post = $postnum;

  $info = getinfo($post, $modp);

  if (!$info) {
    echo "<span style='color: red; font-weight: bold;'>Post not found:</span> $post";
    return;
  }

  $video = $info['video'];
  $name = @$info['name'];
  $url = @$info['url'];

  displayPost();
}

function dopost() {
   global $youtube, $video, $name, $email, $url, $password, $verify;
   global $input, $time, $hash;
   global $cap, $keepcap, $onloadid;
   global $db, $postnum;

   // Validate captcha
   if (!$postnum) {
     $keepcap = FALSE;
     if (!$cap->verify($input, $time, $hash, getscrambler())) {
       $onloadid = 'input';
       return post('Wrong answer to simple arithmetic problem');
     }
     $keepcap = TRUE;
   }

   // validate email
   $post = $db->getemailpost($email);
   if ($post) {
     if (!$db->verify_post_password($post, $password)) {
       return post("Invalid email or password");
     }
     $postnum = $post;
   }

   // validate YouTube URL
   $yt = parse_url($youtube);
   $host = @$yt['host'];
   if ($host=='youtube.com' || $host=='www.youtube.com') {
     $query = $yt['query'];
     $pos = strpos($query, 'v=');
     if ($pos==0 && !($pos===FALSE)) $video = substr($query, 2);
     else {
       $pos = strpos($query, '&v=');
       if ($pos === FALSE) {
         $onloadid = 'youtube';
         return post('Malformed Youtube Video link');
       }
       $video = substr($query, $pos+3);
     }
     $pos = strpos($video, '&');
     if (!($pos === false)) $video = substr($video, 0, $pos);
   } elseif ($host == 'youtu.be') {
     $video = @$yt['path'];
     if (strlen($video) > 0) $video = substr($video, 1);
   } else {
     $onloadid = 'youtube';
     return post('Malformed Youtube Video link');
   }

   // Validate email and password
   if (!$email) {
     $onloadid = 'email';
     return post('Email address is required');
   }
   if (!$password) {
     $onloadid = 'password';
     return post('Password is required');
   }
   if (!$postnum && $password != $verify) {
     $onloadid = 'password';
     return post('Passwords do not match');
   }

   if ($url && strpos($url, "http://")===FALSE && strpos($url, "https://")===FALSE) {
     $url = "http://$url";      // Probably right, but make user verify
     $onloadid = 'url';
     return post('Prefixed Web Site with "http://". Verify and resubmit.');
   }

   displayPost();
}

function displayPost($newpostp=FALSE) {
   global $youtube, $video, $name, $email, $url, $password, $verify;
   global $string, $input, $time, $hash;
   global $postnum, $modp;

?>
              <div style="text-align: center; margin-left: auto; margin-right: auto; width: 560px;">
<?php
  if ($modp) {
?>
                <p style="color: red;">This video is awaiting moderation</p>
<?php
  } else {
?>
                <p>
                  <iframe width="560" height="315" src="<?php echo "http://www.youtube.com/embed/$video"; ?>" frameborder="0" allowfullscreen></iframe>
                </p>
<?php
  }
?>
                <a href="<?php echo "http://youtu.be/$video"; ?>"><?php echo "youtu.be/$video"; ?></a>
                <br/>
                <?php if ($url) {
  echo "<a href='$url'>";
  if ($name) echo hsc($name);
  else echo hsc($url);
  echo "</a>\n";
} elseif ($name) echo hsc($name);
?>
                <form method='post' action='./'>
<?php
  if ($email && $password) {
?>
                  <input type='hidden' name='cmd' value='finishpost'/>
<?php
  if ($hash) {
?>
                  <input type='hidden' name='string' value='<?php echo $string; ?>'/>
                  <input type='hidden' name='input' value='<?php echo $input; ?>'/>
                  <input type='hidden' name='time' value='<?php echo $time; ?>'/>
                  <input type='hidden' name='hash' value='<?php echo $hash; ?>'/>
<?php
  }
?>
                  <input type='hidden' name='youtube' value='<?php echo hsc($youtube); ?>'/>
                  <input type='hidden' name='email' value='<?php echo hsc($email); ?>'/>
                  <input type='hidden' name='password' value='<?php echo hsc($password); ?>'/>
<?php
  } else {
?>
                  <input type='hidden' name='page' value='post'/>
<?php
  }
  if ($postnum) {
?>
                  <input type='hidden' name='postnum' value='<?php echo $postnum; ?>'/>
<?php
  }
?>
                  <input type='hidden' name='video' value='<?php echo hsc($video); ?>'/>
                  <input type='hidden' name='name' value='<?php echo hsc($name); ?>'/>
                  <input type='hidden' name='url' value='<?php echo hsc($url); ?>'/>
                  <br/>
<?php
  if ($email && $password) {
?>
                  <input type='submit' name='submit' value='Post'/>
<?php
  }
?>
                  <input type='submit' name='edit' value='Edit'/>
                </form>
              </div>
<?php
  if ($email && $password) {
?>
              <p>
                Click the "Post" button to verify your video and information. Click on the "Edit" button to change something before posting.
              </p>
<?php
  } elseif ($newpostp) {
?>
              <p>
                Your video information has been submitted for moderation. It will appear in the list of videos after approval.
              </p>
              </p>
                <a href='./?page=view&postnum=<?php echo $postnum; ?>'>Click here</a> for your video's permanent page.</a>
              </p>
<?php
  } else {
?>
              <p>
                Click the edit button to edit this video.
              </p>
<?php
  }
}

function finishpost() {
   global $youtube, $video, $email, $password, $verify, $name, $url;
   global $keepcap, $string, $input, $time, $hash;
   global $submit, $edit, $cap, $mcrypt;
   global $db, $postnum;

   // if the email already exists, and the password verifies,
   // post a change for moderation
   $postnum = $db->getemailpost($email);
   if ($postnum) {
     if (!$db->verify_post_password($postnum, $password)) {
       return post('Invalid email or password');
     }
     if (!$edit) {
       putmodinfo($postnum, $video, $email, $password, $name, $url);
?>
              </p>
                Your updated video information has been saved. It will appear on the site after a moderator approves it.
              </p>
              <p>
                <a href="./?page=view&postnum=<?php echo $postnum; ?>">Click here</a> for your video's permanent page.
              </p>
<?php
       return;
     }
   }

   if (!$postnum) {
     $scrambler = getscrambler();
     if (!$cap->verify($input, $time, $hash, $scrambler)) {
       $onloadid = 'input';
       return post('Wrong answer to simple arithmetic problem');
     }
   }

   if ($edit) {
     $keepcap = TRUE;
     $verify = $password;
     return post();
   }

   // Send a confirmation email
   $reginfo = array('video' => $video,
                    'email' => $email,
                    'password' => $password);
   if ($name) $reginfo['name'] = $name;
   if ($url) $reginfo['url'] = $url;
   $info = serialize($reginfo);
   $info = urlencode($mcrypt->encrypt($info, $scrambler));
   $baseurl = baseurl();
   $fullurl = "$baseurl?cmd=register&info=$info";

   $to = $email;
   $subject = "Post verification from The Patrick Henry Project";

   $message = "<html>
  <head>
    <title>$subject</title>
  </head>
  <body>
    <p>Click the link below to complete your video post.</p>
    <p style='margin-left: 2em;'><a href='$fullurl'>Complete Post</a></p>
    <p>If you didn't post a video to the Patrick Henry Project, please ignore this message.</p>
  </body>
</html>
";

   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   $headers .= 'From: admin@patrickhenryproject.org' . "\r\n";

   mail($to, $subject, $message, $headers);
?>
                <p>An email has been sent to <?php echo hsc($email); ?>. Click on the link in that email to complete your post.</p>
<?php
}

function baseurl() {
  $host = @$_SERVER['HTTP_HOST'];
  if (!$host) $host = "patrickhenryproject.org";
  $uri = @$_SERVER['REQUEST_URI'];
  $pos = strpos($uri, "?");
  if (!($pos === FALSE)) $uri = substr($uri, 0, $pos);
  $http = @$_SERVER['HTTPS'] ? "https" : "http";
  return "$http://$host$uri";
}

function register() {
  global $info;
  global $youtube, $video, $email, $password, $verify, $name, $url;
  global $keepcap, $string, $input, $time, $hash;
  global $submit, $edit, $cap, $mcrypt;
  global $db, $postnum;
  
  $info = @$mcrypt->decrypt($info, getscrambler());
  $reginfo = @unserialize($info);
  if (!$reginfo) {
    return post("Malformed registration info.");
  }
  //echo "<pre>"; print_r($reginfo); echo "</pre>\n";
  $video = $reginfo['video'];
  $youtube = "http://youtu.be/$video";
  $email = $reginfo['email'];
  $password = $reginfo['password'];
  $verify = $password;
  $name = $reginfo['name'];
  $url = $reginfo['url'];

  if ($db->getemailpost($email)) {
    // Don't give away email or password to stealer of verification message
    $email = '';
    $password = '';
    $verify = '';
    return edit('Email already registered');
  }

  $postnum = $db->nextpostnum();
  putmodinfo($postnum, $video, $email, $password, $name, $url);

  $email = NULL;
  $password = NULL;
  displaypost(true);
}

function putmodinfo($postnum, $video, $email, $password, $name, $url) {
  global $db;

  $passwordhash = $db->passwordhash($password, $salt);
  $info = array('postnum' => $postnum,
                'video' => $video,
                'emailhash' => sha1($email),
                'salt' => $salt,
                'passwordhash' => $passwordhash);
  if ($name) $info['name'] = $name;
  if ($url) $info['url'] = $url;

  $db->putmodinfo($postnum, $info);
  $db->putemailpost($email, $postnum);
}

function doedit() {
  global $email, $password, $newpass, $verify, $onloadid;
  global $time, $hash, $input;
  global $submit, $changepass, $delete, $forgot;
  global $postnum, $video, $name, $url;
  global $db, $cap, $mcrypt;

  if (!$email) {
    $onloadid = 'email';
    return edit('Email is required');
  }

  $postnum = $db->getemailpost($email);
  if (!$postnum) {
    $onloadid = 'email';
    return edit('Unknown email address');
  }
  $modp = false;
  $info = getinfo($postnum, $modp);
  if (!$info) {
    $onloadid = 'email';
    return edit("Can't find video record for that email address");
  }

  if ($submit || $changepass || $delete) {
    if (!$password) {
      $onloadid = 'password';
      return edit('Password is required');
    }
    if (!$db->verify_password($password, $info['salt'], $info['passwordhash'])) {
      $onloadid = 'email';
      return edit('Invalid email or password');
    }

    if ($submit) {
      $video = $info['video'];
      $name = @$info['name'];
      $url = @$info['url'];
      return post();
    } elseif ($changepass) {
      if ($newpass != $verify) {
        $onloadid = 'newpass';
        return edit('Passwords do not match');
      }
      $passwordhash = $db->passwordhash($newpass, $salt);
      $info['salt'] = $salt;
      $info['passwordhash'] = $passwordhash;
      if ($modp) $db->putmodinfo($postnum, $info);
      else $db->putinfo($postnum, $info);
      echo "<p>Your password has been changed.</p>";
      return;
    } else {
?>
              <p>Are you sure you want to delete your video?</p>
              <p>
                <form method='post' action='./'>
                  <input type='hidden' name='cmd' value='delete'/>
                  <input type='hidden' name='email' value='<?php echo hsc($email); ?>'/>
                  <input type='hidden' name='password' value='<?php echo hsc($password); ?>'/>
                  <input type='submit' name='submit' value='Delete'/>
                  <input type='submit' name='cancel' value='Cancel'/>
                </form>
              </p>
<?php
      return;
    }
  } elseif ($forgot) {
    $scrambler = getscrambler();
    if (!$cap->verify($input, $time, $hash, $scrambler)) {
      $onloadid = 'input';
      return edit('Wrong answer to simple arithmetic problem');
    }
    // Send password reset email
    $key = sha1($db->rand->urandom_bytes(20));
    $pwdinfo = array('postnum' => $postnum,
                     'key' => $key);
    $pwdinfo = serialize($pwdinfo);
    $pwdinfo = urlencode($mcrypt->encrypt($pwdinfo, $scrambler));
    $baseurl = baseurl();
    $fullurl = "$baseurl?page=forgot&info=$pwdinfo";
    $to = $email;
    $subject = "Password recovery from The Patrick Henry Project";
    $message = "<html>
  <head>
    <title>$subject</title>
  </head>
  <body>
    <p>Click the link below to set a new password.</p>
    <p style='margin-left: 2em;'><a href='$fullurl'>Set Password</a></p>
    <p>If you didn't request a password change from the Patrick Henry Project, please ignore this message.</p>
  </body>
</html>
";
   $headers  = 'MIME-Version: 1.0' . "\r\n";
   $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
   $headers .= 'From: admin@patrickhenryproject.org' . "\r\n";
   mail($to, $subject, $message, $headers);

   $info['key'] = $key;
   if ($modp) $db->putmodinfo($postnum, $info);
   else $db->putinfo($postnum, $info);
?>
                <p>An email has been sent to <?php echo hsc($email); ?>. Click on the link in that email to set your password.</p>
<?php
  }
}

function forgot($doit=false) {
  global $info, $password, $verify, $onloadid;
  global $db, $mcrypt;

  $pwdinfo = @$mcrypt->decrypt($info, getscrambler());
  $pwdinfo = @unserialize($pwdinfo);
  $invalidmsg = "<p>Invalid password reset info.</p>";
  if (!$pwdinfo) {
    echo $invalidmsg;
    return;
  }
  $postnum = $pwdinfo['postnum'];
  $key = $pwdinfo['key'];
  $modp = false;
  $postinfo = getinfo($postnum, $modp);
  if (!$postinfo || $key != @$postinfo['key']) {
    echo $invalidmsg;
    return;
  }
  $onloadid = 'password';
  $error = '';
  if ($doit) {
    if (!$password) $error = "Password is required";
    elseif ($password != $verify) $error = "Passwords do not match";
    else {
      $passwordhash = $db->passwordhash($password, $salt);
      $postinfo['salt'] = $salt;
      $postinfo['passwordhash'] = $passwordhash;
      unset($postinfo['key']);
      if ($modp) $db->putmodinfo($postnum, $postinfo);
      else $db->putinfo($postnum, $postinfo);
?>
              <p>Your password has been set.</p>
<?php
      return;
    }
  }
?>
              <p>
                Use this form to change your password.
              </p>
              <p>
                <form method='post' action='./'>
                  <input type='hidden' name='cmd' value='forgot'/>
                  <input type='hidden' name='postnum' value='<?php echo $postnum;?>'/>
                  <input type='hidden' name='info' value='<?php echo $info;?>'/>
                  <table>
                    <tr>
                      <td></td>
                      <td><span style='color: red;'><?php echo $error; ?></span></td>
                    </tr><tr>
                      <td style="text-align: right;">Password:</td>
                      <td><input type='password' name='password' id='password' size='20' value='<?php echo hsc($password); ?>'/></td>
                    </tr><tr>
                      <td style="text-align: right;">Password Again:</td>
                      <td><input type='password' name='verify' id='verify' size='20' value='<?php echo hsc($verify); ?>'/></td>
                    </tr><tr>
                      <td></td>
                      <td><input type='submit' name='submit' value='Set Password'/></td>
                    </tr>
                  </table>
                </form>
              </p>
<?php
}

function newadmin_error(&$error) {
  global $newadmin;
  if (!$error && $newadmin) $error = "Logged in as administrator";
}

function post($error=null) {
  global $youtube, $video, $name, $url, $email, $password, $verify;
  global $cap, $keepcap, $default_error, $onloadid;
  global $postnum;

  newadmin_error($error);
  if (!$error) $error = $default_error;
  if (!$onloadid) $onloadid = 'youtube';

  if (!$youtube && $video) $youtube = "http://youtu.be/$video";

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
<?php
  if ($postnum) {
?>
                  <input type='hidden' name='postnum' value='<?php echo $postnum; ?>'/>
<?php
  } else {
?>
                  <input type='hidden' name='string' value='<?php echo $string; ?>'/>
                  <input type='hidden' name='time' value='<?php echo $time; ?>'/>
                  <input type='hidden' name='hash' value='<?php echo $hash; ?>'/>
<?php
  }
?>
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
<?php
  if (!$postnum) {
?>
                    </tr><tr>
                      <td style='text-align: right;'><?php echo $string; ?> =</td>
                      <td><input type='text' name='input' id='input' size='2' value='<?php echo $input; ?>'/></td>
<?php
  }
?>
                    </tr><tr>
                      <td style="text-align: right;">Email:</td>
                      <td><input type='text' name='email' id='email' size='40' value='<?php echo $email; ?>'/></td>
                    </tr><tr>
                      <td style="text-align: right;">Password:</td>
                      <td><input type='password' name='password' id='password' size='20' value='<?php echo hsc($password); ?>'/></td>
<?php
  if (!$postnum) {
?>
                    </tr><tr>
                      <td style="text-align: right;">Password Again:</td>
                      <td><input type='password' name='verify' id='verify' size='20' value='<?php echo hsc($verify); ?>'/></td>
<?php
  }
?>
                    </tr><tr>
                      <td></td>
                      <td style="color: blue;">Optional</td>
                    </tr><tr>
                      <td style="text-align: right;">Name:</td>
                      <td><input type='text' name='name' id='name' size='40' value='<?php echo hsc($name); ?>'/></td>
                    </tr><tr>
                      <td style="text-align: right;">Web Site:</td>
                      <td><input type='text' name='url' id='url' size='40' value='<?php echo hsc($url); ?>'/></td>
                    </tr><tr>
                      <td></td>
                      <td><input type='submit' name='submit' value='Submit'/></td>
                    </tr>
                  </table>
                </form>
              </p>
<?php
   if ($postnum) {
?>
              <p>
                To make changes to your post, fill in your "Email" and "Password" and change your "YouTube Video", "Name", and "Web Site" as desired. Click the "Submit" button, and you will be able to view your new information before approving the change.
              </p>
<?php
  } else {
?>
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
}

function edit($error=null) {
  global $email, $password, $newpass, $verify;
  global $cap, $onloadid;

  if (!$onloadid) $onloadid = 'email';

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
                      <td><span style='color: red;'><?php echo $error; ?></span></td>
                    </tr><tr>
                      <td></td>
                      <td style="color: blue;">Required</td>
                    </tr><tr>
                      <td style="text-align: right;">Email:</td>
                      <td><input type='text' name='email' id='email' size='40' value='<?php echo $email; ?>'/></td>
                    </tr><tr>
                      <td></td>
                      <td style="color: blue;">Required for "Lookup", "Change Password", and "Delete"</td>
                    </tr><tr>
                      <td style="text-align: right;">Password:</td>
                      <td><input type='password' name='password' id='password' size='20' value='<?php echo hsc($password); ?>'/></td>
                    </tr><tr>
                      <td></td>
                      <td style="color: blue;">Required for "Change Password"</td>
                    </tr><tr>
                      <td style="text-align: right;">New Password:</td>
                      <td><input type='password' name='newpass' id='newpass' size='20' value='<?php echo hsc($newpass); ?>'/></td>
                    </tr><tr>
                      <td style="text-align: right;">Again:</td>
                      <td><input type='password' name='verify' id='verify' size='20' value='<?php echo hsc($verify); ?>'/></td>
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
                        <input type='submit' name='delete' value='Delete'/>
                        <input type='submit' name='forgot' value='Forgot Password'/>
                      </td>
                    </tr>
                  </table>
                </p>
                <p>
                  To look up your video, enter your "Email" address and your "Password", and click the "Lookup" button. To change your password, enter your "Email" address, your old "Password", the "New Password" and the new password "Again", and click the "Change Password" button. To delete your video, enter your "Email" address and your "Password", and click the "Delete" button. If you've forgotten your password, enter your "Email" address and the answer to the simple arithmetic problem, click the "Forgot Password" button, and a link will be sent to you allowing you to enter a new password.
                </p>
              </form>
<?php
}

function dodelete() {
  global $email, $password;
  global $submit;
  global $db;

  if (!$submit) return edit();
  $postnum = $db->getemailpost($email);
  if (!$postnum || !$db->verify_email_password($email, $password)) {
    return edit('Invalid email or  password');
  }
  $db->putemailpost($email, '');
  $db->putinfo($postnum, '');
  $db->putmodinfo($postnum, '');
  $db->freepostnum($postnum);
?>
                <p>Your video has been deleted.</p>
<?php
}

function videos() {
?>
<p>Videos will go here</p>
<?php
}

function notelogout() {
?>
                <p>You have been logged out as administrator.</p>
<?php
}

function getinfo($postnum, &$modp) {
  global $db;

  $modp = FALSE;
  $res = $db->getinfo($postnum);
  if (!$res) {
    $res = $db->getmodinfo($postnum);
    if ($res) $modp = true;
  }
  return $res;
}

function gencap() {
  global $cap, $keepcap, $string, $time, $hash, $input;
  if ($keepcap) return $cap->newtime($string, $input, getscrambler());
  return $cap->generate(getscrambler());
}

function getscrambler() {
  global $db;
  return $db->getscrambler();
}

// From http://www.php.net/manual/en/function.session-destroy.php
function kill_session() {
  // Unset all of the session variables.
  $_SESSION = array();

  // If it's desired to kill the session, also delete the session cookie.
  // Note: This will destroy the session, and not just the session data!
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
              $params["path"], $params["domain"],
              $params["secure"], $params["httponly"]
              );
  }

  // Finally, destroy the session.
  session_destroy();  
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
