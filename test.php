<?php

 include(__DIR__.'/paypal.php');

 $data=array(
   "f"=>'usuario',    //Fichero para leer
   "ref"=>'asas1s',  //Referencia
   "to"=>'100',    //Total
   "q"=>'new'
 );

 print_r(Mecha($data));

 echo "\n\n FIN: \n";