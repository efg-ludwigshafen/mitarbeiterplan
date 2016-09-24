<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$month = array_key_exists('m', $_GET) && preg_match('/^\d\d?$/i', $_GET['m']) ? $_GET['m'] : date('m');
$year = array_key_exists('y', $_GET) && preg_match('/^\d\d\d\d$/i', $_GET['y']) ? $_GET['y'] : date('Y');

$connection = mysqli_connect('localhost', 'root') or die ('Error connecting to mysql server: '.mysqli_error($connection));

if (!($result = mysqli_query($connection, 'CREATE DATABASE IF NOT EXISTS efg_mitarbeiterplan'))):
	echo 'Error creating database efg_mitarbeiterplan: '.mysqli_error($connection);
endif;

mysqli_select_db($connection, 'efg_mitarbeiterplan');
if (mysqli_connect_errno()):
	die ('Error connecting to database efg_mitarbeiterplan: '.mysqli_connect_error());
endif;

//if (!($result = mysqli_query($connection, "DROP TABLE dates"))):
//	die ('Error dropping table efg_mitarbeiterplan.dates'.mysqli_error($connection));
//endif;

if (!($result = mysqli_query($connection, "CREATE TABLE IF NOT EXISTS dates (
	date_id DATE NOT NULL,
	service VARCHAR(30) DEFAULT NULL,
	offering VARCHAR(30) DEFAULT NULL,
	sermon_subject VARCHAR(90) DEFAULT NULL,
	sermon_text VARCHAR(30) DEFAULT NULL,
	pastor VARCHAR(30) DEFAULT NULL,
	moderator VARCHAR(30) DEFAULT NULL,
	lector VARCHAR(30) DEFAULT NULL,
	worship_pastor VARCHAR(60) DEFAULT NULL,
	pianist VARCHAR(30),
	childrens_service VARCHAR(60) DEFAULT NULL,
	toddler_care VARCHAR(60) DEFAULT NULL,
	welcome VARCHAR(60) DEFAULT NULL,
	audio_engineer VARCHAR(30) DEFAULT NULL,
	video_engineer VARCHAR(30) DEFAULT NULL,
	coffee VARCHAR(60) DEFAULT NULL,
	clean_hemshofstrasse VARCHAR(60) DEFAULT NULL,
	clean_boehlstrasse VARCHAR(60) DEFAULT NULL,
	flowers VARCHAR(30) DEFAULT NULL,
	other_preachers VARCHAR(60) DEFAULT NULL,
	supper_lead VARCHAR(30) DEFAULT NULL,
	supper_distribute VARCHAR(90) DEFAULT NULL,
	PRIMARY KEY (date_id)
)"))):
	die ('Error creating table efg_mitarbeiterplan.dates'.mysqli_error($connection));
endif;

if (!($result = mysqli_query($connection, "INSERT IGNORE INTO dates (date_id) VALUES ".join(', ', array_map(function($s) {return "('".$s."')";}, getDateForSpecificDayBetweenDates($year."-".$month."-01", "+3 months")))))):
	die ('Error inserting values into database: '.mysqli_error($connection));
endif;

/** @see http://stackoverflow.com/a/7213207/1168892 */
function getDateForSpecificDayBetweenDates($start, $end, $weekday = 0){
	$weekdays="Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday";

	$arr_weekdays = explode(",", $weekdays);
	$weekday = $arr_weekdays[$weekday];
	if (!$weekday)
		die("Invalid Weekday!");

	$start = strtotime("+0 day", strtotime($start) );
	$end = strtotime($end);

	$dateArr = array();
	$friday = strtotime($weekday, $start);
	while($friday <= $end)
	{
		$dateArr[] = date("Y-m-d", $friday);
		$friday = strtotime("+1 weeks", $friday);
	}
	$dateArr[] = date("Y-m-d", $friday);

	return $dateArr;
}

function to_input($str, $name_id, $name_postfix) {
	return "<input name=\"".$name_id."_".$name_postfix."\" value=\"".$str."\" list=\"list_".$name_postfix."\" data-mode=\"navigation\">"; 
}
?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'):

function valid($s) {
	return preg_replace('/[^a-zäöüßA-ZÄÖÜ\. \/-]/', '', $s);
}

$new_dates = [];
foreach ($_POST as $k => $v):
	$date_id = substr($k, 0, strpos($k, '_'));
	$field = substr($k, strpos($k, '_') + 1);
	if (array_key_exists($date_id, $new_dates)):
		$new_dates[$date_id][$field] = valid($v);
	else:
		$new_dates[$date_id] = [$field => valid($v)];
	endif;
endforeach;

foreach ($new_dates as $date_id => $d):
	$update_cols = array_filter(array_keys($d), function($key) use ($d) { return $key != 'date_id' && $d[$key] != ''; });
	if (count($update_cols) > 0 && !$result = mysqli_query($connection, "UPDATE dates SET ".join(',', array_map(function($key) use ($d) {
			return $key."='".$d[$key]."'";
		}, $update_cols))." WHERE date_id = '".join('-', array_reverse(explode('-', $date_id)))."';")):
		die ('Error updating '.$date_id.': '.mysqli_error($connection));
	endif;
endforeach;

endif; ?>

<?php // if ($_SERVER['REQUEST_METHOD'] === 'GET'): // (need to send if POST as well -- otherwise screen is white after form submission)

if (!($result = mysqli_query($connection, "SELECT *, DATE_FORMAT(date_id, '%d-%m-%Y') as date_id FROM dates WHERE MONTH(date_id) = ".$month." and YEAR(date_id) = ".$year))):
	die ('Error selecting values from database: '.mysqli_error($connection));
endif;

$dates = [];
while ($row = mysqli_fetch_array($result)):
	$dates[] = $row;
endwhile;

$rows = [
	['Gottesdienst', 'service'],
	['Opfer', 'offering'],
	['Predigtthema', 'sermon_subject'],
	['Predigttext', 'sermon_text'],
	['Predigt', 'pastor', 'section-start'],
	['Leitung', 'moderator'],
	['Schriftlesung', 'lector'],
	['Anbetung', 'worship_pastor', 'section-start'],
	['Klavier', 'pianist'],
	['Kindergottesdienst', 'childrens_service', 'section-start'],
	['Kleinkinderbetreuung', 'toddler_care'],
	['Begrüßung', 'welcome', 'section-start'],
	['Tontechnik', 'audio_engineer'],
	['Beamer', 'video_engineer'],
	['Kaffee', 'coffee'],
	['Putzen Hemshofstraße', 'clean_hemshofstrasse', 'section-start'],
	['Putzen Böhlstraße', 'clean_boehlstrasse'],
	['Blumenschmuck', 'flowers', 'section-start'],
	['Sonstige Predigtdienste', 'other_preachers'],
	['Abendmahl leiten', 'supper_lead', 'section-start'],
	['Abendmahl austeilen', 'supper_distribute']
];
?>
<!doctype html>
<meta charset="utf-8">
<title>Mitarbeiterplan <?php echo date('F Y', strtotime($year."-".$month."-01")); ?></title>
<style>
* {
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}
html {
	font-size: calc(1em + .5vmin); 
	font-family: sans-serif;
}
body {
	margin: 0 auto;
}
table {
	width: 100%;
	border-collapse: collapse;
}
th,
td {
	border: 1px solid rgba(0,0,0,.1);
	line-height: 2em;
	text-align: left;
}
th,
th input,
td input {
	padding: 0 1rem;
}
table input {
	display: block;
	padding: 0 1rem;
	height: 2rem;
	width: 100%;
	border: none;
	font-size: 1em;
	text-overflow: ellipsis;
}
.section-start th,
.section-start td {
	border-top-width: 3px;
}
:focus {
	outline: 1px dotted rgba(0,0,0,.5);
	outline-offset: -3px;
}
[disabled] {
	opacity: .5;
}
button {
	margin: .5rem 1rem;
	padding: 0 1rem;
	border-radius: .25rem;
	border: 1px solid rgba(0,0,0,.1);
	line-height: 2;
	font-size: 1em;

	color: white;
	background-color: #55ace8;

	cursor: pointer;
}
button:focus {
	outline: 1px dotted white;
}
button:hover,
button:focus,
button:active {
	background-color: #449bd7;
}
h1 {
	display: none;
}
@media print {
	h1 {
		display: block;
	}
	html {
		font-size: 12pt;
	}
	button {
		display: none;
	}
	th,
	td {
		line-height: 1.5;
		border-color: #999;
	}
	table input {
		height: 1.5rem;
		text-overflow: clip;
	}
}
</style>
<h1>Mitarbeiterplan <?php echo date('F Y', strtotime($year."-".$month."-01")); ?></h1>
<form name="mitarbeiterplan" method="POST" action=".">
<table>
	<tr><th></th><?php foreach ($dates as $date): ?><th><?php echo str_replace('-', '.', $date['date_id']); ?></th><?php endforeach; ?></tr>
	<?php foreach ($rows as $row): ?><tr<?php if (count($row) == 3) { echo ' class="'.$row[2].'"'; } ?>><th><?php echo $row[0]; ?></th><?php foreach ($dates as $d): ?><td><?php echo to_input($d[$row[1]], $d['date_id'], $row[1]); ?></td><?php endforeach; ?></tr>
	<?php endforeach; ?>
</table>
<p style="text-align:right">
	<button>Speichern</button>
</p>
</form>
<div style="display:none;visibility:hidden" aria-hidden="true">
	<?php foreach ($rows as $row): ?>
	<datalist id="list_<?php echo $row[1]; ?>"><?php foreach (array_reduce($dates, function($options, $date) use ($row) { if ($date[$row[1]] != '' && !in_array($date[$row[1]], $options)) { $options[] = $date[$row[1]]; } return $options; }, []) as $option): ?><option value="<?php echo $option; ?>"><?php endforeach; ?></datalist>
	<?php endforeach; ?>
</div>
<script>
function currentContentColIndex($i) {
	return $i.parentElement.cellIndex - 1;
}

function currentContentRowIndex($i) {
	return $i.parentElement.parentElement.rowIndex - 1;
}

function focusContentCell(column, row) {
	var trs = document.querySelector('table').querySelectorAll('tr');
	var targetTr = trs[Math.min(trs.length-1, Math.max(1, row+1))];
	var tds = targetTr.children;
	var targetTd = tds[Math.min(tds.length-1, Math.max(1, column+1))];
	targetTd.firstChild.focus();
}

function getCookie(name) {
	return unescape((document.cookie.match(name + '=([^;]+)(;|$)') || Array(2))[1] || '');
}

if (getCookie('col') && getCookie('row')) {
	focusContentCell(parseInt(getCookie('col')), parseInt(getCookie('row')));
}

document.mitarbeiterplan.addEventListener('keydown', function(e) {
	if (!e.altKey && e.ctrlKey && !e.metaKey && !e.shiftKey) {
		switch (e.keyCode) {
			case 13: // [CTRL]+[ENTER]
				document.mitarbeiterplan.submit();
				break;
		}
	}
	if (!e.altKey && !e.ctrlKey && e.metaKey && !e.shiftKey) {
		switch (e.keyCode) {
			case 13: // [CMD]+[ENTER]
				document.mitarbeiterplan.submit();
				break;
		}
	}
});

window.addEventListener('beforeunload', function(e) {
	if (document.activeElement.nodeName === 'INPUT') {
		document.cookie = 'col=' + currentContentColIndex(document.activeElement); 
		document.cookie = 'row=' + currentContentRowIndex(document.activeElement);
	}
});

[].forEach.call(document.querySelectorAll('input'), function($i) {
	if ($i.scrollWidth > $i.clientWidth) {
		$i.title = $i.value;
	}
	$i.addEventListener('focus', function(e) {
		e.target.dataset.mode = 'navigation';
		e.target.select();
	});
	$i.addEventListener('keydown', function(e) {
		var handled = false;
		if (!e.altKey && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
			switch (e.keyCode) {
				case 13: // [ENTER]
					focusContentCell(currentContentColIndex(e.target), currentContentRowIndex(e.target) + 1);
					handled = true; // need to cancel form submission on any enter -- form should only be submitted at [CMD]+[ENTER]/[CTRL]+[ENTER] (see form handler)
					break;
				case 27: // [ESC]
					e.target.value = e.target.defaultValue || e.target.value;
					if (e.target.dataset.mode === 'navigation') {
						e.target.select();
					} else {
						e.target.setSelectionRange(0, 0);
					}
					handled = true;
					break;
				case 37: // [LEFT]
					if (e.target.dataset.mode === 'navigation') {
						focusContentCell(currentContentColIndex(e.target) - 1, currentContentRowIndex(e.target));
						handled = true;
					}
					break;
				case 38: // [UP]
					if (e.target.dataset.mode === 'navigation') {
						focusContentCell(currentContentColIndex(e.target), currentContentRowIndex(e.target) - 1);
						handled = true;
					}
					break;
				case 39: // [RIGHT]
					if (e.target.dataset.mode === 'navigation') {
						focusContentCell(currentContentColIndex(e.target) + 1, currentContentRowIndex(e.target));
						handled = true;
					}
					break;
				case 40: // [DOWN]
					if (e.target.dataset.mode === 'navigation') {
						focusContentCell(currentContentColIndex(e.target), currentContentRowIndex(e.target) + 1);
						handled = true;
					}
					break;
				case 113: // [F2]
					e.target.dataset.mode = e.target.dataset.mode === 'navigation' ? 'edit' : 'navigation';
					if (e.target.dataset.mode === 'navigation') {
						e.target.select();
					} else {
						e.target.setSelectionRange(0, 0);
					}
					handled = true;
					break;
			}
		}
		if (handled) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});
});
</script>
<?php // endif; ?>