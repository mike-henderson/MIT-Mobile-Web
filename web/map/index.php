<?php

/**
 * Copyright (c) 2008 Massachusetts Institute of Technology
 * 
 * Licensed under the MIT License
 * Redistributions of files must retain the above copyright notice.
 * 
 */

require_once "../../lib/db.php";
require_once "../page_builder/page_header.php";
require_once "../../config.gen.inc.php";
require_once "lib/map.lib.inc.php";
require_once "data/data.inc.php";

$category_info = Categorys::$info;

$index = ($prefix == "ip") ? 0 : 2;

$categorys = array();
foreach($category_info as $category => $title) {
  $categorys[$category] = $title[$index];
}

if(!isset($_REQUEST['category'])) {
  $page->cache();
  require "$prefix/index.html";
} else {
  $category = $_REQUEST['category'];
  $title = $category_info[$category][2];


  if(!isset($_REQUEST['drilldown'])) {
    if($category=="names" || $category=="campus" || $category=="codes") {
      require "$prefix/$category.html";
    } else {
	  if ($category=="wifi") {
		 $places = getData("wifi = 'Y'");
	  }
	  else {
		$places = getData("type = '".$category_info[$category][3]."'");
	  }
      require "$prefix/places.html";
    }
  } else {
    $titlebar = ucwords($category_info[$category][0]);
    $drilldown = str_replace('%20',' ',$_REQUEST['drilldown']);
    $drilldown_title = $_REQUEST['desc'];
    if ($category=="names") {
	    if (stristr($drilldown,"-")) {
		    $sql_substr = '(';
			$sql_substr .= subSQLStrBuilder($drilldown);
			$sql_substr .= ')';
	    }
	    else {
			$sql_substr = "name LIKE \"".$drilldown."%\"";
	    }
	    $places = getData("(type = 'Building' OR type='Housing' OR type='Library' OR type='PRT Station' OR type='Athletic Facility') and ".$sql_substr);
        require "$prefix/drilldown.html";
    }    
    else if ($category=="campus") {
		$places = getData("(type = 'Building' OR type='Housing' OR type='Library' OR type='PRT Station' OR type='Athletic Facility') and campus = '".$drilldown."'");
	    require "$prefix/drilldown.html";
	}
	else if ($category=="codes") {
		$db = db::$connection;
		$stmt = $db->prepare("SELECT * FROM Buildings WHERE code LIKE \"".$drilldown."%\" GROUP BY code ORDER BY code ASC");
        $stmt->execute();
	    $places = $stmt->fetchAll();
		require "$prefix/drilldown_codes.html";
	}
    
  }
} 

$page->output();

############################################################
### Extra functions
############################################################

function places() {
  require "buildings.php";

  if($_REQUEST['category'] == 'buildings') {
    // array needs to be converted to a hash
    $places = array();
    foreach($buildings as $building_number) {
      $places[$building_number] = $building_number;
    }
  } else {
    $places = ${$_REQUEST['category']};
  }
  return $places;
}

function places_sublist($listName) {
  if($_REQUEST['category'] == 'buildings') {
    $drill = new DrillNumeralAlpha($listName, "key");
  } else {
    $drill = new DrillAlphabeta($listName, "key");
  }
  return $drill->get_list(places());
}


function drillURL($drilldown, $name=NULL) {
  $url = categoryURL() . "&drilldown=$drilldown";
  if($name) {
    $url .= "&desc=" . urlencode($name);
  }
  return $url;
}

function categoryURL($category=NULL) {
  $category = $category ? $category : $_REQUEST['category'];
  return "?category=$category";
}

function searchURL() {
  return "search.php";
}

function subSQLStrBuilder($drilldown) {	
	$drilldown_a = explode('-',$drilldown);
	$alpha_a = array("1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
	$i = array_search($drilldown_a[0], $alpha_a);
	$max = array_search($drilldown_a[1], $alpha_a);
	while ($i <= $max) {
		$sql_str .= "name LIKE \"".$alpha_a[$i]."%\"";
		if ($i < ($max)) { $sql_str .= " OR "; }
		$i++;
	}
	return $sql_str;
}
function getData($where=false) {
	$db = db::$connection;
    if ($where) {
		$stmt = $db->prepare("SELECT * FROM Buildings WHERE ".$where." GROUP BY name ORDER BY name ASC");
	}
	else {
		$stmt = $db->prepare("SELECT * FROM Buildings GROUP BY name ORDER BY name ASC");
	}
        $stmt->execute();
	$result = $stmt->fetchAll();
	return $result;
}

    
?>
