<?php

require 'lib/correios.php'; 

$c = new Correios();

header('Content-Type: application/json; charset=utf8');
echo $c->cep('90410005');

?>
