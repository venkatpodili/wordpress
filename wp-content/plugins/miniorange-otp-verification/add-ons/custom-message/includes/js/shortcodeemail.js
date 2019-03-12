jQuery(document).ready(function (){
    // The button and message content
    const html = "<img alt='"+movcustomemail.alt+"' src='"+movcustomemail.img+"'>";
    const msgbox = '<input type="button" id="mo_custom_email_send" value="'+movcustomemail.buttonText+'">' +
        '         <div  class="mo_message" ' +
        '               id="mo_email_message" ' +
        '               hidden="" ' +
        '               style=" background-color: #f7f6f7;' +
        '                       padding: 1em 2em 1em 3.5em; ' +
        '                       text-align: center;' +
        '                       margin-top:3px;">' +
        '         </div>';

    $mo=jQuery;

    // if Custom SMS Box is on page then append the button and message box
    if($mo('#custom_email_box').length>0) {
        $mo("#custom_email_box").append(msgbox);
    }

    // pick up the message and phone number after user clicks on the send button
    // and send it to the backend for processing
    $mo("#mo_custom_email_send").click( function(){
        $mo("#mo_email_message").empty();
        $mo("#mo_email_message").append(html);
        $mo.ajax( {
            url: movcustomemail.url,
            type:"POST",
            data:{
                action:movcustomemail.action,
                toEmail:$mo("input[name='toEmail']").val(),
                content:$mo("#custom_email_box textarea").val(),
                subject:$mo("#custom_email_subject").val(),
                fromName:$mo("#custom_email_from_name").val(),
                fromEmail:$mo("#custom_email_from_id").val(),
                security:movcustomemail.nonce,
                ajax_mode:true,
            },
            crossDomain:!0,
            dataType:"json",
            success:function(o){
                if(o.result==="success") { // show the success message returned from the server
                    $mo("#mo_email_message").empty();
                    $mo("#mo_email_message").show();
                    $mo("#mo_email_message").append(o.message);
                    $mo("#mo_email_message").css("border-top","3px solid green");
                } else { // show the error message returned from the server
                    $mo("#mo_email_message").empty();
                    $mo("#mo_email_message").show();
                    $mo("#mo_email_message").append(o.message);
                    $mo("#mo_email_message").css("border-top","3px solid red");
                }
            },
            error:function(o){}
        });
    });

});
