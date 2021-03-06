<?php
/**
 * Copyright (c) 2008 Massachusetts Institute of Technology
 * 
 * Licensed under the MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 */


require "../page_builder/page_header.php";

// various copy includes
require_once "../../config.gen.inc.php";
require_once "data/data.inc.php";

// sets up google calendar classes
require "lib/gcalendar_setup.php";

// defines all the variables related to being today
require "lib/calendar_lib.php";

$service = Zend_Gdata_Calendar::AUTH_SERVICE_NAME; // predefined service name for calendar

// the method of building a query based on newEventQuery() just didn't seem to want to work. this did though.
$client = Zend_Gdata_ClientLogin::getHttpClient($username.'@gmail.com',$password,$service);
$gdataCal = new Zend_Gdata_Calendar($client);
$event = $gdataCal->getCalendarEventEntry('http://www.google.com/calendar/feeds/'.$calendars[$_REQUEST['cal']]['user'].'/private/full/_'.$_REQUEST['id']);

#$day_num = (string)(int)$event->start->day;

$when = $event->getWhen();
$startTime = $when[0]->startTime;
$endTime = $when[0]->endTime;
$date_str = strftime('%A, %B %e, %Y',strtotime($startTime));
if (!(strlen($startTime) == 10)) {
  $time_of_day = strftime('%l:%M%P',strtotime($startTime));
  if ($endTime != '') {
    $time_of_day .= "-".strftime('%l:%M%P',strtotime($endTime));
  }
}

$description = $event->getContent()->text;

list($description,$event_link) = getExtraData($description,'link',true);
list($description,$contact_phone) = getExtraData($description,'contact_phone',false);
list($description,$contact_email) = getExtraData($description,'contact_email',false);
list($description,$contact_name) = getExtraData($description,'contact_name',false);

function getExtraData($description,$extra_data_pattern,$urlize) {
	$extra_data = '';
        $pattern = '/\[\['.$extra_data_pattern.'\]\](.*)\[\[\/'.$extra_data_pattern.'\]\]/';
        if (preg_match($pattern,$description,$matches)) {
	  if (trim($matches[1] != '')) {
			if ($urlize) {
				$extra_data = URLize($matches[1]);
			}
			else {
				$extra_data = $matches[1];
			}
		}
		$description = preg_replace($pattern,'',$description);
	}
	return array($description,$extra_data);
}


function phoneURL($number) {
  if($number) {

    // add the local area code if missing
    if(preg_match('/^\d{3}-\d{4}/', $number)) {
      $number = '617' . $number;
    }

    // check if the number is short number such as x4-2323, 4-2323, 42323
    if(preg_match('/^\d{5}/', $number)) {
      $first_digit = substr($number, 0, 1);
    } elseif(preg_match('/^x\d/', $number)) {
      $number = substr($number, 1);
      $first_digit = substr($number, 0, 1);
    } elseif(preg_match('/^\d-\d{4}/', $number)) {
      $first_digit = substr($number, 0, 1);
    }

    // if short number add the appropriate prefix and area code
    $prefixes = array('252', '253', '324', '225', '577', '258');
    if($first_digit) {
      foreach($prefixes as $prefix) {
        if(substr($prefix, -1) == $first_digit) {
          $number = "617" . substr($prefix, 0, 2) . $number;
          break;
        }  
      }
    }

    // remove all non-word characters from the number
    $number = preg_replace('/\W/', '', $number);
    return "tel:1$number";
  }
}

function mapURL($event) {
  preg_match('/^((|W|N|E|NW|NE)\-?(\d+))/', $event->shortloc, $matches);
  if($matches[3]) {
    return "../map/detail.php?selectvalues={$matches[2]}{$matches[3]}&snippets={$event->shortloc}";
  } else {
    return "../map/search.php?filter=" . urlencode(briefLocation($event));
  }
}

function URLize($web_address) {
  if(preg_match('/^http\:\/\//', $web_address)) {
    return $web_address;
  } else {
    return 'http://' . $web_address;
  }
}

require "$prefix/detail.html";
$page->output();

?>
