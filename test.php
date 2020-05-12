<?php

 include(__DIR__.'/paypal.php');

 $data=array(
   "f"=>'usuario',    //Fichero para leer
   "ref"=>'',  //Referencia
   "to"=>'',    //Total
   "q"=>'new'
 );

 print_r(Mecha($data));

 echo "\n\n FIN: \n";