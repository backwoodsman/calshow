<?php
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#This project's homepage is: http://cmsmadesimple.sf.net
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# This plugin tag by richard <richard@the-place.net>.  It is intended to
# extract a booking list, such as for a holiday letting property, from a
# list stored in either a page or a global block.  Each entry has four, 
# pipe-separated fields, arrival-date|departure-date|name|type, with an 
# optional '<br />' at the end to allow easy viewing for admin purposes.
#
# USAGE: {calshow [show="n"] [skip="m"] [block="bn"|page="pn"]}
# where n  is number of months to view - default 12
#       m  is number of months to skip forward and backward - default 6
#       bn is global content block name, or )
#       pn is page alias                    ) default page = calendar
#
# Formatting of the calendar is controlled by css.
#


function smarty_cms_function_calshow($params, &$smarty) {
    global $gCms;

	$display = "";
	$bookings = array();
	$days = array();
	$depdays = array();

	if (isset($params['block'])) {
		# get content from a global content block:
		$sourceblock = $params['block'];
		$modules = array_keys($gCms->modules);
		$modObj = $gCms->modules[$modules[0]]['object'];
		$bookingdata = $modObj->ProcessTemplateFromData("{global_content name='{$sourceblock}'}");
	} else {
		# so get content from a page (default is 'calendar'):
		if (isset($params['page'])) {
			$sourcepage = $params['page'] ;
		} else {
			$sourcepage = 'calendar';
		}
		$cntops = $gCms->getContentOperations();
		$content = $cntops->LoadContentFromAlias("$sourcepage");
		$bookingdata = $content->Show();
	}
	# and form an array of the data
	$bookings = explode("\n", $bookingdata);

	$calleduri = preg_replace('/\?.*/', '',  $_SERVER['REQUEST_URI']);
	$oururl = "http://".$_SERVER['HTTP_HOST'].$calleduri;
	
	# we need UTC to avoid summertime errors. 
	date_default_timezone_set('UTC');
	$sperday = 86400;
	$nowdate = strtotime(date('Y-m-d'));
	$nowd = $nowdate/$sperday;
	# unix "day number" of base date for display
	if ((isset($_GET['baseyear'])) && (isset($_GET['basemonth']))) {
		$basedate = strtotime($_GET['baseyear']."-".$_GET['basemonth']."-01");
	} elseif (isset($params['basedate'])) {
		$basedatefrnow = (strtotime($params['basedate']))/$sperday - $nowd;
		if (($basedatefrnow < 0 ) || ($basedatefrnow > 1095)) {
			$basedate = $nowdate;
		} else {
			$basedate = strtotime($params['basedate']);
		}
	} 
	if ( (!isset($basedate)) || (($basedate - $nowdate) > $sperday*3653 ) || ($basedate < $nowdate))  {
		$basedate = $nowdate;
	}

	$baseyear = date('Y', $basedate);
	$basemonth = date('m', $basedate);

	# number of months to display
	if ( isset($params['show']) ) {
		$show = $params['show'];
	} else { 
		$show = 12;
	}
	# year and month for skip forward/backward
	$nowyear = date('Y');
	$nowmonth = date('m');
	if (isset($params['skip'])) {
		$skip  = $params['skip'];
	} else {
		$skip = 6;
	}
	if ( $skip > $show ) {
		$skip = $show;
	}
	$latermonth = $basemonth + $skip;
	if ($latermonth > 12) {
		$lateryear = $baseyear + floor($latermonth / 12 );
		$latermonth = bcmod($latermonth, 12);
	} else {
		$lateryear = $baseyear;
	}
	if ( ($baseyear*12 + $basemonth) > ($nowyear*12 + $nowmonth) ) {
		$earliermonth = $basemonth -$skip;
		if ($earliermonth < 1) {
			$earlieryear = $baseyear + ceil($earliermonth / 12) -1;
			$earliermonth = bcmod($earliermonth, 12) + 12;
		} else {
			$earlieryear = $baseyear;
		}
	}
	
	$based = $basedate/$sperday;
	$curyear = date('Y', $basedate);
	$curmonth = date('m', $basedate);

	# make array of the data
	$bookingscount = count($bookings);
	
	for( $i = 0; $i < $bookingscount; $i++ ) {
		$row = preg_replace("/ *<\/?pre> */", '', $bookings[$i]);
		if (strpos($row, '|')) {
			list($arr, $dep, $name, $type) = explode("|", $bookings[$i]);
			$type = preg_replace('/\<br *\/*\>/', '', $type);
			$bookings[$i] = array( 'arr' => $arr, 'dep' => $dep, 'name' => $name, 'type' => $type );
			$arrd =strtotime($arr)/$sperday;
			$depd = strtotime($dep)/$sperday;
			$arrdaysfrnow = ($arrd - $nowd);
			$depdaysfrnow = ($depd - $nowd);
			$durationdays = ($depd - $arrd);
			# check for gross errors
			if (( $durationdays > 365 ) || ( $durationdays < 0 )) {
				$bookings[$i]['type'] = 'date-error';
				$days[$arrd] .= "error";
				$days[$depd] = "error";
			}
			elseif ( $depdaysfrnow < 0 ) {
				$bookings[$i]['type'] = 'past';
			}
			else {
				# add busy days to days array (note if already "out" "in" is concatenated to form "outin")
				$days[$arrd] = "in";
				for ( $j = $arrd+1; $j < $depd; $j++ ) {
					$days[$j] = "busy";
				}
				$depdays[$depd] = TRUE;
			}
		}
	}
		# start display with legend
$display = "<div id=\"callegend\">\n<table class=\"calendar\"><tr><th>&nbsp;legend:&nbsp;</th><td class=\"weekday\"><b>&nbsp; available &nbsp;</b></td><td class=\"weekday\"><div class=\"busy\"><b>&nbsp; not available &nbsp;</b></div></td><td><span>&nbsp; darker tones show weekend rates &nbsp;</span></td></tr></table>\n</div>\n";
	# set out the months in rows of three
	for ($row = 0; $row < ceil($show/3); $row++) {
		$display .= "<div class=\"calcontainer\">\n";
		for ($col = 0; $col < 3; $col++) {
			$showyear = $curyear;
			$showmonth = $curmonth + $col + (3*$row);
# 			echo "baseyear $baseyear, now month is $showmonth >> ";
			if ( $showmonth > 12 ) {
				$showyear = $baseyear + floor(($showmonth-1)/12);
				$showmonth = bcmod(($showmonth-1), 12) +1;
			}
# 			echo "trying $showyear -- $showmonth -- and same old daysarray.<br />\n";
			$display .= make_display($showyear, $showmonth, $days, $depdays);
		}
		$display .= "</div>\n";
	}
	$display .= "<div class=\"calcontainer\">\n";
	if ( (isset($earliermonth)) && (isset($earlieryear)) ) {
		$display .= "<div class=\"rectl\"><a href=\"$oururl?baseyear=$earlieryear&basemonth=$earliermonth\">earlier</a>\n</div>\n";
	}	
	$display .= "<div class='rectr'><a href=\"$oururl?baseyear=$lateryear&basemonth=$latermonth\">later</a>\n</div>\n";
	$display .= "</div>";
	$outtext = $display;

    return $outtext;
}


function make_display($ayear, $amonth, $dayarray, $depdays) {
	$sperday = 86400;
	$nowd = (strtotime(date('Y-m-d')))/$sperday;
	$months = array('0', 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
	$daynames = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun');
	# start output of calendar
	$text = "<div class=\"calblock\">\n    <table class=\"calendar\">\n        <caption class=\"calendar-month\">$months[$amonth] $ayear</caption>\n";
	$text .= "    <tbody><tr>\n";
	# daynames table header
	foreach ( $daynames as $dayname ) {
		$text .= "        <th>$dayname</th>";
	}
	$text .= "</tr>\n    <tr>\n";
	# first of month and number of days
	$firstdate = strtotime("$ayear-$amonth-01");
	$datumd = $firstdate/$sperday - 1;
	$firstarray = getdate($firstdate);
	$weekdayno = $firstarray["wday"];
	if($weekdayno == 0) { 
		$weekdayno = 7; 
	}
	$skip = $weekdayno - 1;
	$lastdayofmonth = date("t", $firstdate);
	# blank space at beginning
	switch ($weekdayno) {
    	case 1:
        	$cspan = '';
        	break;
    	case 2:
        	$cspan = "<td> </td>\n";
			break;
		default:
			$cspan = "<td colspan=\"$skip\"> </td>\n";
	}
	$text .= "$cspan";
	for ($d = 1; $d <= $lastdayofmonth; $d++ ) {
		if ($weekdayno > 4) { 
			$dayclass = "weekend";
		} else {
			$dayclass = "weekday";
		}
		$text .= "<td class = \"$dayclass\">";
		if( ($datumd+$d) < $nowd ) {
			$text .= "<div class=\"past\">$d</div>";
		} elseif ( isset($dayarray[$datumd+$d]) || ($depdays[$datumd+$d]) ) {
			if ( $depdays[$datumd+$d] ) {
				$text .= "<div class=\"out\">&nbsp;</div>";
			}	
			$text .= "<div class=\"".$dayarray[$datumd+$d]."\">$d</div>";
		} else {
			$text .= "$d";
		}
		$text .= "</td>\n";
		if ( $weekdayno == 7 ) {
			$text .= "</tr>\n    <tr>";
		}
		$weekdayno++;
		if ( $weekdayno == 8 ) {
			$weekdayno = 1;
		}
	}
	if ($weekdayno != 1) {
    	$skip = 8 - $weekdayno;
    	$text .= "<td colspan=\"$skip\">&nbsp;</td>\n ";
  	} 
	$text .= "      </tr>\n    </tbody>\n  </table>\n</div>\n";

return $text;
}

function smarty_cms_help_function_calshow()
{
  echo lang('help_function_calshow');
}


function smarty_cms_about_function_calshow()
{
?>
  <p>Author:  richard Lyons &lt;richard@the-place.net&gt; </p>
  <p>Version 0.1.1</p>
  <p>Change History<br/>
	0.1.1 - allow wrapping source in \<pre\> tags 
	0.1 - test version<br/>
  </p>
<?php
}

?>
