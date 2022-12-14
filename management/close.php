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

// Enforce that the user has access to risk management
enforce_permission_riskmanagement();

    // Check if the user has access to close risks
    if (!isset($_SESSION["close_risks"]) || $_SESSION["close_risks"] != 1)
    {
            $close_risks = false;

    // Display an alert
    set_alert(true, "bad", "You do not have permission to close risks.  Any attempts to close risks will not be recorded.  Please contact an Administrator if you feel that you have reached this message in error.");
    }
    else $close_risks = true;

    // Check if a risk ID was sent
    if (isset($_GET['id']) || isset($_POST['id']))
    {
            if (isset($_GET['id']))
            {
                    // Test that the ID is a numeric value
                    $id = (is_numeric($_GET['id']) ? (int)$_GET['id'] : 0);
            }
            else if (isset($_POST['id']))
            {
                    // Test that the ID is a numeric value
                    $id = (is_numeric($_POST['id']) ? (int)$_POST['id'] : 0);
            }

            // If team separation is enabled
            if (team_separation_extra())
            {
                    //Include the team separation extra
                    require_once(realpath(__DIR__ . '/../extras/separation/index.php'));

                    // If the user should not have access to the risk
                    if (!extra_grant_access($_SESSION['uid'], $id))
                    {
                            // Redirect back to the page the workflow started on
                            header("Location: " . $_SESSION["workflow_start"]);
                            exit(0);
                    }
            }

            // Get the details of the risk
            $risk = get_risk_by_id($id);

            // If the risk was found use the values for the risk
            if (count($risk) != 0)
            {
                $status = $risk[0]['status'];
                $subject = $risk[0]['subject'];
                $calculated_risk = $risk[0]['calculated_risk'];
            }
            // If the risk was not found use null values
            else
            {
                // If Risk ID exists.
                if(check_risk_by_id($id)){
                    $status = $lang["RiskDisplayPermission"];
                }
                // If Risk ID does not exist.
                else{
                    $status = $lang["RiskIdDoesNotExist"];
                }
                $subject = "N/A";
                $calculated_risk = "0.0";
            }
            
            // Get the mitigation for the risk
            $mitigation = get_mitigation_by_id($id);
            if ($mitigation == true){
                $mitigation_percent = isset($mitigation[0]['mitigation_percent']) ? $mitigation[0]['mitigation_percent'] : 0;
            }
            else
            {
                $mitigation_percent = 0;
            }

    }

    // Check if a risk closure was submitted and the user has permissions to close risks
    if ((isset($_POST['submit'])) && $close_risks)
    {
            $status = "Closed";
            $close_reason = $_POST['close_reason'];
            $note = $_POST['note'];

    // Submit a review
    submit_management_review($id, $status, "", "", $_SESSION['uid'], $note, "0000-00-00", true);

            // Close the risk
            close_risk($id, $_SESSION['uid'], $status, $close_reason, $note);

    // Display an alert
            set_alert(true, "good", "Your risk has now been marked as closed.");

            // Check that the id is a numeric value
            if (is_numeric($id))
            {
                    // Create the redirection location
        $url = "view.php?id=" . $id;

                    // Redirect to plan mitigations page
                    header("Location: " . $url);
            }
    }
?>

<!doctype html>
<html>

<head>
<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<title>Risk Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
<link rel="stylesheet" href="../css/bootstrap.css">
<link rel="stylesheet" href="../css/bootstrap-responsive.css">
</head>

<body>
<title>Risk Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
<link rel="stylesheet" href="../css/bootstrap.css">
<link rel="stylesheet" href="../css/bootstrap-responsive.css">
<link rel="stylesheet" href="../css/divshot-util.css">
<link rel="stylesheet" href="../css/divshot-canvas.css">
<link rel="stylesheet" href="../css/display.css">

<link rel="stylesheet" href="../bower_components/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="../css/theme.css">

<?php
view_top_menu("RiskManagement");
?>
<div class="container-fluid">
  <div class="row-fluid">
    <div class="span3">
      <?php view_risk_management_menu("ReviewRisksRegularly"); ?>
    </div>
    <div class="span9">
      <div class="row-fluid">
        <div class="well">
          <?php view_top_table($id, $calculated_risk, $subject, $status, false, $mitigation_percent); ?>
        </div>
      </div>
      
      <?php 
          include(realpath(__DIR__ . '/partials/close.php'));
      ?>
      
    </div>
  </div>
</div>
</body>

</html>
