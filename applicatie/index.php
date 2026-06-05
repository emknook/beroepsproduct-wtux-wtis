<?php
require_once 'db_connectie.php';

// maak verbinding met de database (zie db_connection.php)
$db = maakVerbinding();
$query = 'select * from product';

$data = $db->query($query);

$html = "";
$textdata = "";
while($rij = $data->fetch()) {
  $name = $rij['name'];
  $html = $html . '<li>' . $name . "</li>";
  $textdata = $textdata . '<br>' . $rij[0] . ' - ' . $rij[1] . ' - ' . $rij[2];
}
echo '<ul style="list-style-type: none">' . $html . '</ul>';
echo $textdata;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index</title>
</head>
<body>
    <h1>It Works!</h1>
    <?php echo('Hallo WT\'er, de webserver is online en PHP werkt.'); ?>
    <br>
    <br>
    Alle technische informatie over je webserver vind je hier: <a href="phpinfo.php">http://<?=$_SERVER['HTTP_HOST']?>/phpinfo.php</a>
    <br>
    <br>
    Een voorbeeld van een pagina die gegevens uit de database haalt vind je hier: <a href="componist-aantalstukken.php">http://<?=$_SERVER['HTTP_HOST']?>/componist-aantalstukken.php</a>
</body>
</html>