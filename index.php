<?php
/* Simple sample USSD registration application
 * USSD gateway that is being used is Africa's Talking USSD gateway
 */
// Print the response as plain text so that the gateway can read it
header('Content-type: text/plain');
/* local db configuration */
$dsn = 'pgsql:host=localhost;dbname=vasmaster;host=127.0.0.1;'; //database name
$user = 'postgres'; // your mysql user 
$password = 'postgres'; // your mysql password
//  Create a PDO instance that will allow you to access your database
try {
    $dbh = new PDO($dsn, $user, $password);
    // Get the parameters provided by Africa's Talking USSD gateway
    $phone = $_GET['MSISDN'];
    $session_id = $_GET['SESSION_ID'];
    $service_code = $_GET['SERVICE_CODE'];
    $ussd_string= $_GET['USSD_STRING'];
    //set default level to zero
    $level = 0;

    
    /* Split text input based on asteriks(*)
    * Africa's talking appends asteriks for after every menu level or input
    * One needs to split the response from Africa's Talking in order to determine
    * the menu level and input for each level
    * */
    $ussd_string = trim($ussd_string);
    if (empty($ussd_string))
    {
        display_menu(); // show the home/first menu
    }
    else
    {
        if (strpos($ussd_string, '*') !== false) {
            // echo 'true';
            processMenu($ussd_string, $dbh, $phone);
        }
        else
        {
            $ussd_text = "1. Daily subscription";
            ussd_proceed($ussd_text);
        }
    }
}
catch(PDOException $e) {
    var_dump($e);
    // ussd_stop("Application under maintenance");
}
catch(Exception $e) {
    var_dump($e);
    // ussd_stop("Application under maintenance");
}

/**
 * Process child menus and subcribes users
 */
function processMenu($ussd_string, $dbh, $phone)
{
    $ussd_string_exploded = explode("*", $ussd_string);
    $count = count($ussd_string_exploded);
    switch($count)
    {
        case 1:
            break;
        case 2:
            if ($ussd_string_exploded[0] == "1")
            {
                save("MDSP2000314288", "Thank you for subscribing to daily video service", $dbh, $phone, 'vodd');
            }
            if ($ussd_string_exploded[0] == "2")
            {
                save("MDSP2000310695", "Thank you for subscribing to daily games service", $dbh, $phone, 'subd');
            }
            if ($ussd_string_exploded[0] == "3")
            {
                save("MDSP2000333968", "Thank you for subscribing to daily Laliga updates", $dbh, $phone, 'lal');
            }
            break;
    }
}

/* The ussd_proceed function appends CON to the USSD response your application gives.
 * This informs Africa's Talking USSD gateway and consecuently Safaricom's
 * USSD gateway that the USSD session is till in session or should still continue
 * Use this when you want the application USSD session to continue
*/
function ussd_proceed($ussd_text){
    echo "CON $ussd_text";
}
/* This ussd_stop function appends END to the USSD response your application gives.
 * This informs Africa's Talking USSD gateway and consecuently Safaricom's
 * USSD gateway that the USSD session should end.
 * Use this when you to want the application session to terminate/end the application
*/
function ussd_stop($ussd_text){
    echo "END $ussd_text";
}

//This is the home menu function
function display_menu()
{
    $ussd_text =    "1. Videos\n2. Games\n3. Live football updates"; // add \n so that the menu has new lines
    ussd_proceed($ussd_text);
}

function save($code, $message, $dbh, $phone, $keyword)
{
    $sql = "INSERT INTO subscribe_product(user_id, user_type, product_id, oper_code, is_auto_extend, channel_id, account_id) VALUES ('$phone', 0, '$code', '$keyword', 0, 2, 2)";
    $sth = $dbh->prepare($sql);
    $sth->execute();
    if($sth->errorCode() == 0) {
        ussd_stop($message);
    } else {
        $errors = $sth->errorInfo();
    }
}

# close the pdo connection  
$dbh = null;
?>
