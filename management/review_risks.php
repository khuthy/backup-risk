<?php
    /* This Source Code Form is subject to the terms of the Mozilla Public
    * License, v. 2.0. If a copy of the MPL was not distributed with this
    * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

    // Include required functions file
    require_once(realpath(__DIR__ . '/../includes/functions.php'));
    require_once(realpath(__DIR__ . '/../includes/authenticate.php'));
    require_once(realpath(__DIR__ . '/../includes/display.php'));
    require_once(realpath(__DIR__ . '/../includes/alerts.php'));
    require_once(realpath(__DIR__ . '/../includes/permissions.php'));

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

    // Check for session timeout or renegotiation
    session_check();

    // Check if access is authorized
    if (!isset($_SESSION["access"]) || $_SESSION["access"] != "granted")
    {
        set_unauthenticated_redirect();
        header("Location: ../index.php");
        exit(0);
    }

    // Include the CSRF-magic library
    // Make sure it's called after the session is properly setup
    include_csrf_magic();

    // Enforce that the user has access to risk management
    enforce_permission_riskmanagement();

    // Record the page the workflow started from as a session variable
    $_SESSION["workflow_start"] = $_SERVER['SCRIPT_NAME'];

    // If reviewed is passed via GET
    if (isset($_GET['reviewed']))
    {
        // If it's true
        if ($_GET['reviewed'] == true)
        {
            // Display an alert
            set_alert(true, "good", "Risk review submitted successfully!");
        }
    }
    if (isset($_POST['update_control_risk']))
    {
        $control_id = (int)$_POST['control_id'];
        
        // If user has no permission to modify controls
        if(empty($_SESSION['modify_controls']))
        {
            // Display an alert
            set_alert(true, "bad", $escaper->escapeHtml($lang['NoModifyControlPermission']));
        }
        // Verify value is an integer
        elseif (is_int($control_id))
        {
            $control = array(
                'short_name' => isset($_POST['short_name']) ? $_POST['short_name'] : "",
                'long_name' => isset($_POST['long_name']) ? $_POST['long_name'] : "",
                'description' => isset($_POST['description']) ? $_POST['description'] : "",
                'supplemental_guidance' => isset($_POST['supplemental_guidance']) ? $_POST['supplemental_guidance'] : "",
                'framework_ids' => isset($_POST['frameworks']) ? $_POST['frameworks'] : "",
                'control_owner' => isset($_POST['control_owner']) ? (int)$_POST['control_owner'] : 0,
                'control_class' => isset($_POST['control_class']) ? (int)$_POST['control_class'] : 0,
                'control_phase' => isset($_POST['control_phase']) ? (int)$_POST['control_phase'] : 0,
                'control_number' => isset($_POST['control_number']) ? $_POST['control_number'] : "",
                'control_priority' => isset($_POST['control_priority']) ? (int)$_POST['control_priority'] : 0,
                'family' => isset($_POST['family']) ? (int)$_POST['family'] : 0,
                'mitigation_percent' => (isset($_POST['mitigation_percent']) && $_POST['mitigation_percent'] >= 0 && $_POST['mitigation_percent'] <= 100) ? (int)$_POST['mitigation_percent'] : 0
            );
            // Update the control
            update_framework_control($control_id, $control);
            
            // Display an alert
            set_alert(true, "good", "An existing control was updated successfully.");
        }
        // We should never get here as we bound the variable as an int
        else
        {
            // Display an alert
            set_alert(true, "bad", "The control ID was not a valid value.  Please try again.");
        }
        
        // Refresh current page
        refresh();
    }

?>

<!doctype html>
<html>

<head>
    <title>Risk Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">

    <script src="../js/jquery.min.js"></script>
    <script src="../js/jquery-ui.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/jquery.dataTables.js"></script>
    <script src="../js/cve_lookup.js"></script>
    <script src="../js/sorttable.js"></script>
    <script src="../js/common.js"></script>
    <script src="../js/pages/risk.js"></script>
    <script src="../js/highcharts/code/highcharts.js"></script>
    <script src="../js/moment.min.js"></script>
    <script src="../js/bootstrap-multiselect.js"></script>
    <script src="../js/jquery.blockUI.min.js"></script>
    <script src="../js/moment.min.js"></script>

    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="../css/bootstrap-responsive.css">
    <link rel="stylesheet" href="../css/jquery.dataTables.css">

    <link rel="stylesheet" href="../css/divshot-util.css">
    <link rel="stylesheet" href="../css/divshot-canvas.css">
    <link rel="stylesheet" href="../css/style.css">

    <link rel="stylesheet" href="../bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/theme.css">
    <link rel="stylesheet" href="../css/bootstrap-multiselect.css">

    <link rel="stylesheet" href="../css/selectize.bootstrap3.css">
    <script src="../js/selectize.min.js"></script>

    <?php
        setup_alert_requirements("..");
    ?>        

</head>

<body>

    <?php
        view_top_menu("RiskManagement");
    ?>
    <?php  
        // Get any alert messages
        get_alert();
    ?>
  
    <div class="tabs new-tabs">
        <div class="container-fluid">
          <div class="row-fluid">
            <div class="span3"> </div>
            <div class="span9">
              <div class="tab-append">
                <div class="tab selected form-tab tab-show new" >
                    <div>
                        <span>
                            <?php echo $escaper->escapeHtml($lang['RiskList']); ?>
                        </span>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row-fluid">
          <div class="span3">
            <?php view_risk_management_menu("ReviewRisksRegularly"); ?>
          </div>
          <div class="span9">
            <div id="tab-content-container" class="row-fluid">
                <div id="tab-container" class="tab-data">
                    <div class="row-fluid">
                        <div class="span12 ">
                            <p><?php echo $escaper->escapeHtml($lang['ReviewRegularlyHelp']); ?>.</p>
                            <?php // get_reviews_table(3); ?>
                            
                            <?php display_review_risks(); ?>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </div>
    <input type="hidden" id="_delete_tab_alert" value="<?php echo $escaper->escapeHtml($lang['Are you sure you want to close the risk? All changes will be lost!']); ?>">
    <input type="hidden" id="enable_popup" value="<?php echo get_setting('enable_popup'); ?>">

    <?php display_set_default_date_format_script(); ?>
  
</body>
</html>
