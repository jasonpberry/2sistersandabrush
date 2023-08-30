// Reservation Form 
$("#reservation-form").validator().on("submit", function (event) {
    if (event.isDefaultPrevented()) {
		formError();
    } else {
        $("#booking-submit").attr('disabled', true).val("Sending...");
        event.preventDefault();
        reservationSubmitForm();
    }
});

function reservationSubmitForm(){
    // Initiate Variables With Form Content
    var fname = $("#rfname").val();
	var lname = $("#rlname").val();
    var email = $("#remail").val();
    var phone = $("#rphone").val();
	var address = $("#raddress").val();
	var zipcode = $("#rzipcode").val();
    var city = $("#rcity").val();
    var select_service = $("#rselect-service").val();
	var date = $("#rdate").val();
	var time = $("#rtime").val();
	var dataString = $('#reservation-form').serialize();
	
    $.ajax({
        type: "POST",
        url: "mail/php/reservation-form-process.php",
        data: dataString,
        success : function(text){
            if (text == "success"){
                reservationFormSuccess();
            } else {
                reservationFormError();
                reservationSubmitMSG(false,text);
            }
        }
    });
}

function reservationFormSuccess(){
    $('.booking-title').html('Thank You!');
    reservationSubmitMSG(true, "Your booking request has been submitted successfully. We will be in touch soon!");
    $("#reservation-form").fadeOut(1000);
    // $("#reservation-form")[0].reset();
	setTimeout(function() { $('#appointment').modal('hide'); 
        $("#reservation-form").fadeIn(1000); 
        $('#msgSubmitRes').hide();
        $('.booking-title').html('Request to Book Us!');
        $("#booking-submit").attr('disabled', false);
        $("#booking-submit").val("Submit");

    }, 8000);
    

}
function reservationFormError(){
    $("#reservation-form").removeClass().addClass('animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
        $(this).removeClass();
    });
}
function reservationSubmitMSG(valid, msg){
    if(valid){
        var msgClasses = "alert alert-success h3 text-center tada animated text-success";
    } else {
        var msgClasses = "alert alert-danger h3 text-center text-danger";
    }
    $("#msgSubmitRes").removeClass().addClass(msgClasses).text(msg).fadeIn(1000);
}



// Contact Form

$("#contactForm").validator().on("submit", function (event) {
    if (event.isDefaultPrevented()) {
		formError();
    } else {
        event.preventDefault();
        submitForm();
    }
});


function submitForm(){
    // Initiate Variables With Form Content
    var fname = $("#fname").val();
	var lname = $("#lname").val();
    var email = $("#email").val();
    var message = $("#message").val();
	var dataString = $('#contactForm').serialize();
	//alert(dataString);

    $.ajax({
        type: "POST",
        url: "mail/php/form-process.php",
        data: dataString,
        success : function(text){
            if (text == "success"){
                formSuccess();
            } else {
                formError();
                submitMSG(false,text);
            }
        }
    });
}

function formSuccess(){
    $("#contactForm")[0].reset();
    submitMSG(true, "Message Submitted!")
}
function formError(){
    $("#contactForm").removeClass().addClass('animated').one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
        $(this).removeClass();
    });
}
function submitMSG(valid, msg){
    if(valid){
        var msgClasses = "h3 text-center tada animated text-success";
    } else {
        var msgClasses = "h3 text-center text-danger";
    }
    $("#msgSubmit").removeClass().addClass(msgClasses).text(msg);
}