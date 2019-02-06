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
    $ussd_string_exploded = explode ("*", $ussd_string);
    // die(print_r($ussd_string_exploded));

    // Get menu level from ussd_string reply
    $level = count($ussd_string_exploded);
    if($level == 1 or $level == 0){
        display_menu(); // show the home/first menu
    }

    if ($level > 1)
    {
        if ($ussd_string_exploded[1] == "1")
        {
            // If user selected 1 send them to the registration menu
            $count = count($ussd_string_exploded);
            switch($count)
            {
                case 1:
                    break;
                case 2:
                    $ussd_text = "1. Daily\n2. Weekly\n3. Monthly";
                    ussd_proceed($ussd_text);
                    break;
                case 3:
                    if ($ussd_string_exploded[2] == "1")
                    {
                        save("MDSP2000314288", "Thank you for subscribing to daily video service", $dbh, $phone, 'vodd');
                    }
                    break;
            }
        }
        else if ($ussd_string_exploded[1] == "2"){
            //If user selected 2, send them to the about menu
            showGames($ussd_string_exploded);
        }
        else if ($ussd_string_exploded[1] == "3"){
            // If user selected 1 send them to the registration menu
            $count = count($ussd_string_exploded);
            switch($count)
            {
                case 1:
                    break;
                case 2:
                    $ussd_text = "1. Daily";
                    ussd_proceed($ussd_text);
                    break;
                case 3:
                    if ($ussd_string_exploded[2] == "1")
                    {
                        save("MDSP2000333968", "Thank you for Laliga update", $dbh, $phone, 'lal');
                    }
                    break;
            }
        }
    }
}
catch(PDOException $e) {
    //var_dump($e);
    ussd_stop("Application under maintenance");
}
catch(Exception $e) {
    //var_dump($e);
    ussd_stop("Application under maintenance");
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
    $ussd_text =    "1. Videos\n2. Games\n3. Laliga"; // add \n so that the menu has new lines
    ussd_proceed($ussd_text);
}

// Function that hanldles About menu
function showGames($ussd_text)
{
    $ussd_text =    "Coming soon";
    ussd_stop($ussd_text);
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
