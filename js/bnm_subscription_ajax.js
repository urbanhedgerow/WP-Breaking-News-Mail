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
                    '_wpnonce': jQuery(form).find('#_wpnonce').val(),
                    'ip': jQuery(form).find('#bnm_ip').val(),
                    'bnm_email': jQuery(form).find('#bnm_email').val()
                }, 
                function(response){                       
                    jQuery('#result').html(response);                    
                }
                );
        },             
        debug:true
    });
});
