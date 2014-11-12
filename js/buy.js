// Created by Larry Ullman, www.larryullman.com, @LarryUllman
// Posted as part of the series "Processing Payments with Stripe"
// http://www.larryullman.com/series/processing-payments-with-stripe/
// Last updated February 20, 2013

// This page is intended to be stored in a public "js" directory.

// This function is just used to display error messages on the page.
// Assumes there's an element with an ID of "payment-errors".
function reportError(msg) {
	// Show the error in the form:
	$('#payment-errors').text(msg).addClass('alert alert-error');
	// re-enable the submit button:
	$('#submitBtn').prop('disabled', false);
	return false;
}

// Function handles the Stripe response:
function stripeResponseHandler(status, response) {
	
	// Check for an error:
	if (response.error) {

		reportError(response.error.message);
		
	} else { // No errors, submit the form:

	  var f = $("#payment-form");

	  // Token contains id, last4, and card type:
	  var token = response['id'];
	
	  // Insert the token into the form so it gets submitted to the server
	  f.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
	
	  // Submit the form:
	  f.get(0).submit();

	}
	
} // End of stripeResponseHandler() function.

// Assumes jQuery is loaded!
// Watch for the document to be ready:
$(document).ready(function () {
	
    $('#payment-form').bootstrapValidator({
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            fullName: {
                validators: {
                    notEmpty: {
                        message: 'The Name is required'
                    },
                    stringLength: {
                        min: 3,
                        max: 50,
                        message: 'Please enter your full name'
                    }
                }
            },
            emailAddress: {
                validators: {
                    notEmpty: {
                        message: 'The email is required and cannot be empty'
                    },
                    emailAddress: {
                        message: 'The input is not a valid email address'
                    }
                }
            },
            addressLine1: {
                validators: {
                    notEmpty: {
                        message: 'The address cannot be empty'
                    },
                    stringLength: {
                        min: 5,
                        max: 50,
                        message: 'The address line should be between 5 and 50 characters long'
                    }
                }
            },
            city: {
                validators: {
                    notEmpty: {
                        message: 'The city cannot be left empty'
                    },
                    stringLength: {
                        min: 2,
                        max: 20,
                        message: 'Please enter a valid city'
                    }
                }
            },
            postCode: {
                validators: {
                    notEmpty: {
                        message: 'The postal code cannot be left empty'
                    },
                    stringLength: {
                        min: 4,
                        max: 10,
                        message: 'Please enter a valid postcode'
                    }
                }
            },
            country: {
                validators: {
                    notEmpty: {
                        message: 'The country cannot be left empty'
                    }
                }
            },
            ccNumber: {
                selector: '.card-number',
                validators: {
                    notEmpty: {
                        message: 'The credit card number is required'
                    },
                    creditCard: {
                        message: 'The credit card number is not valid'
                    }
                }
            },
            expMonth: {
                selector: '.card-expiry-month',
                validators: {
                    notEmpty: {
                        message: 'The expiration month is required'
                    },
                    digits: {
                        message: 'The expiration month can contain digits only'
                    },
                    callback: {
                        message: 'Expired',
                        callback: function (value, validator) {
                            value = parseInt(value, 10);
                            var year         = validator.getFieldElements('expYear').val(),
                                currentMonth = new Date().getMonth() + 1,
                                currentYear  = new Date().getFullYear();
                            if (value < 0 || value > 12) {
                                return false;
                            }
                            if (year === '') {
                                return true;
                            }
                            year = parseInt(year, 10);
                            if (year > currentYear || (year === currentYear && value > currentMonth)) {
                                validator.updateStatus('expYear', 'VALID');
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                }
            },
            expYear: {
                selector: '.card-expiry-year',
                validators: {
                    notEmpty: {
                        message: 'The expiration year is required'
                    },
                    digits: {
                        message: 'The expiration year can contain digits only'
                    },
                    callback: {
                        message: 'Expired',
                        callback: function (value, validator) {
                            value = parseInt(value, 10);
                            var month        = validator.getFieldElements('expMonth').val(),
                                currentMonth = new Date().getMonth() + 1,
                                currentYear  = new Date().getFullYear();
                            if (value < currentYear || value > currentYear + 100) {
                                return false;
                            }
                            if (month === '') {
                                return false;
                            }
                            month = parseInt(month, 10);
                            if (value > currentYear || (value === currentYear && month > currentMonth)) {
                                validator.updateStatus('expMonth', 'VALID');
                                return true;
                            } else {
                                return false;
                            }
                        }
                    }
                }
            },
            cvvNumber: {
                selector: '.card-cvc',
                validators: {
                    notEmpty: {
                        message: 'The CVV number is required'
                    },
                    cvv: {
                        message: 'The value is not a valid CVV',
                        creditCardField: 'ccNumber'
                    }
                }
            }
        }
    });

   // $('[data-toggle="confirmation"]').confirmation();
	// Watch for a form submission:
	$("#payment-form").submit(function (event) {
		// Flag variable:
		//var error = false;	
		// Check for errors:
		//if (!error) {		
            var ccNum = $('.card-number').val(), cvcNum = $('.card-cvc').val(), expMonth = $('.card-expiry-month').val(), expYear = $('.card-expiry-year').val();
        // Get the Stripe token:
			Stripe.createToken({
				number: ccNum,
				cvc: cvcNum,
				exp_month: expMonth,
				exp_year: expYear
			}, stripeResponseHandler);
		//}
		// Prevent the form from submitting:
		return false;
	}); // Form submission
	
}); // Document ready.