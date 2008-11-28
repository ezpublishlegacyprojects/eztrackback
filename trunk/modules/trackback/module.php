<?php

/**
 * ID as view is workaround for issue http://issues.ez.no/12795
 * where module can't be a view anymore
 */

$Module = array( 'name' => 'eZ trackback' );

$ViewList = array();
$ViewList['id'] = array( 'script' => 'id.php',
                         'params' => array( 'id' => 'ID' ) );


?>
