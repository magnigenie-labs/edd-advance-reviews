
jQuery(document).ready(function ($) {
    $('.popup-with-form').magnificPopup({
        type: 'inline',
        preloader: false,
        focus: '#name',
        callbacks: {
            beforeOpen: function () {
                if ($(window).width() < 700) {
                    this.st.focus = false;
                } else {
                    $(".jq-ry-rated-group").css('width', '0%');
                    $('.edd-star-rating').val('');
                    $('.edd-form')[0].reset();
                    this.st.focus = '#name';
                }
            }
        }
    });
    $('.update-form').magnificPopup({
        type: 'inline',
        preloader: false,
        focus: '#name',
        callbacks: {
            beforeOpen: function () {
                if ($(window).width() < 700) {
                    this.st.focus = false;
                } else {
                    var rating = $('.update-form').attr('rating');
                    var user_id = $('.update-form').attr('user_id');
                    var charLength = 1000 - $('.update-form').attr('comment').length;

                    $('#edd-comment').after('<input type="hidden" id="userId" value="' + user_id + '">');
                    $('.save-btn').attr('posttype', 'update');
                    $('.edd-star-rating').val($('.update-form').attr('rating'));
                    $('.save-btn').html('Update');
                    $("#charNum").html(charLength);
                    $('#edd-reason').val($('.update-form').attr('reason'));
                    $('#edd-comment').html($('.update-form').attr('comment'));
                    $('#commentId').val($('.update-form').attr('commentId'));
                    $(".jq-ry-rated-group").css('width', rating * 20 + '%');
                    this.st.focus = '#name';
                }
            }
        }
    });
});
jQuery('.table-form').click(function () {
    var thisForm = jQuery(this);
    jQuery.magnificPopup.open({
        items: {src: '#test-form'}
        , type: 'inline',
        callbacks: {
            beforeOpen: function () {
                if (jQuery(window).width() < 700) {
                    this.st.focus = false;
                } else {
                    var rating = thisForm.attr('rating');
                    var user_id = thisForm.attr('user_id');
                    var charLength = 1000 - (thisForm.attr('comment').length);
                    jQuery('#edd-comment').after('<input type="hidden" id="userId" value="' + user_id + '">');
                    jQuery('.edd-star-rating').val(thisForm.attr('rating'));
                    jQuery("#charNum").html(charLength);
                    jQuery('#edd-reason').val(thisForm.attr('reason'));
                    jQuery('#edd-comment').html(thisForm.attr('comment'));
                    jQuery('#commentId').val(thisForm.attr('commentId'));
                    jQuery(".jq-ry-rated-group").css('width', rating * 20 + '%');
                    this.st.focus = '#name';
                }
            }
        }
    });

});

jQuery(function ($) {
    var rating = 0;
    $(".rateyo-readonly-widg").rateYo({
        rating: rating,
        numStars: 5,
        precision: 1,
        halfStar: true,
        minValue: 1,
        maxValue: 5,
        starWidth: "30px",
        multiColor: {
            "startColor": "#FF0000", //RED
            "endColor": "#00FF00"  //GREEN
        }
    }).on("rateyo.change", function (e, data) {
        $('.edd-star-rating').val(data.rating);

    });
});

function countChar(val) {
    var len = val.value.length;
    var totalLength = 1000;
    if (len > totalLength) {
        val.value = val.value.substring(0, totalLength);
    } else {
        jQuery('#charNum').text(totalLength - len);
    }
}
jQuery('.cancel-btn').click(function () {
    jQuery('.edd-form')[0].reset();
    jQuery.magnificPopup.instance.close();
});
jQuery('.cancel-table-btn').click(function () {
    jQuery('.edd-table-form')[0].reset();
    jQuery.magnificPopup.instance.close();
});


function add_reviews() {
    var star_rating = jQuery('.edd-star-rating').val();
    var reason = jQuery('#edd-reason').val();
    var comment = jQuery('#edd-comment').val();
    var commentId = jQuery('#commentId').val();
    var postType = jQuery('.save-btn').attr('posttype');
    var dataString = jQuery('.edd-form').serialize() + "&action=edd_add_reviews" + "&posttype=" + postType;

    if (star_rating === "" || star_rating === '0') {
        jQuery('.edd-error-msg').text('please give your rating !').show().delay(3000).fadeOut();
        return false;
    } else if (reason === "" && star_rating < 5) {
        jQuery('#edd-reason').focus();
        jQuery('.edd-error-msg').text('please give reason for your rating !').show().delay(3000).fadeOut();
        return false;
    } else if (comment === "" && star_rating < 3) {
        jQuery('#edd-comment').focus();
        jQuery('.edd-error-msg').text('please describe the reason for your rating !').show().delay(3000).fadeOut();
        return false;
    } else if (comment !== "" && jQuery('#edd-comment').val().length < 30) {
        jQuery('#edd-comment').focus();
        jQuery('.edd-error-msg').text('comment should be minimum of 30 characters !').show().delay(3000).fadeOut();
        return false;
    } else {
        jQuery('.main-loader').show();
        jQuery('.edd-loader').show();
        jQuery.post(eddReviews.ajaxUrl, dataString, function (response) {
            if (response !== "") {
                if (response['status'] === "update") {
                    var userId = jQuery('#userId').val();
                    jQuery('#rated-' + userId).html(response.rating);
                    jQuery('#nickname-' + userId).html(response.nickname);
                    jQuery('#date-' + userId).html(response.date);
                    jQuery('#reason-' + userId).html(response.reason);
                    jQuery('#comment-' + userId).html(response.comment);

                    jQuery('.update-form').attr("rating", response.rating);
                    jQuery('.update-form').attr("reason", response.reason);
                    jQuery('.update-form').attr("comment", response.comment);
                } else {
                    jQuery('.edd-reviews-btn').remove();
                    location.reload();
//                    jQuery('.edd-reviews-btn').after(response.html);
//                    jQuery('.edd-reviews-btn').remove();
                }
            }
            jQuery('.main-loader').hide();
            jQuery('.edd-loader').hide();
            jQuery.magnificPopup.instance.close();

        }, 'json');
        return false;
    }

}


function add_table_reviews() {
    var star_rating = jQuery('.edd-star-rating').val();
    var reason = jQuery('#edd-reason').val();
    var comment = jQuery('#edd-comment').val();
    var commentId = jQuery('#commentId').val();
    var postType = 'update';
    var dataString = jQuery('.edd-table-form').serialize() + "&action=edd_add_reviews" + "&posttype=" + postType;

    if (star_rating === "" || star_rating === '0') {
        jQuery('.edd-error-msg').text('please give your rating !').show().delay(3000).fadeOut();
        return false;
    } else if (reason === "" && star_rating < 5) {
        jQuery('#edd-reason').focus();
        jQuery('.edd-error-msg').text('please give reason for your rating !').show().delay(3000).fadeOut();
        return false;
    } else if (comment === "" && star_rating < 3) {
        jQuery('#edd-comment').focus();
        jQuery('.edd-error-msg').text('please describe the reason for your rating !').show().delay(3000).fadeOut();
        return false;
    } else if (comment !== "" && jQuery('#edd-comment').val().length < 30) {
        jQuery('#edd-comment').focus();
        jQuery('.edd-error-msg').text('comment should be minimum of 30 characters !').show().delay(3000).fadeOut();
        return false;
    } else {
        jQuery('.main-loader').show();
        jQuery('.edd-loader').show();
        jQuery.post(eddReviews.ajaxUrl, dataString, function (response) {
            if (response !== "") {
                if (response['status'] === "update") {
                    var comment = response.comment;

                    jQuery('.table-rating-' + commentId).html(response.rating);
                    jQuery('.table-date-' + commentId).html(response.date);
                    jQuery('.table-reason-' + commentId).html(response.reason);
                    jQuery('.table-comment-' + commentId).html(comment.substring(0,15)+"...");



                    jQuery('.table-form').attr("rating", response.rating);
                    jQuery('.table-form').attr("reason", response.reason);
                    jQuery('.table-form').attr("comment", response.comment);
                }
            }
            jQuery('.main-loader').hide();
            jQuery('.edd-loader').hide();
            jQuery.magnificPopup.instance.close();

        }, 'json');
        return false;
    }

}

