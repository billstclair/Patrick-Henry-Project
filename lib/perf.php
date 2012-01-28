<?php

  // perf.php
  // Code performance monitor
  // On my 2 GHz machine, it takes about 150 nanoseconds per start/stop call.
  // That means you can only effectively time things that take a microsecond
  // or more.
  // $correction helps a little.

/**********************************************************************
 * Usage:
 * perf_init();               // Initialize global state
 * $idx = perf_start($name);  // Enter function named $name
 * perf_stop($idx);           // Exit entry above
 * $res = perf_times();       // $res = array(array('cnt' => $count,
 *                            //                    'time' => $time))
 **********************************************************************/

$perf_instance = null;

function perf_init() {
  global $perf_instance;
  $perf_instance = new perf();
}

function perf_start($name) {
  global $perf_instance;
  if ($perf_instance) return $perf_instance->start($name);
}

function perf_stop($idx) {
  global $perf_instance;
  if ($perf_instance) $perf_instance->stop($idx);
}

function perf_times($times=null) {
  global $perf_instance;
  if ($perf_instance) return $perf_instance->times($times);
  return $times;
}

function perf_precision() {
  global $perf_instance;
  if ($perf_instance) return $perf_instance->PRECISION;
  else return 0;
}

class perf {

  var $times;
  var $stack;

  var $PRECISION = 8;
  var $correction = 0;

  function perf() {
    $this->times = array();
    $this->stack = array();
    $this->stop($this->start('test'));
    $time0 = $this->times['test']['time'];
    $this->times['test']['time'] = 0;
    $this->stop($this->start('test'));
    $time1 = $this->times['test']['time'];
    // Hard to say how much we should weight initial vs. subsequent timing
    $this->correction = bcdiv(bcadd($time0, $time1, $this->PRECISION), 2, $this->PRECISION);
    $this->times = array();
    $this->stack = array();
  }

  function start($name) {
    $now = $this->now();
    $end = count($this->stack) - 1;

    $this->times[$name]['cnt'] = @$this->times[$name]['cnt'] + 1;
    if ($end >= 0) {
      $oname = $this->stack[$end][0];
      $time = $this->stack[$end][1];
      $delta = bcsub($now, $time, $this->PRECISION);
      $this->times[$oname]['time'] =
        bcadd(@$this->time[$oname]['time'], $delta, $this->PRECISION);
    }
    $this->stack[$end+1] = array($name, $now);
    return count($this->stack) - 1;
   }

  function stop($idx) {
    $now = $this->now();
    $end = count($this->stack) - 1;
    $name = $this->stack[$end][0];
    $time = $this->stack[$end][1];

    $delta = bcsub($now, $time, $this->PRECISION);
    $delta = bcsub($delta, $this->correction, $this->PRECISION);

    $this->times[$name]['time'] =
      bcadd(@$this->times[$name]['time'], $delta, $this->PRECISION);
    for ($i=$end; $i>=$idx; $i--) unset($this->stack[$i]);
    
    if ($i >= 0) {                                      
      $this->stack[$i][1] = $now;
    }
  }

  function comparestats($s1, $s2) {
    $t1 = $s1['time'];
    $t2 = $s2['time'];
    if ($t1 < $t2) return 1;
    if ($t1 == $t2) return 0;
    return -1;
  }

  // Return times, sorted by decreasing time, with a "Total" item
  function times($times=null) {
    if (!$times) $times = $this->times;
    unset($times['Total']);
    $total = 0;
    foreach($times as $name => $stats) {
      $total = bcadd($total, $stats['time'], $this->PRECISION);
    }
    $times['Total'] = array('cnt' => '', 'time' => $total);
    uasort($times, array("perf", "comparestats"));
    return $times;
  }

  // Returns the current time as a float
  // PHP 5 supports this natively, but I want this to work in PHP 4,
  // and to use bcmath
  function now() {
    $now = microtime();         // "microseconds seconds"
    $a = explode(' ', $now);
    return bcadd($a[0], $a[1], $this->PRECISION);
  }

}

/* Test code
// Proper results are 0.006 for 'x' and 0.002 for 'y'
perf_init();
$x1 = perf_start('x');
$t1 = 2000;
$t2 = 1000;
usleep($t1);                    // sleep $t1 microseconds
$y = perf_start('y');
usleep($t2);
$x2 = perf_start('x');
usleep($t1);
perf_stop($x2);
usleep($t2);
perf_stop($y);
usleep($t1);
perf_stop($x1);
print_r(perf_times());
echo "\n";
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
 * Portions created by the Initial Developer are Copyright (C) 2008
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
