/* var form = new FormData($(this)[0]);
 $.ajax({
    type: 'POST',
    url: BASE_URL + '/api/governance/update_control',
    data: form,
    async: true,
    cache: false,
    contentType: false,
    processData: false,
    success: function(result){
        var data = result.data;alert('Results: '+result.status_message);
        if(result.status_message){ 
            //showAlertsFromArray(result.status_message);
        }
        $('#control--update').modal('toggle');
        //controlDatatable.ajax.reload(null, false);
    }
})
.fail(function(xhr, textStatus){
    if(!retryCSRF(xhr, this))
    {
        if(xhr.responseJSON && xhr.responseJSON.status_message){
            showAlertsFromArray(xhr.responseJSON.status_message);
        }
    }
    
});*/

form.append('control_id', $('[name=control_id]', modal).val()); 

                        