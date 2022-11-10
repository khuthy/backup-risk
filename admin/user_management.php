<?php
/* This Source Code Form is subject to the terms of the Mozilla Public
* License, v. 2.0. If a copy of the MPL was not distributed with this
* file, You can obtain one at http://mozilla.org/MPL/2.0/. */

// Include required functions file
require_once(realpath(__DIR__ . '/../includes/functions.php'));
require_once(realpath(__DIR__ . '/../includes/authenticate.php'));
require_once(realpath(__DIR__ . '/../includes/display.php'));
require_once(realpath(__DIR__ . '/../includes/messages.php'));
require_once(realpath(__DIR__ . '/../includes/alerts.php'));
require_once(realpath(__DIR__ . '/../includes/apiconnection.php'));

// Include Zend Escaper for HTML Output Encoding
require_once(realpath(__DIR__ . '/../includes/Component_ZendEscaper/Escaper.php'));
$escaper = new Zend\Escaper\Escaper('utf-8');

// Add various security headers
add_security_headers();

$id = 0;

if (!isset($_SESSION))
{
    // Session handler is database
    if (USE_DATABASE_FOR_SESSIONS == "true")
    {
        session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy', 'sess_gc');
    }

    // Start the session
    session_set_cookie_params(0, '/', '', isset($_SERVER["HTTPS"]), true);

    session_name('SimpleRisk');
    session_start();
}

// Include the language file
require_once(language_file());

require_once(realpath(__DIR__ . '/../includes/csrf-magic/csrf-magic.php'));

// Check for session timeout or renegotiation
session_check();

// Check if access is authorized
if (!isset($_SESSION["access"]) || $_SESSION["access"] != "granted")
{
    set_unauthenticated_redirect();
    header("Location: ../index.php");
    exit(0);
}



// Check if access is authorized
if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != "1")
{
    header("Location: ../index.php");
    exit(0);
}

// Check if a new user was submitted
if (isset($_POST['add_user']))
{
    

        $employeeid = $_POST['empid'];  // Storing Selected Value In Variable
       // echo "You have selected :" .$selected_val;  // Displaying Selected Value
 

   // echo "<script>console.log('Debug straight: " . $_POST['empid']. "' );</script>";

  

   $employees = get_employees();

   
    foreach($employees as $emp)
    {
        if($emp['EmployeeID'] == $employeeid )
        {
            $name = ($emp['LastName'].' '.$emp['FirstName']);
            $defaultmailuser = $emp['LastName'].$emp['FirstName'];
            
            if($emp['eMail'] == Null)
            {
                $email = $defaultmailuser.'@geoscience.co.za';
            }else{
                $email = $emp['eMail'];
            }

            $user = $emp['FirstName'];
          

           // echo "<script>console.log('Debug Objects: " .$user. "' );</script>";
        }

        // echo $employees['EmployeeID']['FirstName'];
        // echo  $employees['EmployeeID'] ;
    }

    
   // echo $_POST['employee'];



    $type = 1;
   // $obj  = $employees[$empid] ;

//    $name = $_POST['name'];
//    $email = $_POST['email'];
//    $user = $_POST['new_user'];
//    $pass = $_POST['password'];
//    // echo $name;
    $pass = $_POST['password'];
    $manager = (int)$_POST['manager'];

    $repeat_pass = $_POST['repeat_password'];
    $teams = isset($_POST['team']) ? $_POST['team'] : array('none');

    //$teamsid = $employee[]

    $role_id = (int)$_POST['role'];
    $admin = isset($_POST['admin']) ? '1' : '0';
    $governance = isset($_POST['governance']) ? '1' : '0';
    $riskmanagement = isset($_POST['riskmanagement']) ? '1' : '0';
    $compliance = isset($_POST['compliance']) ? '1' : '0';
    $assessments = isset($_POST['assessments']) ? '1' : '0';
    $asset = isset($_POST['asset']) ? '1' : '0';
    $submit_risks = isset($_POST['submit_risks']) ? '1' : '0';
    $modify_risks = isset($_POST['modify_risks']) ? '1' : '0';
    $close_risks = isset($_POST['close_risks']) ? '1' : '0';
    $plan_mitigations = isset($_POST['plan_mitigations']) ? '1' : '0';
    $review_veryhigh = isset($_POST['review_veryhigh']) ? '1' : '0';
    $accept_mitigation = isset($_POST['accept_mitigation']) ? '1' : '0';
    $review_high = isset($_POST['review_high']) ? '1' : '0';
    $review_medium = isset($_POST['review_medium']) ? '1' : '0';
    $review_low = isset($_POST['review_low']) ? '1' : '0';
    $review_insignificant = isset($_POST['review_insignificant']) ? '1' : '0';
    $multi_factor = (int)$_POST['multi_factor'];
    $change_password = (int)(isset($_POST['change_password']) ? $_POST['change_password'] : 0);
    
    $add_new_frameworks = (int)(isset($_POST['add_new_frameworks']) ? 1 : 0);
    $modify_frameworks = (int)(isset($_POST['modify_frameworks']) ? 1 : 0);
    $delete_frameworks = (int)(isset($_POST['delete_frameworks']) ? 1 : 0);
    $add_new_controls = (int)(isset($_POST['add_new_controls']) ? 1 : 0);
    $modify_controls = (int)(isset($_POST['modify_controls']) ? 1 : 0);
    $delete_controls = (int)(isset($_POST['delete_controls']) ? 1 : 0);
    $add_documentation = (int)(isset($_POST['add_documentation']) ? 1 : 0);
    $modify_documentation = (int)(isset($_POST['modify_documentation']) ? 1 : 0);
    $delete_documentation = (int)(isset($_POST['delete_documentation']) ? 1 : 0);
    $comment_risk_management = (int)(isset($_POST['comment_risk_management']) ? 1 : 0);
    $comment_compliance = (int)(isset($_POST['comment_compliance']) ? 1 : 0);
    
    $view_exception           = isset($_POST['view_exception']) ? 1 : 0;
    $create_exception         = isset($_POST['create_exception']) ? 1 : 0;
    $update_exception         = isset($_POST['update_exception']) ? 1 : 0;
    $delete_exception         = isset($_POST['delete_exception']) ? 1 : 0;
    $approve_exception        = isset($_POST['approve_exception']) ? 1 : 0;

    // If the type is 1
    if ($type == "1")
    {
        // This is a local SimpleRisk user account
        $type = "simplerisk";

        // Check the password
        $error_code = valid_password($pass, $repeat_pass);
    }
    // If the type is 2
   // else if ($type == "2")
  //  {
        // This is an LDAP user account
    //    $type = "ldap";

        // No password check required
   //     $error_code = 1;
  //  }
    // If the type is 3
   // else if ($type == "3")
   // {
        // This is a SAML user account
    //    $type = "saml";

        // No password check required
    //    $error_code = 1;
    //}
   // else
   // {
        // This is an invalid type
      //  $type = "INVALID";

        // Return an error
       // $error_code = 0;
  //  }

    // If the password is valid
    if ($error_code == 1)
    {
        // Verify that the user does not exist
        if (!user_exist($user))
        {
            // Verify that it is a valid username format
            if (valid_username($user))
            {
                // Create a unique salt for the user
                $salt = generate_token(20);

                // Hash the salt
                $salt_hash = '$2a$15$' . md5($salt);

                // Generate the password hash
                $hash = generateHash($salt_hash, $pass);

                // Create a boolean for all
                $all = false;

                // Create a boolean for none
                $none = false;

                // Initialize the team value as null
                $team = null;

                // Create the team value
                foreach ($teams as $value)
                {
                    // If the selected value is all
                    if ($value == "all") $all = true;

                    // If the selected value is none
                    if ($value == "none") $none = true;

                    $team .= ":";
                    $team .= $value;
                    $team .= ":";
                }

                // If no value was submitted then default to none
                if ($value == "") $none = true;

                // If all was selected then assign all teams
                if ($all) $team = "all";

                // If none was selected then assign no teams
                if ($none) $team = "none";
                
                $other_options = [
                    "add_documentation" => $add_documentation,
                    "modify_documentation" => $modify_documentation,
                    "delete_documentation" => $delete_documentation,
                    "comment_risk_management" => $comment_risk_management,
                    "comment_compliance" => $comment_compliance,
                    "view_exception" => $view_exception,
                    "create_exception" => $create_exception,
                    "update_exception" => $update_exception,
                    "delete_exception" => $delete_exception,
                    "approve_exception" => $approve_exception,
                    "manager" => $manager,
                ];

                // Insert a new user
                add_user($type, $user, $email, $name, $salt, $hash, $team, $role_id, $governance, $riskmanagement, $compliance, $assessments, $asset, $admin, $review_veryhigh, $accept_mitigation, $review_high, $review_medium, $review_low, $review_insignificant, $submit_risks, $modify_risks, $plan_mitigations, $close_risks, $multi_factor, $change_password, $add_new_frameworks, $modify_frameworks, $delete_frameworks, $add_new_controls, $modify_controls, $delete_controls, $other_options);

           
		// If the encryption extra is enabled
                // if (encryption_extra())
                // {
                //     // Load the extra
                //     require_once(realpath(__DIR__ . '/../extras/encryption/index.php'));

                //     // If the encryption method is mcrypt
                //     if (isset($_SESSION['encryption_method']) && $_SESSION['encryption_method'] == "mcrypt")
                //     {
                //         // Add the new encrypted user
                //         add_user_enc($pass, $salt, $user);
                //     }
                // }

                // Clear values
                $name = "";
                $email = "";
                $user = "";
                $change_password = 0;

                // Display an alert
                set_alert(true, "good", "The new user was added successfully.");
            }
            // Otherwise, an invalid username was specified
            else
            {
                // Display an alert
                set_alert(true, "bad", "An invalid username was specified.  Please try again with a different username.");
            }
        }
        // Otherwise, the user already exists
        else
        {
            // Display an alert
            set_alert(true, "bad", "The username already exists.  Please try again with a different username.");
        }
    }
    // Otherewise, an invalid password was specified
    else
    {
        // Display an alert
        //set_alert(true, "bad", password_error_message($error_code));
    }
}

// Check if a user was enabled
if (isset($_POST['enable_user']))
{
    $value = (int)$_POST['disabled_users'];

    // Verify value is an integer
    if (is_int($value))
    {
        enable_user($value);

        // Display an alert
        set_alert(true, "good", "The user was enabled successfully.");
    }
}

// Check if a user was disabled
if (isset($_POST['disable_user']))
{
    $value = (int)$_POST['enabled_users'];

    // Verify value is an integer
    if (is_int($value))
    {
        disable_user($value);

        // Display an alert
        set_alert(true, "good", "The user was disabled successfully.");
    }

}

// Check if a user was deleted
if (isset($_POST['delete_user']))
{
    $value = (int)$_POST['user'];

    // Verify value is an integer
    if (is_int($value))
    {
        delete_value("user", $value);

        // If the encryption extra is enabled
       /* if (encryption_extra())
        {
            // Load the extra
            require_once(realpath(__DIR__ . '/../extras/encryption/index.php'));

            // If the encryption method is mcrypt
            if (isset($_SESSION['encryption_method']) && $_SESSION['encryption_method'] == "mcrypt")
            {
                // Delete the value from the user_enc table
                delete_user_enc($value);

                // Check to see if all users have now been activated
                check_all_activated();
            }
        } */

        // Display an alert
        set_alert(true, "good", "The existing user was deleted successfully.");
    }
}

// Check if a password reset was requested
if (isset($_POST['password_reset']))
{
    $value = (int)$_POST['user'];

    // Verify value is an integer
    if (is_int($value))
    {
        password_reset_by_userid($value);

        // Display an alert
        set_alert(true, "good", "A password reset email was sent to the user.");
    }
}

// Check if a password policy update was requested
if (isset($_POST['password_policy_update']))
{
    $strict_user_validation = (isset($_POST['strict_user_validation'])) ? 1 : 0;
    $pass_policy_enabled = (isset($_POST['pass_policy_enabled'])) ? 1 : 0;
    $min_characters = (int)$_POST['min_characters'];
    $alpha_required = (isset($_POST['alpha_required'])) ? 1 : 0;
    $upper_required = (isset($_POST['upper_required'])) ? 1 : 0;
    $lower_required = (isset($_POST['lower_required'])) ? 1 : 0;
    $digits_required = (isset($_POST['digits_required'])) ? 1 : 0;
    $special_required = (isset($_POST['special_required'])) ? 1 : 0;

    $pass_policy_attempt_lockout =(int)$_POST['pass_policy_attempt_lockout'];
    $pass_policy_attempt_lockout_time = (int)$_POST['pass_policy_attempt_lockout_time'];
    $pass_policy_min_age = (int)$_POST['pass_policy_min_age'];
    $pass_policy_max_age = (int)$_POST['pass_policy_max_age'];
    $pass_policy_reuse_limit = (int)$_POST['pass_policy_reuse_limit'];

    update_password_policy($strict_user_validation, $pass_policy_enabled, $min_characters, $alpha_required, $upper_required, $lower_required, $digits_required, $special_required, $pass_policy_attempt_lockout, $pass_policy_attempt_lockout_time, $pass_policy_min_age, $pass_policy_max_age, $pass_policy_reuse_limit);

    // Display an alert
    set_alert(true, "good", "The settings were updated successfully.");
}

?>

<!doctype html>
<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=10,9,7,8">
    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery-ui.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/bootstrap-multiselect.js"></script>
    <title>Risk Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="../css/bootstrap-responsive.css">
    <link rel="stylesheet" href="../css/bootstrap-multiselect.css">
    <script type="text/javascript">
    $(function(){
        $("#team").multiselect({
            allSelectedText: '<?php echo $escaper->escapeHtml($lang['AllTeams']); ?>',
            includeSelectAllOption: true
        });
    });
    </script>
    <script type="text/javascript">
        function handleSelection(choice) {
            elements = document.getElementsByClassName("ldap_pass");
            if (choice=="1") {
                for(i=0; i<elements.length; i++) {
                    elements[i].style.display = "";
                }
            }
            if (choice=="2") {
                for(i=0; i<elements.length; i++) {
                    elements[i].style.display = "none";
                }
            }
            if (choice=="3") {
                for(i=0; i<elements.length; i++) {
                    elements[i].style.display = "none";
                }
            }
        }
    </script>

    <link rel="stylesheet" href="../css/divshot-util.css">
    <link rel="stylesheet" href="../css/divshot-canvas.css">
    <link rel="stylesheet" href="../css/display.css">

    <link rel="stylesheet" href="../bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/theme.css">
    
    <?php
        setup_alert_requirements("..");
    ?>    
</head>

<body>
<script type="text/javascript">
    $(document).ready(function(){
        // role event
        $("#role").change(function(){
            setUserResponsibilitesByRole();
        });
        
        if($("#role").val()){
            setUserResponsibilitesByRole();
        }
    });
    
    function setUserResponsibilitesByRole(){
        // If role is unselected, uncheck all responsibilities
        if(!$("#role").val())
        {
            $(".checklist input[type=checkbox]").prop("checked", false);
        }
        // If administrator role is selected
        else if($("#role").val() == 1)
        {
            // Set all user responsibilites
            $(".checklist input[type=checkbox]").prop("checked", true);
            
            // Set all teams
            $("#team").multiselect("selectAll", false);
            $("#team").multiselect("refresh");
        }
        else
        {
            $.ajax({
                type: "GET",
                url: BASE_URL + "/api/role_responsibilities/get_responsibilities",
                data: {
                    role_id: $("#role").val()
                },
                success: function(data){
                    // Uncheck all checkboxes
                    $(".checklist input[type=checkbox]").prop("checked", false);
                    
                    // Check all for responsibilites
                    var responsibility_names = data.data;
                    for(var key in responsibility_names){
                        $(".checklist input[name="+responsibility_names[key]+"]").prop("checked", true)
                    }
                },
                error: function(xhr,status,error){
                    if(xhr.responseJSON && xhr.responseJSON.status_message){
                        showAlertsFromArray(xhr.responseJSON.status_message);
                    }
                }
            })
        }
    }
    
    function checkAll(bx) {
        if(bx.checked){
            $(bx).parents('table').find('input[type=checkbox]').prop('checked', true);
        }else{
            $(bx).parents('table').find('input[type=checkbox]').prop('checked', false);
        }
    }

    function checkAllGovernance(bx) {
        if (document.getElementsByName("check_governance")[0].checked == true) {
            document.getElementsByName("governance")[0].checked = true;
            document.getElementsByName("add_new_frameworks")[0].checked = true;
            document.getElementsByName("modify_frameworks")[0].checked = true;
            document.getElementsByName("delete_frameworks")[0].checked = true;
            document.getElementsByName("add_new_controls")[0].checked = true;
            document.getElementsByName("modify_controls")[0].checked = true;
            document.getElementsByName("delete_controls")[0].checked = true;
            document.getElementsByName("add_documentation")[0].checked = true;
            document.getElementsByName("modify_documentation")[0].checked = true;
            document.getElementsByName("delete_documentation")[0].checked = true;
            document.getElementsByName("view_exception")[0].checked = true;
            document.getElementsByName("create_exception")[0].checked = true;
            document.getElementsByName("update_exception")[0].checked = true;
            document.getElementsByName("delete_exception")[0].checked = true;
            document.getElementsByName("approve_exception")[0].checked = true;
        }
        else {
            document.getElementsByName("governance")[0].checked = false;
            document.getElementsByName("add_new_frameworks")[0].checked = false;
            document.getElementsByName("modify_frameworks")[0].checked = false;
            document.getElementsByName("delete_frameworks")[0].checked = false;
            document.getElementsByName("add_new_controls")[0].checked = false;
            document.getElementsByName("modify_controls")[0].checked = false;
            document.getElementsByName("delete_controls")[0].checked = false;
            document.getElementsByName("add_documentation")[0].checked = false;
            document.getElementsByName("modify_documentation")[0].checked = false;
            document.getElementsByName("delete_documentation")[0].checked = false;
            document.getElementsByName("view_exception")[0].checked = false;
            document.getElementsByName("create_exception")[0].checked = false;
            document.getElementsByName("update_exception")[0].checked = false;
            document.getElementsByName("delete_exception")[0].checked = false;
            document.getElementsByName("approve_exception")[0].checked = false;
        }
    }

    function checkAllRiskMgmt(bx) {
        if (document.getElementsByName("check_risk_mgmt")[0].checked == true) {
            document.getElementsByName("riskmanagement")[0].checked = true;
            document.getElementsByName("submit_risks")[0].checked = true;
            document.getElementsByName("modify_risks")[0].checked = true;
            document.getElementsByName("close_risks")[0].checked = true;
            document.getElementsByName("plan_mitigations")[0].checked = true;
            document.getElementsByName("review_insignificant")[0].checked = true;
            document.getElementsByName("review_low")[0].checked = true;
            document.getElementsByName("review_medium")[0].checked = true;
            document.getElementsByName("review_high")[0].checked = true;
            document.getElementsByName("review_veryhigh")[0].checked = true;
            document.getElementsByName("accept_mitigation")[0].checked = true;
            document.getElementsByName("comment_risk_management")[0].checked = true;
        }
        else {
            document.getElementsByName("riskmanagement")[0].checked = false;
            document.getElementsByName("submit_risks")[0].checked = false;
            document.getElementsByName("modify_risks")[0].checked = false;
            document.getElementsByName("close_risks")[0].checked = false;
            document.getElementsByName("plan_mitigations")[0].checked = false;
            document.getElementsByName("review_insignificant")[0].checked = false;
            document.getElementsByName("review_low")[0].checked = false;
            document.getElementsByName("review_medium")[0].checked = false;
            document.getElementsByName("review_high")[0].checked = false;
            document.getElementsByName("review_veryhigh")[0].checked = false;
            document.getElementsByName("accept_mitigation")[0].checked = false;
            document.getElementsByName("comment_risk_management")[0].checked = false;
        }
    }

    function checkAllCompliance(bx) {
        if (document.getElementsByName("check_compliance")[0].checked == true) {
            document.getElementsByName("compliance")[0].checked = true;
            document.getElementsByName("comment_compliance")[0].checked = true;
        }
        else {
            document.getElementsByName("compliance")[0].checked = false;
            document.getElementsByName("comment_compliance")[0].checked = false;
        }
    }

    function checkAllAssetMgmt(bx) {
        if (document.getElementsByName("check_asset_mgmt")[0].checked == true) {
            document.getElementsByName("asset")[0].checked = true;
        }
        else {
            document.getElementsByName("asset")[0].checked = false;
        }
    }

    function checkAllAssessments(bx) {
        if (document.getElementsByName("check_assessments")[0].checked == true) {
            document.getElementsByName("assessments")[0].checked = true;
        }
        else {
            document.getElementsByName("assessments")[0].checked = false;
        }
    }

    function checkAllConfigure(bx) {
        if (document.getElementsByName("check_configure")[0].checked == true) {
            document.getElementsByName("admin")[0].checked = true;
        }
        else {
            document.getElementsByName("admin")[0].checked = false;
        }
    }
</script>

<?php
    view_top_menu("Configure");

    // Get any alert messages
    get_alert();
?>
<div class="container-fluid">
    <div class="row-fluid">
        <div class="span3">
            <?php view_configure_menu("UserManagement"); ?>
        </div>
        <div class="span9">
            <div class="row-fluid">
                <div class="span12">
                    <div class="hero-unit">
                        <form name="add_user" method="post" action="">
                            <select name="empid" id= "empid" style = 'position: relative'>
                                <option>
                                    Employees search
                                </option>   
                            
                                <?php  
                                    
                                    
                                    $employees = get_employees();
                                    
                                    foreach($employees as $employee) {
                                       // $empnames = $employees['LastName'];  
                                    ?>
                            
                                      <option value="<?php echo $employee['EmployeeID']?>">
                                            <?php $GLOBALS['r']  = $GLOBALS['id'] + $employee['EmployeeID'] ?>
                                            <?php echo $employee['LastName'].'    '.$employee['FirstName']?>
                                      </option>
                                <?php } ?>
                            </select>
                                  </br>

                                  <!--input type="submit" name="add_user" value="Submit" -->

                                  </br>
                                  </br>
                            <table border="0" cellspacing="0" cellpadding="0">
                                <tr><td colspan="2"><h4><?php echo $escaper->escapeHtml($lang['AddANewUser']); ?>:</h4></td></tr>
                                <!--tr>
                                    <td>< ?php echo $escaper->escapeHtml($lang['Type']); ?>:&nbsp;</td>
                                    <td>
                                        <select name="type" id="select" onChange="handleSelection(value)">
                                            <option selected value="1">SimpleRisk</option>
                                            < ?php                                                // If the custom authentication extra is enabeld
                                                if (custom_authentication_extra())
                                                {
                                                    // Display the LDAP option
                                                    echo "<option value=\"2\">LDAP</option>\n";

                                                    // Display the SAML option
                                                    echo "<option value=\"3\">SAML</option>\n";
                                                }
                                            ?>
                                        </select>
                                    </td>
                                </tr-->
                                <!--tr><td>< ?php echo $escaper->escapeHtml($lang['FullName']); ?>:&nbsp;</td><td><input name="name" type="text" maxlength="50" size="20" value="< ?php echo isset($name) ? $escaper->escapeHtml($name) : "" ?>" /></td></tr-->
                                <!--tr><td>< ?php echo $escaper->escapeHtml($lang['EmailAddress']); ?>:&nbsp;</td><td><input name="email" type="text" maxlength="200" value="< ?php echo isset($email) ? $escaper->escapeHtml($email) : "" ?>" size="20" /></td></tr-->
                                <!--tr><td>< ?php echo $escaper->escapeHtml($lang['Username']); ?>:&nbsp;</td><td><input name="new_user" type="text" maxlength="200" value="< ?php echo isset($user) ? $escaper->escapeHtml($user) : "" ?>" size="20" /></td></tr-->
                                <tr class="ldap_pass"><td><?php echo $escaper->escapeHtml($lang['Password']); ?>:&nbsp;</td><td><input name="password" type="password" maxlength="50" size="20" autocomplete="off" /></td></tr>
                                <tr class="ldap_pass"><td><?php echo $escaper->escapeHtml($lang['RepeatPassword']); ?>:&nbsp;</td><td><input name="repeat_password" type="password" maxlength="50" size="20" autocomplete="off" /></td></tr>
                            </table>
                            <div>
                                <input name="change_password" id="change_password" <?php if(isset($change_password) && $change_password == 1) echo "checked"; ?> class="hidden-checkbox" type="checkbox" value="1" />  <label for="change_password">  &nbsp;&nbsp;&nbsp; <?php echo $escaper->escapeHtml($lang['RequirePasswordChangeOnLogin']); ?> </label> 
                            </div>

                            <h6>
                                <u><?php echo $escaper->escapeHtml($lang['Manager']); ?></u>
                            </h6>
                            <?php create_dropdown("user", "", "manager"); ?>

                            <h6><u><?php echo $escaper->escapeHtml($lang['Teams']); ?></u></h6>
                            <?php create_multiple_dropdown("team"); ?>

                            <h6><u><?php echo $escaper->escapeHtml($lang['Role']); ?></u></h6>
                            <?php create_dropdown("role", get_setting('default_user_role')); ?>
                            

                            <h6><u><?php echo $escaper->escapeHtml($lang['MultiFactorAuthentication']); ?></u></h6>
                            <input id="none" type="radio" name="multi_factor" value="1" checked />&nbsp;<?php echo $escaper->escapeHtml($lang['None']); ?><br />
                            <!--?php
                                // If the custom authentication extra is installed
                                //if (custom_authentication_extra())
                               // {
                                    // Include the custom authentication extra
                                //   require_once(realpath(__DIR__ . '/../extras/authentication/index.php'));

                                    // Display the multi factor authentication options
                                 //   multi_factor_authentication_options(1);
                             //   }
                            ?-->
                            <input type="submit" value="Submit" name="add_user" /><br />
                        </form>
                    </div>
                    <div class="hero-unit">
                        <form name="select_user" method="post" action="view_user_details.php">
                            <p>
                                <h4><?php echo $escaper->escapeHtml($lang['ViewDetailsForUser']); ?>:</h4>
                                <?php echo $escaper->escapeHtml($lang['DetailsForUser']); ?> <?php create_dropdown('enabled_users', null, 'user'); ?>&nbsp;&nbsp;<input type="submit" value="<?php echo $escaper->escapeHtml($lang['Select']); ?>" name="select_user" />
                            </p>
                        </form>
                    </div>
                    <div class="hero-unit">
                        <form name="enable_disable_user" method="post" action="">
                            <p>
                                <h4><?php echo $escaper->escapeHtml($lang['EnableAndDisableUsers']); ?>:</h4>
                                <?php echo $escaper->escapeHtml($lang['EnableAndDisableUsersHelp']); ?>.
                            </p>
                            <p>
                                <?php echo $escaper->escapeHtml($lang['DisableUser']); ?> <?php create_dropdown("enabled_users"); ?>&nbsp;&nbsp;<input type="submit" value="<?php echo $escaper->escapeHtml($lang['Disable']); ?>" name="disable_user" />
                            </p>
                            <p>
                                <?php echo $escaper->escapeHtml($lang['EnableUser']); ?> <?php create_dropdown("disabled_users"); ?>&nbsp;&nbsp;<input type="submit" value="<?php echo $escaper->escapeHtml($lang['Enable']); ?>" name="enable_user" />
                            </p>
                        </form>
                    </div>
                    <div class="hero-unit">
                        <form name="delete_user" method="post" action="">
                            <p>
                                <h4><?php echo $escaper->escapeHtml($lang['DeleteAnExistingUser']); ?>:</h4>
                                <?php echo $escaper->escapeHtml($lang['DeleteCurrentUser']); ?> <?php create_dropdown("user"); ?>&nbsp;&nbsp;<input type="submit" value="<?php echo $escaper->escapeHtml($lang['Delete']); ?>" name="delete_user" />
                            </p>
                        </form>
                    </div>
                    <!--div class="hero-unit">
                        <form name="password_reset" method="post" action="">
                            <p>
                                <h4>< ?php echo $escaper->escapeHtml($lang['PasswordReset']); ?>:</h4>
                                < ?php echo $escaper->escapeHtml($lang['SendPasswordResetEmailForUser']); ?> < ?php create_dropdown("user"); ?>&nbsp;&nbsp;<input type="submit" value="<?php echo $escaper->escapeHtml($lang['Send']); ?>" name="password_reset" />
                            </p>
                        </form>
                    </div-->
                    <div class="hero-unit">
                        <form name="password_policy" method="post" action="">
                            <p><h4><?php echo $escaper->escapeHtml($lang['UserPolicy']); ?>:</h4></p>
                            <p><input class="hidden-checkbox" type="checkbox" id="strict_user_validation" name="strict_user_validation"<?php if (get_setting('strict_user_validation') == 1) echo " checked" ?> /><label for="strict_user_validation">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['UseCaseSensitiveValidationOfUsername']); ?></label></p>

                            <br />
                            <p><h4><?php echo $escaper->escapeHtml($lang['AccountLockoutPolicy']); ?>:</h4></p>

                            <p><?php echo $escaper->escapeHtml($lang['MaximumAttemptsLockout']); ?>:&nbsp;&nbsp;<input style="width:50px" type="number" id="pass_policy_attempt_lockout" name="pass_policy_attempt_lockout" min="0" maxlength="2" size="2" value="<?php echo $escaper->escapeHtml(get_setting('pass_policy_attempt_lockout')); ?>"/> <?php echo $escaper->escapeHtml($lang['attempts']); ?>&nbsp;&nbsp;[0 = Lockout Disabled]</p>

                            <p><?php echo $escaper->escapeHtml($lang['MaximumAttemptsLockoutTime']); ?>:&nbsp;&nbsp;<input style="width:50px" type="number" id="pass_policy_attempt_lockout_time" name="pass_policy_attempt_lockout_time" min="0" maxlength="2" size="2" value="<?php echo $escaper->escapeHtml(get_setting('pass_policy_attempt_lockout_time')); ?>"/> <?php echo $escaper->escapeHtml($lang['minutes']); ?>.&nbsp;&nbsp;[0 = Manual Enable Required]</p>

                            <br />
                            <p><h4><?php echo $escaper->escapeHtml($lang['PasswordPolicy']); ?>:</h4></p>

                            <p><input class="hidden-checkbox" type="checkbox" id="pass_policy_enabled" name="pass_policy_enabled"<?php if (get_setting('pass_policy_enabled') == 1) echo " checked" ?> /><label for="pass_policy_enabled">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['Enabled']); ?></label></p>

                            <p><?php echo $escaper->escapeHtml($lang['MinimumNumberOfCharacters']); ?> <input style="width:50px" type="number" id="min_characters" name="min_characters" min="1" max="50" maxlength="2" size="2" value="<?php echo $escaper->escapeHtml(get_setting('pass_policy_min_chars')); ?>"/> [1-50]</p>

                            <p><input type="checkbox" class="hidden-checkbox" id="alpha_required" name="alpha_required"<?php if (get_setting('pass_policy_alpha_required') == 1) echo " checked" ?>  /><label for="alpha_required">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['RequireAlphaCharacter']); ?></label></p>

                            <p><input type="checkbox" class="hidden-checkbox" id="upper_required" name="upper_required"<?php if (get_setting('pass_policy_upper_required') == 1) echo " checked" ?>  /><label for="upper_required">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['RequireUpperCaseCharacter']); ?></label></p>

                            <p><input type="checkbox" class="hidden-checkbox" id="lower_required" name="lower_required"<?php if (get_setting('pass_policy_lower_required') == 1) echo " checked" ?>  /><label for="lower_required">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['RequireLowerCaseCharacter']); ?></label></p>

                            <p><input type="checkbox" class="hidden-checkbox" id="digits_required" name="digits_required"<?php if (get_setting('pass_policy_digits_required') == 1) echo " checked" ?>  /><label for="digits_required">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['RequireNumericCharacter']); ?></label></p>

                            <p><input type="checkbox" class="hidden-checkbox" id="special_required" name="special_required"<?php if (get_setting('pass_policy_special_required') == 1) echo " checked" ?> /><label for="special_required">&nbsp;&nbsp;<?php echo $escaper->escapeHtml($lang['RequireSpecialCharacter']); ?></p>

                            <p><?php echo $escaper->escapeHtml($lang['MinimumPasswordAge']); ?>:&nbsp;&nbsp;<input style="width:50px" type="number" id="pass_policy_min_age" name="pass_policy_min_age" min="0" maxlength="4" size="2" value="<?php echo $escaper->escapeHtml(get_setting('pass_policy_min_age')); ?>"/> <?php echo $escaper->escapeHtml($lang['days']); ?>.&nbsp;&nbsp;[0 = Min Age Disabled]</p>

                            <p><?php echo $escaper->escapeHtml($lang['MaximumPasswordAge']); ?>:&nbsp;&nbsp;<input style="width:50px" type="number" id="pass_policy_max_age" name="pass_policy_max_age" min="0" maxlength="4" size="2" value="<?php echo $escaper->escapeHtml(get_setting('pass_policy_max_age')); ?>"/> <?php echo $escaper->escapeHtml($lang['days']); ?>.&nbsp;&nbsp;[0 = Max Age Disabled]</p>

                            <p><?php echo $escaper->escapeHtml($lang['RememberTheLast']); ?>&nbsp;&nbsp;<input class="text-right" style="width:70px" type="number" id="pass_policy_reuse_limit" name="pass_policy_reuse_limit" min="0" maxlength="4" size="2" value="<?php echo $escaper->escapeHtml(get_setting('pass_policy_reuse_limit')); ?>"/> <?php echo $escaper->escapeHtml($lang['Passwords']); ?></p>

                            <p><input type="submit" value="<?php echo $escaper->escapeHtml($lang['Update']); ?>" name="password_policy_update" /></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php display_set_default_date_format_script(); ?>
</body>

</html>
