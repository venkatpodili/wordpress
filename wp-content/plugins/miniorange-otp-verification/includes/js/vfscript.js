jQuery(document).ready(function () {
    $mo = jQuery;
    if ($mo("div.visual-form-builder-container").length == 0) return;

    $mo("div.visual-form-builder-container").each(function () {
        //fetch the form id for the form
        var formid = $mo(this).attr('id').replace('vfb-form-', '');

        // if formid exists in formDetails - admin has enabled OTP for this form
        if (formid in movfvar.formDetails)
        {
            var checkFlag = false,

                emailId = movfvar.formDetails[formid]['emailkey'],// email field id
                phoneId = movfvar.formDetails[formid]['phonekey'],// phone field id
                field = movfvar.otpType == 0 ? phoneId : emailId, // select field based on otpType is phone or not

                // image HTML templates
                img = "<img src='" + movfvar.imgURL + "'>",
                // messagebox template
                messageBox = '<div  class="vfb-item" ' +
                                    'id="mo_message' + field + '" ' +
                                    'hidden="" ' +
                                    'style="width:100%; background-color: #f7f6f7;' +
                                            'padding: 1em 2em 1em 3.5em; text-align: center;margin-top:3px;">' +
                             '</div>',
                // verify button template
                button = '<li>' +
                            '<input type= "button" ' +
                                    'id="mobutton' + field + '" ' +
                                    'class="vfb-submit" ' +
                                    'value= "' + movfvar.buttontext + '">' +
                            messageBox +
                         '</li>',
                //otp field template
                otpfield = button +
                            '<li class="vfb-item"  ' +
                                 'hidden="" ' +
                                 'id="item-mo_verify_code' + field + '">' +
                                '<label for="mo_verify_code' + field + '" class="vfb-desc">' +
                                    movfvar.fieldText +
                                '</label>' +
                                '<input name="mo_verify_code' + field + '" ' +
                                        'id="mo_verify_code' + field + '" ' +
                                        'value="" ' +
                                        'class="vfb-text  vfb-medium">' +
                             '</li>';
            $mo(otpfield).insertAfter($mo('#' + field + '').parent());

            // handle the css issue when an error message is added
            setTimeout(function () {
                $mo('.flag-container').css('height', $mo('#' + field).css('height'));
                $mo('.country-list').attr('style', 'background-color:#FFFFFF !important');
            }, 300);

            // if the verify button is clicked then
            $mo("#mobutton" + field).click(function () {
                var e = $mo('#' + field).val();
                $mo("#mo_message" + field).empty(),
                $mo("#mo_message" + field).append(img),
                $mo("#mo_message" + field).show(),
                $mo.ajax({
                    url: movfvar.siteURL,
                    type: "POST",
                    data: {
                        user_email: e,
                        user_phone: e,
                        security:movfvar.gnonce,
                        action:movfvar.gaction,
                    },
                    crossDomain: !0, dataType: "json",
                    success: function (o) {
                        if (o.result === "success") {
                            //if otp was sent successfully
                            $mo("#mo_message" + field).empty(),
                                $mo("#mo_message" + field).append(o.message),
                                $mo("#mo_message" + field).css("border-top", "3px solid green"),
                                $mo("#item-mo_verify_code" + field).show();
                        } else {
                            // if otp wasn't sent successfully
                            $mo("#mo_message" + field).empty(),
                                $mo("#mo_message" + field).append(o.message),
                                $mo("#mo_message" + field).css("border-top", "3px solid red"),
                                $mo("#item-mo_verify_code" + field).hide();
                        }
                    },
                    error: function (o) {}
                });
            });

            // onform submit
            $mo(".vfb-form-" + formid).submit(function (e) {
                $mo(".vfb-submit").prop('disabled', false);
                if (!checkFlag) {
                    e.preventDefault();
                    $mo.ajax({
                        url: movfvar.siteURL + "/?option=miniorange-vf-verify-code",
                        type: "POST",
                        data: {
                            otp_token: $mo("#mo_verify_code" + field).val(),
                            sub_field: $mo('#' + field).val(),
                            security:movfvar.vnonce,
                            action:movfvar.vaction,
                        },
                        crossDomain: !0, dataType: "json",
                        success: function (o) {
                            if (o.result === "success") {
                                // submit the form if the verification was successful
                                checkFlag = true;
                                $mo(".vfb-form-" + formid).find("input[name^=vfb-submit]").click();
                            } else {
                                // show an error if verification was not successful
                                $mo("#mo_message" + field).empty(),
                                $mo("#mo_message" + field).append(o.message),
                                $mo("#mo_message" + field).css("border-top", "3px solid red"),
                                $mo("#mo_message" + field).show(),
                                $mo('#' + field).focus();
                            }
                        }, error: function (o) {
                            //show an error message if there's an exception
                            $mo("#mo_message" + field).empty(),
                            $mo("#mo_message" + field).append(o.message),
                            $mo("#mo_message" + field).css("border-top", "3px solid red"),
                            $mo("#mo_message" + field).show(),
                            $mo('#' + field).focus();
                        }
                    });
                }
            });
        }
    });
});


