jQuery(document).ready(function() {
    jQuery('#bnm_subscribe_form').validate({
        messages: {
            email: {
                required: 'Requerido',
                email: 'Please enter a valid email address, example: you@yourdomain.com',
                remote: jQuery.validator.format('{0} is already taken, please enter a different address.')
            }
        },  
        submitHandler: function(form) {                          
            jQuery( '#result').html('loading...'); 
            jQuery.post(
                bnm_ajax.ajaxurl, 
                {
                    'action':'bnm_process_subscription',
                    '_wpnonce': jQuery('#bnm_subscribe_form :_wpnonce').fieldValue()[0],
                    'ip': jQuery('#bnm_subscribe_form :ip').fieldValue()[3],
                    'bnm_email':  jQuery('#bnm_subscribe_form :bnm_email').fieldValue()[2]
                }, 
                function(response){                       
                    jQuery('#result').html(response);                    
                }
                );
        },             
        debug:true
    });
});
