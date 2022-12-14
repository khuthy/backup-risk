<?php
    /* This Source Code Form is subject to the terms of the Mozilla Public
     * License, v. 2.0. If a copy of the MPL was not distributed with this
     * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

    // Include required functions file
    require_once(realpath(__DIR__ . '/../includes/functions.php'));
    require_once(realpath(__DIR__ . '/../includes/authenticate.php'));
    require_once(realpath(__DIR__ . '/../includes/display.php'));
    require_once(realpath(__DIR__ . '/../includes/alerts.php'));

    // Include Zend Escaper for HTML Output Encoding
    require_once(realpath(__DIR__ . '/../includes/Component_ZendEscaper/Escaper.php'));
    $escaper = new Zend\Escaper\Escaper('utf-8');

    // Add various security headers
    add_security_headers();

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
  // If the user has been updated
  if (isset($_POST['update_user']) && isset($_POST['user']))
  {
      // Get the user ID
      $user_id = (int)$_POST['user'];

      // Verify the user ID value is an integer
      if (is_int($user_id))
      {
          // Get the submitted values
          $lockout          = isset($_POST['lockout']) ? '1' : '0';
          $type             = 1;
          $name             = $_POST['name'];
          $email            = $_POST['email'];
          $manager          = (int)$_POST['manager'];
          $teams            = isset($_POST['team']) ? $_POST['team'] : array('none');
          $role_id          = (int)$_POST['role'];
          $language         = 1;
          $governance       = isset($_POST['governance']) ? '1' : '0';
          $riskmanagement   = isset($_POST['riskmanagement']) ? '1' : '0';
          $compliance       = isset($_POST['compliance']) ? '1' : '0';
          $assessments      = isset($_POST['assessments']) ? '1' : '0';
          $asset            = isset($_POST['asset']) ? '1' : '0';
          $admin            = isset($_POST['admin']) ? '1' : '0';
          $submit_risks     = isset($_POST['submit_risks']) ? '1' : '0';
          $modify_risks     = isset($_POST['modify_risks']) ? '1' : '0';
          $close_risks      = isset($_POST['close_risks']) ? '1' : '0';
          $plan_mitigations     = isset($_POST['plan_mitigations']) ? '1' : '0';
          $review_veryhigh      = isset($_POST['review_veryhigh']) ? '1' : '0';
          $accept_mitigation    = isset($_POST['accept_mitigation']) ? '1' : '0';
          $review_high          = isset($_POST['review_high']) ? '1' : '0';
          $review_medium        = isset($_POST['review_medium']) ? '1' : '0';
          $review_low           = isset($_POST['review_low']) ? '1' : '0';
          $review_insignificant = isset($_POST['review_insignificant']) ? '1' : '0';
          $multi_factor         = 1;
          $change_password      = (int)(isset($_POST['change_password']) ? $_POST['change_password'] : 0);
          
          $add_new_frameworks       = isset($_POST['add_new_frameworks']) ? 1 : 0;
          $modify_frameworks        = isset($_POST['modify_frameworks']) ? 1: 0;
          $delete_frameworks        = isset($_POST['delete_frameworks']) ? 1 : 0;
          $add_new_controls         = isset($_POST['add_new_controls']) ? 1 : 0;
          $modify_controls          = isset($_POST['modify_controls']) ? 1 : 0;
          $delete_controls          = isset($_POST['delete_controls']) ? 1 : 0;
          $add_documentation        = isset($_POST['add_documentation']) ? 1 : 0;
          $modify_documentation     = isset($_POST['modify_documentation']) ? 1 : 0;
          $delete_documentation     = isset($_POST['delete_documentation']) ? 1 : 0;
          $comment_risk_management  = isset($_POST['comment_risk_management']) ? 1 : 0;
          $comment_compliance       = isset($_POST['comment_compliance']) ? 1 : 0;

          $view_exception           = isset($_POST['view_exception']) ? 1 : 0;
          $create_exception         = isset($_POST['create_exception']) ? 1 : 0;
          $update_exception         = isset($_POST['update_exception']) ? 1 : 0;
          $delete_exception         = isset($_POST['delete_exception']) ? 1 : 0;
          $approve_exception        = isset($_POST['approve_exception']) ? 1 : 0;

          // Change the type from a numeric to alpha
          switch($type){
            case "1":
              $type = "simplerisk";
              break;
            case "2":
              $type = "ldap";
              break;
            case "3":
              $type = "saml";
              break;
            default:
              $type = "simplerisk";
          }

          // Create a boolean for all
          $all = false;

          // Create a boolean for none
          $none = false;

          // Set the team to empty to start
          $team = "";

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
                "manager" =>          $manager
            ];

          // Update the user
          update_user($user_id, $lockout, $type, $name, $email, $team, $role_id, $language, $governance, $riskmanagement, $compliance, $assessments, $asset, $admin, $review_veryhigh, $accept_mitigation, $review_high, $review_medium, $review_low, $review_insignificant, $submit_risks, $modify_risks, $plan_mitigations, $close_risks, $multi_factor, $change_password, $add_new_frameworks, $modify_frameworks, $delete_frameworks, $add_new_controls, $modify_controls, $delete_controls, $other_options);

          // Display an alert
          set_alert(true, "good", "The user was updated successfully.");
      }
  }

  // Check if a userid was sent
  if (isset($_POST['user']))
  {
      // Get the user ID
      $user_id = (int)$_POST['user'];

      // Get the users information
      $user_info = get_user_by_id($user_id);
      
      $enabled = $user_info['enabled'];
      $lockout = $user_info['lockout'];
      $type = $user_info['type'];
      $username = $user_info['username'];
      $name = $user_info['name'];
      $email = $user_info['email'];
      $last_login = $user_info['last_login'];
      $language = $user_info['lang'];
      $teams = $user_info['teams'];
      $role_id = $user_info['role_id'];
      $admin = $user_info['admin'];
      $manager = $user_info['manager'];
                
      $governance = $user_info['governance'];
      $riskmanagement = $user_info['riskmanagement'];
      $compliance = $user_info['compliance'];
      $assessments = $user_info['assessments'];
          
      $asset = $user_info['asset'];
          
      $accept_mitigation = $user_info['accept_mitigation'];
      $review_veryhigh = $user_info['review_veryhigh'];
      $review_high = $user_info['review_high'];
      $review_medium = $user_info['review_medium'];
      $review_low = $user_info['review_low'];
                
      $review_insignificant = $user_info['review_insignificant'];
      $submit_risks = $user_info['submit_risks'];
      $modify_risks = $user_info['modify_risks'];
      $close_risks = $user_info['close_risks'];
      $plan_mitigations = $user_info['plan_mitigations'];
      $multi_factor = $user_info['multi_factor'];
      $change_password = $user_info['change_password'];

      $add_new_frameworks = $user_info['add_new_frameworks'];
      $modify_frameworks = $user_info['modify_frameworks'];
      $delete_frameworks = $user_info['delete_frameworks'];
      $add_new_controls = $user_info['add_new_controls'];
      $modify_controls = $user_info['modify_controls'];
      $delete_controls = $user_info['delete_controls'];
      $add_documentation = $user_info['add_documentation'];
      $modify_documentation = $user_info['modify_documentation'];
      $delete_documentation = $user_info['delete_documentation'];
      $comment_risk_management = $user_info['comment_risk_management'];
      $comment_compliance = $user_info['comment_compliance'];

      $view_exception = $user_info['view_exception'];
      $create_exception = $user_info['create_exception'];
      $update_exception = $user_info['update_exception'];
      $delete_exception = $user_info['delete_exception'];
      $approve_exception = $user_info['approve_exception'];
  }
  else
  {
      $user_id = "";
      $enabled = 0;
      $lockout = false;
      $type       = "N/A";
      $username   = "N/A";
      $name       = "N/A";
      $email      = "N/A";
      $last_login = "N/A";
      $language   = "N/A";
      $teams      = "none";
      $role_id    = "";
      $admin      = false;
      $manager      = false;
                  
      $governance = false;
      $riskmanagement = false;
      $compliance = false;
      $assessments = false;
          
      $asset = false;
          
      $accept_mitigation = false;
      $review_veryhigh = false;
      $review_high    = false;
      $review_medium  = false;
      $review_low     = false;
                
      $review_insignificant = false;
      $submit_risks       = false;
      $modify_risks       = false;
      $close_risks        = false;
      $plan_mitigations   = false;
      $multi_factor       = 1;

      $add_new_frameworks = false;
      $modify_frameworks = false;
      $delete_frameworks = false;
      $add_new_controls = false;
      $modify_controls = false;
      $delete_controls = false;
      $add_documentation = false;
      $modify_documentation = false;
      $delete_documentation = false;
      $comment_risk_management = false;
      $comment_compliance = false; 

      $view_exception = false;
      $create_exception = false;
      $update_exception = false;
      $delete_exception = false;
      $approve_exception = false;
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
        })
    });

    function setUserResponsibilitesByRole(){
        // If role is unselected, uncheck all responsibilities
        if(!$("#role").val())
        {
            $(".checklist input[type=checkbox]").prop("checked", false);
            return;
        }
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
        $("[type=checkbox]", $(bx).parents('table')).prop('checked', $(bx).is(":checked"));
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
                            <form name="update_user" method="post" action="">
                                <input name="user" type="hidden" value="<?php echo $escaper->escapeHtml($user_id); ?>" />
                                <table border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td colspan="2"><h4>Update an Existing User:</h4></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><input class="hidden-checkbox" name="lockout" id="lockout" type="checkbox"<?php if ($lockout) echo " checked" ?> /> <label for="lockout">  &nbsp;&nbsp;&nbsp; <?php echo $lang['AccountLockedOut']; ?></label> </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">                                
                                            <input name="change_password" id="change_password" <?php if(isset($change_password) && $change_password == 1) echo "checked"; ?> class="hidden-checkbox" type="checkbox" value="1" />  <label for="change_password">  &nbsp;&nbsp;&nbsp; <?php echo $escaper->escapeHtml($lang['RequirePasswordChangeOnLogin']); ?> </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $escaper->escapeHtml($lang['Status']); ?>:&nbsp;</td>
                                        <td><b><?php echo ($enabled == 1 ? $escaper->escapeHtml($lang['Enabled']) : $escaper->escapeHtml($lang['Disabled'])); ?></b></td>
                                    </tr>
                                    <!--tr>
                                        <td>< ? php echo $escaper->escapeHtml($lang['Type']); ?>:&nbsp;</td>
                                        <td>
                                        <select name="type" id="select">
                                        <option value="1"< ? php echo ($type == "simplerisk" ? " selected" : ""); ?>>Geoscience</option>
                                        < ?php
                                        // If the custom authentication extra is enabeld
                                        if (custom_authentication_extra())
                                        {
                                            // Display the LDAP option
                                            echo "<option value=\"2\"" . ($type == "ldap" ? " selected" : "") . ">LDAP</option>\n";

                                            // Display the SAML option
                                            echo "<option value=\"3\"" . ($type == "saml" ? " selected" : "") . ">SAML</option>\n";
                                        }
                                        ?>
                                        </select>
                                        </td>
                                    </tr-->
                                    <tr>
                                        <td><?php echo $escaper->escapeHtml($lang['FullName']); ?>:&nbsp;</td>
                                        <td><input name="name" type="text" maxlength="50" size="20" value="<?php echo $escaper->escapeHtml($name); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $escaper->escapeHtml($lang['EmailAddress']); ?>:&nbsp;</td>
                                        <td><input name="email" type="email" maxlength="200" size="20" value="<?php echo $escaper->escapeHtml($email); ?>" /></td>
                                        </tr>
                                    <tr>
                                        <td><?php echo $escaper->escapeHtml($lang['Username']); ?>:&nbsp;</td>
                                        <td><input style="cursor: default;" name="username" type="text" size="20" title="<?php echo $escaper->escapeHtml($username); ?>" disabled="disabled" value="<?php echo $escaper->escapeHtml($username); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $escaper->escapeHtml($lang['LastLogin']); ?>:&nbsp;</td>
                                        <td><input style="cursor: default;" name="last_login" type="text" maxlength="20" size="20" title="<?php echo $escaper->escapeHtml($last_login); ?>" disabled="disabled" value="<?php echo $escaper->escapeHtml($last_login); ?>" /></td>
                                        </tr>
                                    <!--tr>
                                        <td>< ?php echo $escaper->escapeHtml($lang['Language']); ?>:&nbsp;</td>
                                        <td>< ?php create_dropdown("languages", get_value_by_name("languages", $language)); ?></td>
                                    </tr-->
                                </table>

                                <h6>
                                    <u><?php echo $escaper->escapeHtml($lang['Manager']); ?></u>
                                </h6>
                                <?php create_dropdown("user", $manager, "manager"); ?>

                                <h6>
                                    <u><?php echo $escaper->escapeHtml($lang['Teams']); ?></u>
                                </h6>
                                <?php create_multiple_dropdown("team", $teams); ?>

                                <h6><u><?php echo $escaper->escapeHtml($lang['Role']); ?></u></h6>
                                <?php create_dropdown("role", $role_id); ?>

                                

                                <input type="submit" value="<?php echo $escaper->escapeHtml($lang['Update']); ?>" name="update_user" /><br />
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
