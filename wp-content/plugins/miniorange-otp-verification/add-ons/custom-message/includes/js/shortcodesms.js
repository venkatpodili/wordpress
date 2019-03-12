jQuery(document).ready(function (){
    // The button and message content
    const html = "<img alt='"+movcustomsms.alt+"' src='"+movcustomsms.img+"'>";
    const msgbox = '<input type="button" id="mo_custom_sms_send" value="'+movcustomsms.buttonText+'">' +
        '         <div  class="mo_message" ' +
            '               id="mo_sms_message" ' +
        '               hidden="" ' +
        '               style=" background-color: #f7f6f7;' +
        '                       padding: 1em 2em 1em 3.5em; ' +
        '                       text-align: center;' +
        '                       margin-top:3px;">' +
        '         </div>';

    $mo=jQuery;

    // if Custom SMS Box is on page then append the button and message box
    if($mo('#custom_sms_box').length>0) {
        $mo("#custom_sms_box").append(msgbox);
    }

    //calculate the character count at the start of page load
    if($mo("textarea#custom_sms_msg").length>0){
        calculateCharacterCount($mo("textarea#custom_sms_msg"));
    }
    $mo("textarea#custom_sms_msg").keyup(function(e) {
        calculateCharacterCount(this);
    });

    // pick up the message and phone number after user clicks on the send button
    // and send it to the backend for processing
    $mo("#mo_custom_sms_send").click( function(){
        const e=$mo("input[name='mo_phone_numbers']").val();
        const f=$mo("#custom_sms_msg").val();
        $mo("#mo_sms_message").empty();
        $mo("#mo_sms_message").append(html);
        $mo.ajax( {
            url: movcustomsms.url,
            type:"POST",
            data:{
                action:movcustomsms.action,
                mo_phone_numbers:e,
                mo_customer_validation_custom_sms_msg:f,
                security:movcustomsms.nonce,
                ajax_mode:true,
            },
            crossDomain:!0,
            dataType:"json",
            success:function(o){
                if(o.result==="success") { // show the success message returned from the server
                    $mo("#mo_sms_message").empty();
                    $mo("#mo_sms_message").show();
                    $mo("#mo_sms_message").append(o.message);
                    $mo("#mo_sms_message").css("border-top","3px solid green");
                } else { // show the error message returned from the server
                    $mo("#mo_sms_message").empty();
                    $mo("#mo_sms_message").show();
                    $mo("#mo_sms_message").append(o.message);
                    $mo("#mo_sms_message").css("border-top","3px solid red");
                }
            },
            error:function(o){}
        });
    });

});

/**
 * Calculate and show the remaining character count on the
 * page where the custom sms-shortcode has been appended.
 *
 * @param $this
 */
function calculateCharacterCount($this) {
    $mo = jQuery;
    var maxlen = 160;
    const len = $mo($this).val().length;
    const elem = $mo("span#remaining");
    elem.html(maxlen-len);
    if(len>maxlen){
        elem.parent("span").addClass("limit");
    }else{
        elem.parent("span").removeClass("limit");
    }
}