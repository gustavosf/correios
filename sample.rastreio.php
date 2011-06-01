<?php

require 'lib/correios.php'; 

$correios = new Correios();
$p = $correios->rastreamento('RM813814408BR');

header('Content-Type: application/json; charset=utf8');
echo $p;

?>
