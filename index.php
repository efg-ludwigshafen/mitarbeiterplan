<?php
$connection = mysqli_connect('localhost', 'root') or die ('Error connecting to mysql server: '.mysqli_error($connection));

if (!($result = mysqli_query($connection, 'CREATE DATABASE IF NOT EXISTS efg_mitarbeiterplan'))) {
	echo 'Error creating database efg_mitarbeiterplan: '.mysqli_error($connection);
}

mysqli_select_db($connection, 'efg_mitarbeiterplan');
if (mysqli_connect_errno()) {
	die ('Error connecting to database efg_mitarbeiterplan: '.mysqli_connect_error());
}

if (!($result = mysqli_query($connection, "DROP TABLE dates"))) {
	die ('Error dropping table efg_mitarbeiterplan.dates'.mysqli_error($connection));
}

if (!($result = mysqli_query($connection, "CREATE TABLE IF NOT EXISTS dates (
	date_id DATE NOT NULL,
	service VARCHAR(30),
	offering VARCHAR(30),
	PRIMARY KEY (date_id)
)"))) {
	die ('Error creating table efg_mitarbeiterplan.dates'.mysqli_error($connection));
}

if (!($result = mysqli_query($connection, "INSERT INTO dates VALUES
('2016-07-03', NULL, 'Kinder-Gottesdienst'),
('2016-07-10', 'Abendmahl', 'Fam. Binder / Pakistan'),
('2016-07-17', NULL, 'SOLA MANNHEIM'),
('2016-07-24', NULL, 'Diakonische Aufgaben'),
('2016-07-31', 'Seniorenkreis', 'Allianz-Arbeit')
"))) {
	die ('Error inserting values into database: '.mysqli_error($connection));
}

if (!($result = mysqli_query($connection, "SELECT service, offering, DATE_FORMAT(date_id, '%d.%m.%Y') as date_id FROM dates WHERE DATEDIFF(date_id, NOW()) < 8"))) {
	die ('Error selecting values from database: '.mysqli_error($connection));
}

$dates = [];
while ($row = mysqli_fetch_object($result)) {
	$dates[] = $row;
}
?>
<!doctype html>
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
	max-width: 70rem;
}
table {
	width: 100%;
	border-collapse: collapse;
}
th,
td {
	padding: 0 1rem;
	border: 1px solid rgba(0,0,0,.1);
	line-height: 2;
}
</style>
<table>
	<tr><th></th><?php foreach ($dates as $date): ?><th><?php echo $date->date_id; ?></th><?php endforeach; ?></tr>
	<tr><th>Gottesdienst</th><?php foreach ($dates as $date): ?><td><?php echo $date->service; ?></td><?php endforeach; ?></tr>
	<tr><th>Opfer</th><?php foreach ($dates as $date): ?><td><?php echo $date->offering; ?></td><?php endforeach; ?></tr>
</table>