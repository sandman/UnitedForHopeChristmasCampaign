<?php # buy.php 
// Uses sessions to test for duplicate submissions:
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width"/>
	<title>United For Hope Christmas Fundraiser - Gift Light</title>
    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        .full {
          background: url(images/second-content.jpg) no-repeat center center fixed; 
          -webkit-background-size: cover;
          -moz-background-size: cover;
          -o-background-size: cover;
          background-size: cover;
        }
        
        .col-md-6 {
            background-color: #fff;
        }
        
        .submit-button {
          margin-top: 5px;
        }
        .list-group-horizontal .list-group-item {
            display: inline-block;
        }
        .list-group-horizontal .list-group-item {
            margin-bottom: 0;
            margin-left:-4px;
            margin-right: 0;
        }
        .list-group-horizontal .list-group-item:first-child {
            border-top-right-radius:0;
            border-bottom-left-radius:4px;
        }
        .list-group-horizontal .list-group-item:last-child {
            border-top-right-radius:4px;
            border-bottom-left-radius:0;
        }
    </style>
</head>
<body class="full"><?php
// This page is used to make a purchase.

// Every page needs the configuration file:
require('includes/config.inc.php');

// Uses sessions to test for duplicate submissions:
//session_start();

// Set the Stripe key:
// Uses STRIPE_PUBLIC_KEY from the config file.
echo '<script type="text/javascript">Stripe.setPublishableKey("' . STRIPE_PUBLIC_KEY . '");</script>';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $cart = array();
    $_SESSION['cart'] = $cart;
    $amt_total = 0;
    echo "The GET is $_GET";
    var_dump($_GET);
    $numItems = 0;
    if (isset($_GET['light'])) {
      $amt_total += 1000;
      $_SESSION['cart'][1] = "LIGHT (EUR 10)";
      $numItems++;
    }
    if (isset($_GET['water'])) {
      $amt_total += 2500;
      $_SESSION['cart'][2]  = "WATER (EUR 25)";
      $numItems++;
    }
    if (isset($_GET['health'])) {
      $amt_total += 5000;
      $_SESSION['cart'][3] = "HEALTH (EUR 50)";
      $numItems++;
    }
    if (isset($_GET['future'])) {
      $amt_total += 10000;
      $_SESSION['cart'][4] = "A FUTURE (EUR 100)";
      $numItems++;
    }
    if (isset($_GET['dignity'])) {
      $amt_total += 20000;
      $_SESSION['cart'][5] = "DIGNITY (EUR 200)";
      $numItems++;
    }
    $_SESSION['amt_total'] = $amt_total;
    $amt_eur = $_SESSION['amt_total']/100;
    $fee = $amt_eur * 0.029 + 0.30;
    $amt_eur_t = $amt_eur + $fee;
    $_SESSION['numItems'] = $numItems;
    $_SESSION['amt_eur'] = $amt_eur;
    $_SESSION['fee'] = $fee;

}

// Check for a form submission:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// Stores errors:
	$errors = array();
	
	// Need a payment token:
	if (isset($_POST['stripeToken'])) {
        
        echo "The POST is $_POST";
        var_dump($_POST);
		
		$token = $_POST['stripeToken'];
        $email  = $_POST['emailAddress'];
        $nameOnCard = $_POST['Name'];
		$address1 = $_POST['address-line1'];
        $address2 = $_POST['address-line2'];
        $city = $_POST['city'];
        $region = $_POST['region'];
        $postcode = $_POST['postal-code'];
        $country = $_POST['country'];
        $amount = $_SESSION['amt_total']; //$_POST['amount'];
		// Check for a duplicate submission, just in case:
		// Uses sessions, you could use a cookie instead.
		if (isset($_SESSION['token']) && ($_SESSION['token'] == $token)) {
			$errors['token'] = 'You have apparently resubmitted the form. Please do not do that.';
		} else { // New submission.
			$_SESSION['token'] = $token;
		}		
		
	} else {
		$errors['token'] = 'The order cannot be processed. Please make sure you have JavaScript enabled and try again.';
	}
	
	// Validate other form data!

	// If no errors, process the order:
	if (empty($errors)) {
		
		// create the charge on Stripe's servers - this will charge the user's card
		try {
			
			// Include the Stripe library:
			require_once('includes/stripe/lib/Stripe.php');

			// set your secret key: remember to change this to your live secret key in production
			// see your keys here https://manage.stripe.com/account
			Stripe::setApiKey(STRIPE_PRIVATE_KEY);
            $customer = Stripe_Customer::create(array(
                "email" => $email,
                "card" => $token, // obtained with Stripe.js
                "description" => $nameOnCard
                )
            );
            
            if($customer->id != null)
            {
                // Charge the order:
                $charge = Stripe_Charge::create(array(
                    "customer" => $customer->id, // Linking to Customer
                    "amount" => $amount, // amount in cents, again
                    "currency" => "eur",
                  //  "card" => $token,
                    "description" => $nameOnCard
                    )
                );

                // Check that it was paid:
                if ($charge->paid == true) {

                    // Store the order in the database.
                    // Send the email.
                    // Celebrate!
                    $host = "localhost";
                    $user = "root";
                    $pass = "sandybits";
                    $dbname = "mysql";
                                        
                    # the data we want to insert
                    $data = array( 'name' => $nameOnCard, 'email' => $email, 'customer_id' => $customer->id, 'amount' => $amount, 'paid' => true , 'address1' => $address1, 'address2' => $address2, 'city' => $city, 'region' => $region, 'postcode' => $postcode, 'country' => $country );
                    
                    try {
                        # MySQL with PDO_MYSQL
                        $DBH = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
                        $DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
                        $STH = $DBH->prepare("INSERT INTO stripe_user_info (name, email, customer_id, amount, paid, address1, address2, city, region, postcode, country) VALUES (:name, :email, :customer_id, :amount, :paid, :address1, :address2, :city, :region, :postcode, :country)");
                        $STH->execute((array) $data);
                        $DBH = null;
                    }
                    catch(PDOException $e) {
                        echo $e->getMessage();
                    }
                    
                    header("Location: http://localhost/UnitedForHope/httpdocs/givehope/success.htm");

                } 
                else 
                { // Charge was not paid!	
                    echo "<div class='alert alert-danger'><h4>Payment System Error!</h4>Your payment could NOT be processed (i.e., you have not been charged) because the payment system rejected the transaction. You can try again or use another card. If you face further problems, do drop us a line at info@unitedforhope.org</div>";
                }
            }
            else { // Customer was not created!	
                echo "<div class='alert alert-danger'><h4>Could not create Customer! Something is wrong here..</h4></div>";
            }			
		} catch (Stripe_CardError $e) {
		    // Card was declined.
			$e_json = $e->getJsonBody();
			$err = $e_json['error'];
			$errors['stripe'] = $err['message'];
		} catch (Stripe_ApiConnectionError $e) {
		    // Network problem, perhaps try again.
		} catch (Stripe_InvalidRequestError $e) {
		    // You screwed up in your programming. Shouldn't happen!
		} catch (Stripe_ApiError $e) {
		    // Stripe's servers are down!
		} catch (Stripe_CardError $e) {
		    // Something else that's not the customer's fault.
		}

	} // A user form submission error occurred, handled below.
	//require-validation data-cc-on-file="false" 
} // Form submission.

?>
    <div class="container">
    <div class='row'>
        <div class='col-md-3'></div>
        <div class='col-md-6'>
            <div class="row" style="padding-left:20px padding-right:20px"> 
                <h2>The Gift Hope Christmas Shopping List</h2>
                <h5>This is a secure page.</h5>
                <h5 class="alert alert-warning">You have chosen to GIFT the following this Christmas: 
                    <?php 
                    foreach($cart as $items) {
                      echo "<ul><li>". $items ."</li></ul>";
                    }
                    echo "<h5>The Total amount is EUR ". $_SESSION['amt_eur'] . " + EUR " . $_SESSION['fee'] . " Transaction fee * </h5> 
                    <p>* A Transaction fee of 2.9% + 30 cents on the total amount is levied by the payment gateway.</p>";  ?> <br />
                If you wish to proceed, please enter your card details below.</h5>
                <h5>Not what you want to buy? <a type="button" class="btn btn-default" href='index.php#portfolio'>Go back</a></h5>
                
            </div>
            <div class="row" style="padding-top:20px">
                <form accept-charset="UTF-8" action="buy.php" class="require-validation" data-cc-on-file="false" data-stripe-publishable-key="pk_test_IOwaVhL2sF2ND7UlFJw6ISeu" id="payment-form" method="POST">
                <input name="amount" type="hidden" value=$amt_total />
                <div style="margin:0;padding:0;display:inline">
                <?php // Show PHP errors, if they exist:
                    if (isset($errors) && !empty($errors) && is_array($errors)) {
                        echo '<div class="alert alert-danger" role="alert"><h4>Error!</h4>The following error(s) occurred:<ul>';
                        foreach ($errors as $e) {
                            echo "<li>$e</li>";
                        }
                        echo '</ul></div>';	
                    }?>
                  <div id="payment-errors"></div>
                  <div style="margin:0;padding:0;display:inline">

                    <div class='form-row'>
                      <div class='col-xs-12 form-group required'>
                        <label class='control-label'>Full Name</label>
                        <input class='NameOnCard form-control' name='Name' size='20' type='text'>
                      </div>
                    </div>
                    <div class='form-row'>
                      <div class='col-xs-12 form-group has-feedback required'>
                        <label class='control-label'>Email address</label>
                        <input class='emailAddress form-control' name='emailAddress' placeholder='someone@example.com' size='20' type='email'>
                      </div>
                    </div>
                    <!-- address-line1 input-->
                    <div class='form-row'>
                        <div class='col-xs-12 form-group required'>
                            <label class='control-label'>Complete Address</label>
                            <input name="address-line1" size="40" type="text" placeholder="Street address, P.O. box, company name, c/o"
                            class="address-line1 form-control">
                        </div>
                    </div>
                    <!-- address-line2 input-->
                    <div class='form-row'>
                        <div class='col-xs-12 form-group required'>
                            <input name="address-line2" size="40" type="text" placeholder="Apartment, suite , unit, building, floor, etc."
                            class="address-line2 form-control">
                        </div>
                    </div>
                    <!-- city input-->
                    <div class='form-row'>
                        <div class='col-xs-12 form-group required'>
                            <label class="control-label">City</label>
                            <input name="city" size="20" type="text" placeholder="City/Town" class="city form-control">
                        </div>
                    </div>
                    <!-- region input-->
                    <div class='form-row'>
                        <div class='col-xs-12 form-group'>
                            <label class="control-label">State</label>
                            <input name="region" type="text" placeholder="State / Province / Region"
                            class="region form-control">
                        </div>
                    </div>
                    <!-- postal-code input-->
                    <div class='form-row'>
                        <div class='col-xs-4 form-group required'>
                            <label class="control-label">Zip / Postal Code</label>
                            <input id="postal-code" name="postal-code" type="text" placeholder="zip or postal code"
                            class="postal-code form-control">
                        </div>
                    </div>
                    <!-- country select -->
                        <div class='col-xs-8 form-group required'>
                            <label class="control-label">Country</label>
                            <select id="country" name="country" class="country form-control">
                                <option value="" selected="selected">(please select a country)</option>
                                <option value="AF">Afghanistan</option>
                                <option value="AL">Albania</option>
                                <option value="DZ">Algeria</option>
                                <option value="AS">American Samoa</option>
                                <option value="AD">Andorra</option>
                                <option value="AO">Angola</option>
                                <option value="AI">Anguilla</option>
                                <option value="AQ">Antarctica</option>
                                <option value="AG">Antigua and Barbuda</option>
                                <option value="AR">Argentina</option>
                                <option value="AM">Armenia</option>
                                <option value="AW">Aruba</option>
                                <option value="AU">Australia</option>
                                <option value="AT">Austria</option>
                                <option value="AZ">Azerbaijan</option>
                                <option value="BS">Bahamas</option>
                                <option value="BH">Bahrain</option>
                                <option value="BD">Bangladesh</option>
                                <option value="BB">Barbados</option>
                                <option value="BY">Belarus</option>
                                <option value="BE">Belgium</option>
                                <option value="BZ">Belize</option>
                                <option value="BJ">Benin</option>
                                <option value="BM">Bermuda</option>
                                <option value="BT">Bhutan</option>
                                <option value="BO">Bolivia</option>
                                <option value="BA">Bosnia and Herzegowina</option>
                                <option value="BW">Botswana</option>
                                <option value="BV">Bouvet Island</option>
                                <option value="BR">Brazil</option>
                                <option value="IO">British Indian Ocean Territory</option>
                                <option value="BN">Brunei Darussalam</option>
                                <option value="BG">Bulgaria</option>
                                <option value="BF">Burkina Faso</option>
                                <option value="BI">Burundi</option>
                                <option value="KH">Cambodia</option>
                                <option value="CM">Cameroon</option>
                                <option value="CA">Canada</option>
                                <option value="CV">Cape Verde</option>
                                <option value="KY">Cayman Islands</option>
                                <option value="CF">Central African Republic</option>
                                <option value="TD">Chad</option>
                                <option value="CL">Chile</option>
                                <option value="CN">China</option>
                                <option value="CX">Christmas Island</option>
                                <option value="CC">Cocos (Keeling) Islands</option>
                                <option value="CO">Colombia</option>
                                <option value="KM">Comoros</option>
                                <option value="CG">Congo</option>
                                <option value="CD">Congo, the Democratic Republic of the</option>
                                <option value="CK">Cook Islands</option>
                                <option value="CR">Costa Rica</option>
                                <option value="CI">Cote d'Ivoire</option>
                                <option value="HR">Croatia (Hrvatska)</option>
                                <option value="CU">Cuba</option>
                                <option value="CY">Cyprus</option>
                                <option value="CZ">Czech Republic</option>
                                <option value="DK">Denmark</option>
                                <option value="DJ">Djibouti</option>
                                <option value="DM">Dominica</option>
                                <option value="DO">Dominican Republic</option>
                                <option value="TP">East Timor</option>
                                <option value="EC">Ecuador</option>
                                <option value="EG">Egypt</option>
                                <option value="SV">El Salvador</option>
                                <option value="GQ">Equatorial Guinea</option>
                                <option value="ER">Eritrea</option>
                                <option value="EE">Estonia</option>
                                <option value="ET">Ethiopia</option>
                                <option value="FK">Falkland Islands (Malvinas)</option>
                                <option value="FO">Faroe Islands</option>
                                <option value="FJ">Fiji</option>
                                <option value="FI">Finland</option>
                                <option value="FR">France</option>
                                <option value="FX">France, Metropolitan</option>
                                <option value="GF">French Guiana</option>
                                <option value="PF">French Polynesia</option>
                                <option value="TF">French Southern Territories</option>
                                <option value="GA">Gabon</option>
                                <option value="GM">Gambia</option>
                                <option value="GE">Georgia</option>
                                <option value="DE">Germany</option>
                                <option value="GH">Ghana</option>
                                <option value="GI">Gibraltar</option>
                                <option value="GR">Greece</option>
                                <option value="GL">Greenland</option>
                                <option value="GD">Grenada</option>
                                <option value="GP">Guadeloupe</option>
                                <option value="GU">Guam</option>
                                <option value="GT">Guatemala</option>
                                <option value="GN">Guinea</option>
                                <option value="GW">Guinea-Bissau</option>
                                <option value="GY">Guyana</option>
                                <option value="HT">Haiti</option>
                                <option value="HM">Heard and Mc Donald Islands</option>
                                <option value="VA">Holy See (Vatican City State)</option>
                                <option value="HN">Honduras</option>
                                <option value="HK">Hong Kong</option>
                                <option value="HU">Hungary</option>
                                <option value="IS">Iceland</option>
                                <option value="IN">India</option>
                                <option value="ID">Indonesia</option>
                                <option value="IR">Iran (Islamic Republic of)</option>
                                <option value="IQ">Iraq</option>
                                <option value="IE">Ireland</option>
                                <option value="IL">Israel</option>
                                <option value="IT">Italy</option>
                                <option value="JM">Jamaica</option>
                                <option value="JP">Japan</option>
                                <option value="JO">Jordan</option>
                                <option value="KZ">Kazakhstan</option>
                                <option value="KE">Kenya</option>
                                <option value="KI">Kiribati</option>
                                <option value="KP">Korea, Democratic People's Republic of</option>
                                <option value="KR">Korea, Republic of</option>
                                <option value="KW">Kuwait</option>
                                <option value="KG">Kyrgyzstan</option>
                                <option value="LA">Lao People's Democratic Republic</option>
                                <option value="LV">Latvia</option>
                                <option value="LB">Lebanon</option>
                                <option value="LS">Lesotho</option>
                                <option value="LR">Liberia</option>
                                <option value="LY">Libyan Arab Jamahiriya</option>
                                <option value="LI">Liechtenstein</option>
                                <option value="LT">Lithuania</option>
                                <option value="LU">Luxembourg</option>
                                <option value="MO">Macau</option>
                                <option value="MK">Macedonia, The Former Yugoslav Republic of</option>
                                <option value="MG">Madagascar</option>
                                <option value="MW">Malawi</option>
                                <option value="MY">Malaysia</option>
                                <option value="MV">Maldives</option>
                                <option value="ML">Mali</option>
                                <option value="MT">Malta</option>
                                <option value="MH">Marshall Islands</option>
                                <option value="MQ">Martinique</option>
                                <option value="MR">Mauritania</option>
                                <option value="MU">Mauritius</option>
                                <option value="YT">Mayotte</option>
                                <option value="MX">Mexico</option>
                                <option value="FM">Micronesia, Federated States of</option>
                                <option value="MD">Moldova, Republic of</option>
                                <option value="MC">Monaco</option>
                                <option value="MN">Mongolia</option>
                                <option value="MS">Montserrat</option>
                                <option value="MA">Morocco</option>
                                <option value="MZ">Mozambique</option>
                                <option value="MM">Myanmar</option>
                                <option value="NA">Namibia</option>
                                <option value="NR">Nauru</option>
                                <option value="NP">Nepal</option>
                                <option value="NL">Netherlands</option>
                                <option value="AN">Netherlands Antilles</option>
                                <option value="NC">New Caledonia</option>
                                <option value="NZ">New Zealand</option>
                                <option value="NI">Nicaragua</option>
                                <option value="NE">Niger</option>
                                <option value="NG">Nigeria</option>
                                <option value="NU">Niue</option>
                                <option value="NF">Norfolk Island</option>
                                <option value="MP">Northern Mariana Islands</option>
                                <option value="NO">Norway</option>
                                <option value="OM">Oman</option>
                                <option value="PK">Pakistan</option>
                                <option value="PW">Palau</option>
                                <option value="PA">Panama</option>
                                <option value="PG">Papua New Guinea</option>
                                <option value="PY">Paraguay</option>
                                <option value="PE">Peru</option>
                                <option value="PH">Philippines</option>
                                <option value="PN">Pitcairn</option>
                                <option value="PL">Poland</option>
                                <option value="PT">Portugal</option>
                                <option value="PR">Puerto Rico</option>
                                <option value="QA">Qatar</option>
                                <option value="RE">Reunion</option>
                                <option value="RO">Romania</option>
                                <option value="RU">Russian Federation</option>
                                <option value="RW">Rwanda</option>
                                <option value="KN">Saint Kitts and Nevis</option>
                                <option value="LC">Saint LUCIA</option>
                                <option value="VC">Saint Vincent and the Grenadines</option>
                                <option value="WS">Samoa</option>
                                <option value="SM">San Marino</option>
                                <option value="ST">Sao Tome and Principe</option>
                                <option value="SA">Saudi Arabia</option>
                                <option value="SN">Senegal</option>
                                <option value="SC">Seychelles</option>
                                <option value="SL">Sierra Leone</option>
                                <option value="SG">Singapore</option>
                                <option value="SK">Slovakia (Slovak Republic)</option>
                                <option value="SI">Slovenia</option>
                                <option value="SB">Solomon Islands</option>
                                <option value="SO">Somalia</option>
                                <option value="ZA">South Africa</option>
                                <option value="GS">South Georgia and the South Sandwich Islands</option>
                                <option value="ES">Spain</option>
                                <option value="LK">Sri Lanka</option>
                                <option value="SH">St. Helena</option>
                                <option value="PM">St. Pierre and Miquelon</option>
                                <option value="SD">Sudan</option>
                                <option value="SR">Suriname</option>
                                <option value="SJ">Svalbard and Jan Mayen Islands</option>
                                <option value="SZ">Swaziland</option>
                                <option value="SE">Sweden</option>
                                <option value="CH">Switzerland</option>
                                <option value="SY">Syrian Arab Republic</option>
                                <option value="TW">Taiwan, Province of China</option>
                                <option value="TJ">Tajikistan</option>
                                <option value="TZ">Tanzania, United Republic of</option>
                                <option value="TH">Thailand</option>
                                <option value="TG">Togo</option>
                                <option value="TK">Tokelau</option>
                                <option value="TO">Tonga</option>
                                <option value="TT">Trinidad and Tobago</option>
                                <option value="TN">Tunisia</option>
                                <option value="TR">Turkey</option>
                                <option value="TM">Turkmenistan</option>
                                <option value="TC">Turks and Caicos Islands</option>
                                <option value="TV">Tuvalu</option>
                                <option value="UG">Uganda</option>
                                <option value="UA">Ukraine</option>
                                <option value="AE">United Arab Emirates</option>
                                <option value="GB">United Kingdom</option>
                                <option value="US">United States</option>
                                <option value="UM">United States Minor Outlying Islands</option>
                                <option value="UY">Uruguay</option>
                                <option value="UZ">Uzbekistan</option>
                                <option value="VU">Vanuatu</option>
                                <option value="VE">Venezuela</option>
                                <option value="VN">Viet Nam</option>
                                <option value="VG">Virgin Islands (British)</option>
                                <option value="VI">Virgin Islands (U.S.)</option>
                                <option value="WF">Wallis and Futuna Islands</option>
                                <option value="EH">Western Sahara</option>
                                <option value="YE">Yemen</option>
                                <option value="YU">Yugoslavia</option>
                                <option value="ZM">Zambia</option>
                                <option value="ZW">Zimbabwe</option>
                            </select>
                        </div>
                    </div>
                    <div class='form-row'>
                      <div class='col-xs-12 form-group card required'>
                        <label class='control-label'>Card Number</label>
                        <input autocomplete='off' class='card-number form-control' placeholder='•••• •••• •••• ••••' size='20' type='text'>
                      </div>
                    </div>
                    <div class='form-row'>
                      <div class='col-xs-4 form-group cvc required'>
                        <label class='control-label'>CVC</label>
                        <input autocomplete='off' class='card-cvc form-control' placeholder='ex. 311' size='4' type='text'>
                      </div>
                      <div class='col-xs-4 form-group expiration required'>
                        <label class='control-label'>Expiration</label>
                        <input class='card-expiry-month form-control' placeholder='MM' size='2' type='text'>
                      </div>
                      <div class='col-xs-4 form-group expiration required'>
                        <label class='control-label'> </label>
                        <input class='card-expiry-year form-control' placeholder='YYYY' size='4' type='text'>
                      </div>
                    </div>
                    <div class='form-row'>
                      <div class='col-md-12'>
                        <div class='form-control total btn btn-info'>
                          Total:
                          <span class='amount'>EUR </span>
                        </div>
                      </div>
                    </div>
                    <div class='form-row'>
                      <div class='col-md-12 form-group'>
                        <button class='form-control btn btn-primary submit-button' id='gift' type='submit' data-loading-text="Processing...">Pay</button>
                      </div>
                    </div>
                    <div class='form-row'>
                      <div class='col-md-12 error form-group hide'>
                        <div class='alert-danger alert'>
                          Please correct the errors and try again.
                        </div>
                      </div>
                    </div>
                </form>
            </div>
            </div>
            <div class='col-md-3'></div>
        </div>
        </div>

    <script src="js/jquery-1.11.0.js"></script>
    <script src="js/buy.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

</body>
</html>