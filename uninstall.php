<?php register_uninstall_hook(  __FILE__, 'bnm_unistall' ); 

function bnm_unistall() {
    //delete any options, tables, etc the plugin created
    delete_option( 'bnm_options' );
}

?>