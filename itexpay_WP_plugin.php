<?php

/**
 * @package ItexPay WP_Plugin
 * @version 1.0.2
 */
/*
Plugin Name: ItexPay WP_Plugin
Plugin URI: http://wordpress.org/plugins/itexpay_plugin_WP/
Description: This Plugin is not just for web acquiring. You can now access all our Itex services in one click! Thanks to ITEX | WORDPRESS
Author: ITEX Integrated Services
Version: 1.0.2
Author URI: https://iisysgroup.com
*/

// Declaring $wpdb as global and using it to execute an SQL query statement that returns a PHP object
global $wpdb;


// Set tables names
$GLOBALS['table'] = $wpdb->prefix . 'itexpay_keys';
$GLOBALS['table1'] = $wpdb->prefix . 'itexpay_settings';
$table3 = $wpdb->prefix . 'itexpay_test_transactions';
$table4 = $wpdb->prefix . 'itexpay_live_transactions';


$charset_collate = $wpdb->get_charset_collate();

// Create ItexPay database table for api keys  ----------------------------------------------
$query =  "CREATE TABLE IF NOT EXISTS  ".$GLOBALS['table']." (
            id INT(11) AUTO_INCREMENT,
            mode VARCHAR(255),
            public_key VARCHAR(255),
            private_key VARCHAR(255),
            encryption_key BINARY(255),
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
            )$charset_collate;";

// Execute the query
$wpdb->query( $query );


$rowTest = $wpdb->get_results( "SELECT COUNT(*) as num_rows FROM ".$GLOBALS['table']." WHERE mode = 'test'", OBJECT );
if ($rowTest[0]->num_rows == 0) {
    $wpdb->insert(
        $GLOBALS['table'],
        array(
            'mode' => 'test',
            'public_key' => '',
            'private_key' => '',
            'encryption_key' => '',
        ),
    );
}


$rowLive = $wpdb->get_results( "SELECT COUNT(*) as num_rows FROM ".$GLOBALS['table']." WHERE mode = 'live'", OBJECT );
if ($rowLive[0]->num_rows == 0) {
    $wpdb->insert(
        $GLOBALS['table'],
        array(
            'mode' => 'live',
            'public_key' => '',
            'private_key' => '',
            'encryption_key' => '',
        ),
    );
}
//  ---------------------------------------------------------         Create ItexPay database table for api keys


// Create ItexPay database table for admin settings  ----------------------------------------------
$query1 =  "CREATE TABLE IF NOT EXISTS  ".$GLOBALS['table1']." (
            id INT(11) AUTO_INCREMENT,
            current_mode VARCHAR(255),
            percentage_charges VARCHAR(255),
            threshold VARCHAR(255),
            additional_charges VARCHAR(255),
            price_cap VARCHAR(255),
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
            )$charset_collate;";

// Execute the query
$wpdb->query( $query1 );

$rowSettings = $wpdb->get_results( "SELECT COUNT(*) as num_rows FROM ".$GLOBALS['table1']." ", OBJECT );
if ($rowSettings[0]->num_rows == 0) {
    $wpdb->insert(
        $GLOBALS['table1'],
        array(
            'current_mode' => 'test',
            'percentage_charges' => 1.4,
            'threshold' => 2500,
            'additional_charges' => 100,
            'price_cap' => 2000,
        ),
    );
}
// ------------------------------------------------------------------  Create ItexPay database table for admin settings

// create ItexPay transaction history test and live tables -----------------------------------------------------
$query02 =  "CREATE TABLE IF NOT EXISTS  ".$table3." (
            id INT(11) AUTO_INCREMENT,
            email VARCHAR(255),
            description VARCHAR(255),
            amount VARCHAR(255),
            status VARCHAR(255),
            transaction_reference VARCHAR(255),
            payment_id VARCHAR(255),
            linking_reference VARCHAR(255),
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
            )$charset_collate;";
// Execute the query
$wpdb->query( $query02 );

$query03 =  "CREATE TABLE IF NOT EXISTS  ".$table4." (
            id INT(11) AUTO_INCREMENT,
            email VARCHAR(255),
            description VARCHAR(255),
            amount VARCHAR(255),
            status VARCHAR(255),
            transaction_reference VARCHAR(255),
            payment_id VARCHAR(255),
            linking_reference VARCHAR(255),
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
            )$charset_collate;";
// Execute the query
$wpdb->query( $query03 );

// --------------------------------------------------  create ItexPay transaction history test and live tables

// get and set the ItexPay Settings from database -------------------------------------------------------------
$esults2 = $wpdb->get_results( "SELECT * FROM ".$GLOBALS['table1']." WHERE id = 1", OBJECT );
$jsonddu = json_encode($esults2);
$jsondu = json_decode($jsonddu, true);
$GLOBALS['current_mode'] = $jsondu[0]['current_mode'];
if ($GLOBALS['current_mode'] == 'test'){
    $GLOBALS['table2'] = $table3;
}
elseif ($GLOBALS['current_mode'] == 'live'){
    $GLOBALS['table2'] = $table4;
}
$percentage_charges = $jsondu[0]['percentage_charges'];
$threshold = $jsondu[0]['threshold'];
$additional_charges = $jsondu[0]['additional_charges'];
$price_cap = $jsondu[0]['price_cap'];

$results2 = $wpdb->get_results( "SELECT * FROM ".$GLOBALS['table']." WHERE mode = '".$GLOBALS['current_mode']."' ", OBJECT );
$jsondd = json_encode($results2);
$jsond = json_decode($jsondd, true);
$public_key = $jsond[0]['public_key'];
$private_key = $jsond[0]['private_key'];
$encryption_key = $jsond[0]['encryption_key'];

$GLOBALS['public_key'] = urldecode(filter_var($public_key, FILTER_SANITIZE_STRING));
$GLOBALS['private_key'] = urldecode(filter_var($private_key, FILTER_SANITIZE_STRING));
$GLOBALS['encryption_key'] = urldecode(filter_var($encryption_key, FILTER_SANITIZE_STRING));

$GLOBALS['percentage_charges'] = $percentage_charges;
$GLOBALS['threshold'] = $threshold;
$GLOBALS['additional_charges'] = $additional_charges;
$GLOBALS['price_cap'] = $price_cap;
// ---------------------------------------------------------------     get and set the ItexPay Settings from database


// Update transactions mode
if(isset($_GET['selectedMode'])){

    $mode = $_GET['selectedMode'];
    $wpdb->update(
        $GLOBALS['table1'],
        array(
            'current_mode' => $mode,
        ),
        array('id' => 1),
    );
}

// process user payment form and fetch the authorization url
if (isset($_POST['amount'])) {
    $amount = $_POST['amount'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $description = $_POST['description'];

    $publickey = $GLOBALS['public_key'];

    $authorization_urls = getauthoriztionurl($publickey, $amount, $email, $first_name, $last_name, $phone_number, $description);
        if ($authorization_urls) {
            //    echo json_decode($body) -> authorization_url;
            paymentForm($authorization_urls);
        } else {
            echo '<script>alert("Something went wrong. Payment gateway modal could not be generated. Make sure " +
                                    "you have set a valid Api Keys and you have a strong Internet Connection. Please try again!")</script>';
           // paymentForm("https://toosoft");
        }


}

// process callback for new transactions
if (isset($_GET['mail'], $_GET['code'])) {

    $mailpaymentid = $_GET['mail'];

    $str = $mailpaymentid;
    $arr = explode("?",$str);

    $mail = $arr[0];
    $arr1 = $arr[1];

    $arr2 = explode("=",$arr1);
//l
    $paymentid = $arr2[1];

    $desc = $_GET['desc'];
    $linkingreference = $_GET['linkingreference'];
    $code = $_GET['code'];

    $publickey = $GLOBALS['public_key'];
    $privatekey = $GLOBALS['private_key'];
    $encrkey = $GLOBALS['encryption_key'];

    $response =
        wp_remote_get( 'https://staging.itexpay.com/api/v1/transaction/charge/status?publickey='.$publickey.'&paymentid='.$paymentid.'
');

    $body = wp_remote_retrieve_body( $response );
    $decodeBody = json_decode($body, true);

    $fetchedPaymentid = $decodeBody['transaction']['paymentid'];
    $fetchedLinkingreference = $decodeBody['transaction']['linkingreference'];
    $fetchedAmount = $decodeBody['order']['amount'];
    $fetchedMessage = $decodeBody['message'];
    $fetchedCode = $decodeBody['code'];

    function generateRandomString($length = 20) {
//        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return "ITXPAY|WP|".$randomString;
    }
    $tRef = generateRandomString(20);

    // Verify transaction before updating database
    if ($fetchedPaymentid == $paymentid &&  $fetchedLinkingreference == $linkingreference) {

        // avoid duplicate; check if transaction does not exist then insert new entry
        $rowTransactionsExist = $wpdb->get_results( "SELECT COUNT(*) as num_rows FROM ".$GLOBALS['table2']."  
        WHERE payment_id = '".$paymentid."' AND linking_reference = '".$linkingreference."' AND status = '".$fetchedMessage."' ", OBJECT );
        if ($rowTransactionsExist[0]->num_rows == 0) {
            $wpdb->insert(
                $GLOBALS['table2'],
                array(
                    'email' => $mail,
                    'description' => $desc,
                    'amount' => $fetchedAmount,
                    'status' => $fetchedMessage,
                    'transaction_reference' => $tRef,
                    'payment_id' => $fetchedPaymentid,
                    'linking_reference' => $fetchedLinkingreference,
                ),
            );
             $GLOBALS['mes'] = "Inserted";
        }
        else{
               $GLOBALS['mes'] =   "transaction exists";
        }
    }
    else{
         $GLOBALS['mes'] = "Transaction cannot be verified";
    }

    if ($GLOBALS['mes'] == "Inserted") {

        echo '<script>alert("Payment was Successful.")</script>';
        header("Location: http://localhost/wordpress");

    }
    elseif ($GLOBALS['mes'] == "transaction exists"){

//        echo '<script>alert("This link has expired")</script>';
        header("Location: http://localhost/wordpress/");

    }
    elseif ($GLOBALS['mes'] == "Transaction cannot be verified" //&& $fetchedMessage != "approved" || $fetchedMessage != "pending"
    ){

        echo '<script>alert("Something went wrong. Payment was NOT successful. Please try again!")</script>';
        header("Location: http://localhost/wordpress/index.php/myshort/");

    }
}

// Update ItexPay Settings
if(isset($_POST['mode'])) {

    $mode = $_POST['mode'];
    if ($mode == "test"){

        $public_key = filter_input(INPUT_POST, 'public_key', FILTER_SANITIZE_STRING);
        $private_key = filter_input(INPUT_POST, 'private_key', FILTER_SANITIZE_STRING);
        $encryption_key = filter_input(INPUT_POST, 'encryption_key', FILTER_SANITIZE_ENCODED);

        $wpdb->update(
            $GLOBALS['table'],
            array(
                'public_key' => $public_key,
                'private_key' => $private_key,
                'encryption_key' => $encryption_key,
            ),
            array('mode' => 'test'),
        );


    }

    if ($mode == "live") {

        $public_key = filter_input(INPUT_POST, 'public_key', FILTER_SANITIZE_STRING);
        $private_key = filter_input(INPUT_POST, 'private_key', FILTER_SANITIZE_STRING);
        $encryption_key = filter_input(INPUT_POST, 'encryption_key', FILTER_SANITIZE_ENCODED);

        $wpdb->update(
            $GLOBALS['table'],
            array(
                'public_key' => $public_key,
                'private_key' => $private_key,
                'encryption_key' => $encryption_key,
            ),
            array('mode' => 'live'),
        );


    }

    $percentage_charges = $_POST['percentage_charges'];
    $threshold = $_POST['threshold'];
    $additional_charges = $_POST['additional_charges'];
    $price_cap = $_POST['price_cap'];

    $wpdb->update(
        $GLOBALS['table1'],
        array(
            'percentage_charges' => $percentage_charges,
            'threshold' => $threshold,
            'additional_charges' => $additional_charges,
            'price_cap' => $price_cap,
        ),
        array('id' => 1),
    );

}


function getauthoriztionurl($publickey, $amount, $email, $first_name, $last_name, $phone_number, $description) {

    $rand = rand(10000000, 200000000);
    $args2 = array(
        'headers' => array('Content-Type' => 'application/json', 'Authorization' => $publickey ),
        'body' => '{
            "amount":"'.$amount.'",
            "currency":"NGN",
            "customer":{
            "email":"'.$email.'",
            "first_name":"'.$first_name.'",
            "last_name":"'.$last_name.'",
            "phone_number":"'.$phone_number.'"
            },
            "redirecturl":"http://localhost/wordpress/wp-admin/admin.php?page=transactions&desc='.$description.'&mail='.$email.'",
            "reference":"'.$rand.'"
        }'

    );

    $response = wp_remote_post( 'https://staging.itexpay.com/api/pay', $args2);
    $body     = wp_remote_retrieve_body( $response);

    $authorization_url = json_decode($body) -> authorization_url;

    return $authorization_url;

}

function paymentForm($authorization_url){
    $html = '<html>

<style>
body {font-family: Arial, Helvetica, sans-serif;}

/* The Modal (background) */
.modalframe {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  padding-top: 0px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

/* Modal Content */
.modal-contentframe {
  background-color: transparent;
  /*margin: auto;*/
  /* padding: 20px; */
  width: 100%;
  height: 100%;
}

/* The Close Button */
.close {
  color: #aaaaaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
</style>
</head>
<body>

                <br><br>            
            
                
<!-- Trigger/Open The Modal 
<button id="myBtn">Open Modal</button>-->

<!-- The Modal -->
<div id="myModal" class="modalframe">

  <!-- Modal content -->
  
  <div class="modal-contentframe"> 
        <!-- <span class="close">&times;</span> -->
       <div>
            <iframe style="width: 100%; height: 100%; border: none"  src="'.$authorization_url.'"></iframe>
       </div>
 </div>
 
 
</div>

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the <span> element that closes the modal
//var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
//btn.onclick = function() {
  modal.style.display = "block";
//}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}

</script>


             </html>';
    echo $html;
}

// user input modal
function UsersPaymentForm(){

    $htmlModal = '<div>
            
                            <style>
                            body {font-family: Arial, Helvetica, sans-serif;}
                            /* The Modal (background) */
                            .modal {
                              display: none; /* Hidden by default */
                              position: fixed; /* Stay in place */
                              z-index: 1; /* Sit on top */
                              padding-top: 0px; /* Location of the box */
                              left: 0;
                              top: 0;
                              width: 100%; /* Full width */
                              height: 100%; /* Full height */
                              overflow: auto; /* Enable scroll if needed */
                              background-color: rgb(0,0,0);  /* Fallback color */
                              background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
                            }
                            
                            /* Modal Content */
                            .modal-content {
                              background-color: white;
                              margin: auto;
                              border: 2px dotted green; 
                              border-radius: 5px;
                              width: 450px;
                              height: 550px;
                            }
                            
                            /* The Close Button */
                            .close {
                              color: #aaaaaa;
                              float: right;
                              font-size: 28px;
                              font-weight: bold;
                            }
                            
                            .close:hover,
                            .close:focus {
                              color: #000;
                              text-decoration: none;
                              cursor: pointer;
                            }
                            .userinputs{
                              width: 100%;
                              padding: 6px 10px;
                              margin: 2px 0;
                              box-sizing: border-box;
                              border: none;
                              border-bottom: 1px solid blue;
                            }
                            
                            .userinputslabels{
                            margin: 2px; 
                            }
                            
                            </style>
                            
                            
                                            <br>            
                                        
                                            
                            <!-- Trigger/Open The Modal 
                            <button id="myBtn">Open Modal</button>-->
                            <button onclick="paynow()" style="
                                                      background-color: green;
                                                      border-radius: 4px;
                                                      border: none;
                                                      color: white;
                                                      padding: 10px 30px;
                                                      text-align: center;
                                                      text-decoration: none;
                                                      display: inline-block;
                                                      font-size: 16px;
                                                      margin: 4px 2px;
                                                      cursor: pointer;
                                                      ">ItexPay</button>
                            <!-- The Modal -->
                            <div id="myModalv" class="modal">
                              <!-- Modal content -->
                              <div class="modal-content"> 
                                    <!-- <span class="close">&times;</span> -->
                                   <form action="'.$_SERVER['PHP_SELF'].'" method="post" style="margin: auto; padding: 30px;">
                                                    <p style="text-align: center; font-size: 24px; color: green"><b>ItexPay</b></p>
                                                    <label for="first_name" class="userinputslabels">First Name:</label>
                                                    <input type="text" class="userinputs" placeholder="First name" name="first_name" value="" />
                                                    <label for="last_name" class="userinputslabels">Last Name:</label> 
                                                    <input type="text" class="userinputs" placeholder="Last Name" name="last_name" value="" />
                                                    <label for="Phone" class="userinputslabels">Phone Number:</label>
                                                    <input type="text" class="userinputs" placeholder="Phone Number" name="phone_number" value="" />
                                                    <label for="email" class="userinputslabels">Email Address:</label>
                                                    <input type="text" class="userinputs" placeholder="Email Address" name="email" value="" />
                                                    <label for="amount" class="userinputslabels">Amount:</label>
                                                    <input type="text" class="userinputs" placeholder="Amount" name="amount" value="" />
                                                    <label for="description" class="userinputslabels">Description:</label> 
                                                    <input type="text" class="userinputs" placeholder="Description in a sentence" name="description" value="" /><br>
                                                    <hr>
                                                    <input type="submit" value="pay now" style="
                                                     background-color: green;
                                                      border-radius: 4px;
                                                      border: none;
                                                      color: white;
                                                      padding: 5px 15px;
                                                      text-align: center;
                                                      text-decoration: none;
                                                      display: inline-block;
                                                      font-size: 16px;
                                                      margin: 4px 2px;
                                                      cursor: pointer;">
                                                    <br>
                                            </form>
                             </div>
                             
                            </div>
                            
                            <script>
                            function paynow() {
                                    // Get the modal
                                    var modal = document.getElementById("myModalv");
                                    
                                    // Get the button that opens the modal
                                    //var btn = document.getElementById("myBtn");
                                    
                                    // Get the <span> element that closes the modal
                                    var span = document.getElementsByClassName("close")[0];
                                    
                                    // When the user clicks the button, open the modal 
                                    //btn.onclick = function() {
                                      modal.style.display = "block";   //just load the modal
                                    //}
                                    
                                    // When the user clicks on <span> (x), close the modal
                                    span.onclick = function() {
                                      modal.style.display = "none";
                                    }
                            }
                            // When the user clicks anywhere outside the modal, close it
                            //window.onclick = function(event) {
                              if (event.target == modal) {
                                modal.style.display = "none";
                              }
                            //}
                            </script>
            
            
            </div>';
    return $htmlModal;

};

// ItexPay settings in admin dashboard
function settingsForm(){

    $mode = $GLOBALS['current_mode'];

    echo '
            <script>
            function myFunction(){

                var str = document.getElementById("my_current_mode").value;
                var xhttp;

                xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                     location.reload();
                }
        };
        xhttp.open("GET", "http://localhost/wordpress/wp-admin/admin.php?selectedMode="+str, true);
        xhttp.send();
            };
            </script>';
    echo  '<html>
                <body>    
                <br><br>            
                <form method="post" style="margin: auto; padding: 50px; border: 1px dotted green; border-radius: 5px; width: 80%">
                        <h1 style="text-align: center">ItexPay Settings Page</h1>
                        <p style="text-align: center">Visit <a href="https://itexpay.com">ItexPay</a> to get Apikeys</p>';

    echo '';


    if ($mode == "test") {
        echo '
                        <select name="mode" id="my_current_mode" onchange="myFunction()">
                            <option value="test" onclick="">Test Mode</option>
                            <option value="live" onclick="">Live Mode</option>
                        </select><br><br><br>
                        <label for="apikey" style="margin: 10px; font-size: 18px">Test Public Key:</label><br> 
                        <input type="text" placeholder="" style="width: 70%" name="public_key" value="' . $GLOBALS['public_key'] . '" /><br><br><br>
                          
                        <label for="apikey" style="margin: 10px; font-size: 18px">Test Secret Key:</label><br> 
                        <input type="text" placeholder="" style="width: 70%" name="private_key" value="' . $GLOBALS['private_key'] . '" /><br><br><br>
                        
                        <label for="apikey" style="margin: 10px; font-size: 18px">Test Encryption key (Encrypted Public Key):</label><br> 
                        <input type="text" placeholder="" style="width: 70%" name="encryption_key" value="' . $GLOBALS['encryption_key'] . '" /><br><br><br>';
    };
    if ($mode == "live") {
        echo '                 
                 <select name="mode" id="my_current_mode" onchange="myFunction()">
                            <option value="live" onclick="">Live Mode</option>
                            <option value="test" onclick="">Test Mode</option>
                        </select><br><br><br>      
                        <label for="public_key" style="margin: 10px; font-size: 18px">Live Public Key:</label><br> 
                        <input type="text" placeholder="" style="width: 70%" name="public_key" value="' . $GLOBALS['public_key'] . '" /><br><br><br>
                        
                        <label for="private_key" style="margin: 10px; font-size: 18px">Live Secret Key:</label><br> 
                        <input type="text" placeholder="" style="width: 70%" name="private_key" value="' . $GLOBALS['private_key'] . '" /><br><br><br>
                        
                         <label for="encryption_key" style="margin: 10px; font-size: 18px">Live Encryption key (Encrypted Public Key):</label><br> 
                        <input type="text" placeholder="" style="width: 70%" name="encryption_key" value="' . $GLOBALS['encryption_key'] . '" /><br><br><br>';
    };

    echo '<hr><hr>
                        <h3>Customize Charges</h3>
                        <label for="percentage_charges" style="margin: 10px; font-size: 18px">Percentage Charges:</label><br>                         
                        <input type="text" placeholder="" style="width: 70%" name="percentage_charges" value="'.$GLOBALS['percentage_charges'].'" /><br><br><br>
                        
                        <label for="threshold" style="margin: 10px; font-size: 18px">Threshold:</label><br>                         
                        <input type="text" placeholder="" style="width: 70%" name="threshold" value="'.$GLOBALS['threshold'].'" /><br><br><br>
                        
                        <label for="additional_charges" style="margin: 10px; font-size: 18px">Additional Charge:</label><br>                         
                        <input type="text" placeholder="" style="width: 70%" name="additional_charges" value="'.$GLOBALS['additional_charges'].'" /><br><br><br>
                        
                        <label for="price_cap" style="margin: 10px; font-size: 18px">Price Cap:</label><br>                         
                        <input type="text" placeholder="" style="width: 70%" name="price_cap" value="'.$GLOBALS['price_cap'].'" /><br><br><br>
                        
                        <button class="button" style="
                                                      background-color: #1e1f26;
                                                      border: none;
                                                      color: white;
                                                      padding: 2px 15px;
                                                      text-align: center;
                                                      text-decoration: none;
                                                      display: inline-block;
                                                      font-size: 16px;
                                                      margin: 4px 2px;
                                                      cursor: pointer;
                                                      ">Save Changes</button>

                        <br><br>
                </form></body>
             </html>';
}

// ItexPay transaction history in WordPress admin dashboard
function transaction_history(){

    echo "<html>
                <style>
    table {
        width:98%;
        text-align:center;
        border: 1px solid #ccc;
                }             
                th {
                }
                tr {
                height: 40px;
                }
                tr:nth-child(even){
                background-color: #dedede;
                }
                tr:hover {
                }
                td {
                }
                td:hover {
                }
                </style>
                <body>
                <h1 style='color: green'>All Transactions</h1><br>
                
                <table>
                  <tr>
                    <th>SN</th>
                    <th>email</th>
                    <th>Description</th>
                    <th>Amount(NGN)</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Transaction Reference</th>
                  </tr>";


    global $wpdb;
    $rowTransactions = $wpdb->get_results( "SELECT COUNT(*) as num_rows FROM ".$GLOBALS['table2']." ", OBJECT );
    $num_trans = $rowTransactions[0]->num_rows;

    $resultsTransactions = $wpdb->get_results( "SELECT * FROM ".$GLOBALS['table2']." ", OBJECT );
    $json00 = json_encode($resultsTransactions);
    $jsond00 = json_decode($json00, true) ;
    for($x = 0; $x < $num_trans; $x++)
    {
        $id   = $jsond00[$x]['id'];
        $email   = $jsond00[$x]['email'];
        $description   = $jsond00[$x]['description'];
        $amount   = $jsond00[$x]['amount'];
        $status   = $jsond00[$x]['status'];
        $date   = $jsond00[$x]['time'];
        $transactionRef   = $jsond00[$x]['transaction_reference'];

        echo "
                  <tr>
                    <td>$id</td>
                    <td>$email</td>
                    <td>$description</td>
                    <td>$amount</td>
                    <td>$status</td>
                    <td>$date</td>
                    <td>$transactionRef</td>
                  </tr>";
    }



    echo "</table> 
          </body>
          </html>";

}


/**
 * Register a custom menu page.
 */
function itex_plugin_main_menu(){
    add_menu_page(
        __( 'Custom Menu Title', 'textdomain' ),
        'ItexPay Plugin',
        'manage_options',
        'itexmenu',
        'read_me', // 'my_custom_menu_page',
         background_color(),
//        plugins_url( 'itexlogo2.png', ''),
        6
    );
}
add_action( 'admin_menu', 'itex_plugin_main_menu' );

/**
 * Adds a submenu page under a custom post type parent.
 */
function add_submenu_settings()
{

    add_submenu_page(
        'itexmenu',
        __('Books Shortcode Reference', 'textdomain'),
        __('Settings', 'textdomain'),
        'manage_options',
        'settings',
        'my_custom_menu_page'
    );
}
add_action('admin_menu', 'add_submenu_settings');

function add_submenu_transaction() {

    add_submenu_page(
        'itexmenu',
        __( 'Books Shortcode Reference', 'textdomain' ),
        __( 'Transactions', 'textdomain' ),
        'manage_options',
        'transactions',
        'transaction_history' // 'payment_form'
    );
}
add_action('admin_menu', 'add_submenu_transaction');

/**
 * Central location to create all shortcodes.
 */
function register_shortcodes(){
    add_shortcode('ItexPayButton', 'UsersPaymentForm');
}
add_action( 'init', 'register_shortcodes');


/**
 * Display a custom menu page
 */
function my_custom_menu_page(){
    settingsForm();
}

function read_me(){
    echo "<h1>Read Me</h1>
    <li><h3>This plugin is used for web acquiring for Wordpress Merchants.</h3><li>
    <li><h3>It is easy to use. Get started by setting up your apikeys at <a href='admin.php?page=settings'>settings page</a></h3><li>
    <li><h3>You can retrieve your Api keys by signing up in <a href='https://itexpay.com'>ItexPay</a></h3><li>
    <li><h3>Payment with ItexPay is very simple. Just copy and paste shortcode [ItexPayButton] on any Wordpress page to display 
the payment button <br> <button onclick='paynow()' style='
background-color: green;
border-radius: 4px;
border: none;
color: white;
padding: 10px 30px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;
margin: 4px 2px;
cursor: pointer;
'>ItexPay</button> <br>
when the payment button is clicked, your customers can make payment for your products without struggles.</h3><li>
<li><h3>Visit the <a href='admin.php?page=transactions'>transactions page</a> to view all your transactions</h3><li>";

}

