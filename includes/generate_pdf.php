<?php

    //namespace Dompdf;
    //require_once 'dompdf/autoload.inc.php';
    //require_once(realpath(__DIR__ . '/dompdf/autoload.inc.php'));
    require_once(realpath(__DIR__ . '/../includes/functions.php'));
    require_once(realpath(__DIR__ . '/../includes/authenticate.php'));
    require_once(realpath(__DIR__ . '/../includes/display.php'));
    require_once(realpath(__DIR__ . '/../includes/reporting.php'));
    ////require_once(realpath(__DIR__ . '/../includes/generate_pdf.php'));
    require_once(realpath(__DIR__ . '/../includes/dompdf/autoload.inc.php'));
    // require_once(realpath(__DIR__ . '/../reports/dynamic_risk_report.php'));
    //require_once(realpath(__DIR__ . '/../includes/dompdf/dompdf_config.inc.php'));
    // Include Zend Escaper for HTML Output Encoding
    require_once(realpath(__DIR__ . '/../includes/Component_ZendEscaper/Escaper.php'));

    $escaper = new \Zend\Escaper\Escaper('utf-8');

    use Dompdf\Dompdf;
    use FontLib\Encoding_Map;

    add_security_headers();
    ///////////////////////////////////////////////////////////////////////
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

    // Check if access is authorized
    // if (!isset($_SESSION["access"]) || $_SESSION["access"] != "granted")
    // {
    //     $access= "access";
    //     $_SESSION[$access];
    //     // set_unauthenticated_redirect();
    //     // header("Location: ../index.php");
    //     // exit(0);
    // }


    
    // Record the page the workflow started from as a session variable
    $_SESSION["workflow_start"] = $_SERVER['SCRIPT_NAME'];

    // Set the status
    if (isset($_POST['status']))
    {
        $status = (int)$_POST['status'];
    }
    else if (isset($_GET['status']))
    {
        $status = (int)$_GET['status'];
    }
    else $status = 0;

    if (isset($_POST['affected_assets_filter']))
    {
        $affected_assets_filter = empty($_POST['affected_assets_filter']) ? [] : $_POST['affected_assets_filter'];
    }
    else if (isset($_GET['affected_assets_filter']))
    {
        $affected_assets_filter = empty($_GET['affected_assets_filter']) ? [] : $_GET['affected_assets_filter'];
    }
    else $affected_assets_filter = [];

    $processed_affected_assets_filter = [];

    if (!empty($affected_assets_filter)) {
        $processed_affected_assets_filter = array('group'=>[], 'asset'=>[]);
        foreach($affected_assets_filter as $asset_filter) {
            if (preg_match('/^([\d]+)_(group|asset)$/', $asset_filter, $matches)) {
                list(, $id, $type) = $matches;
                
                array_push($processed_affected_assets_filter[$type], (int)$id);
            }
        }
    }

    // Set the group
    if (isset($_POST['group']))
    {
        $group = (int)$_POST['group'];
    }
    else if (isset($_GET['group']))
    {
        $group = (int)$_GET['group'];
    }
    else $group = 0;

    // Set the sort
    if (isset($_POST['sort']))
    {
        $sort = (int)$_POST['sort'];
    }
    else if (isset($_GET['sort']))
    {
        $sort = (int)$_GET['sort'];
    }
    else $sort = 0;

    // Set the Tags
    if (isset($_POST['tags_filter']))
    {
        $tags_filter = empty($_POST['tags_filter']) ? [] : array_map('intval', $_POST['tags_filter']);
    }
    else if (isset($_GET['tags_filter']))
    {
        $tags_filter = empty($_GET['tags_filter']) ? [] : array_map('intval', $_GET['tags_filter']);
    }
    else $tags_filter = [];

    // Set the locations
    if (isset($_POST['locations_filter']))
    {
        $locations_filter = empty($_POST['locations_filter']) ? [] : array_map('intval', $_POST['locations_filter']);
    }
    else if (isset($_GET['locations_filter']))
    {
        $locations_filter = empty($_GET['locations_filter']) ? [] : array_map('intval', $_GET['locations_filter']);
    }
    else $locations_filter = [];

    // Names list of Risk columns
    $columns = array(
        'id',
        'risk_status',
        'subject',
        'reference_id',
        'regulation',
        'control_number',
        'location',
        'source',
        'category',
        'team',
        'additional_stakeholders',
        'technology',
        'owner',
        'manager',
        'submitted_by',
        'scoring_method',
        'calculated_risk',
        'residual_risk',
        'submission_date',
        'review_date',
        'project',
        'mitigation_planned',
        'management_review',
        'days_open',
        'next_review_date',
        'next_step',
        'affected_assets',
        'planning_strategy',
        'planning_date',
        'mitigation_effort',
        'mitigation_cost',
        'mitigation_owner',
        'mitigation_team',
        'mitigation_accepted',
        'mitigation_date',
        'mitigation_controls',
        'risk_assessment',
        'additional_notes',
        'current_solution',
        'security_recommendations',
        'security_requirements',
        'risk_tags',
        'closure_date'
    );

    $custom_values = [];

    if(!empty($_GET['selection']))
    {
        $selection_id = isset($_GET['selection']) ? (int)$_GET['selection'] : 0;
        $selection = get_dynamic_saved_selection($selection_id);
        
        if($selection['type'] == 'private' && $selection['user_id'] != $_SESSION['uid'])
        {
            set_alert(true, "bad", $escaper->escapeHtml($lang['NoPermissionForThisSelection']));
            refresh("/reports/generate_pdf.php");
        }
        else
        {
            if($selection['custom_display_settings'])
            {
                $custom_display_settings = json_decode($selection['custom_display_settings'], true);
            }
            else
            {
                $custom_display_settings = "";
            }
        }
    }
    else
    {
        
        $custom_display_settings = $_SESSION['custom_display_settings'];
        
    }

    
    if(is_array($custom_display_settings) && !isset($_POST['status'])){
        foreach($columns as $column){
            ${$column} = in_array($column, $custom_display_settings) ? true : false;
        }
        foreach($custom_display_settings as $custom_display_setting){
            if(stripos($custom_display_setting, "custom_field_") === 0){
                $custom_values[$custom_display_setting] = 1;
            }
        }
    }elseif(isset($_POST['status'])){
        foreach($columns as $column){
            ${$column} = isset($_POST[$column]) ? true : false;
        }
        foreach($_POST as $key=>$val){
            if(stripos($key, "custom_field_") === 0){
                $custom_values[$key] = 1;
            }
        }
    }else{
        $id = true;
        $subject = true;
        $calculated_risk = true;
        $residual_risk = true;
        $submission_date = true;
        $mitigation_planned = true;
        $management_review = true;
    }

    // If there was not a POST
    if (!isset($_POST['status']))
    {
        // Set the default fields to show
       $id = true;
       $subject = true;
       $calculated_risk = true;
       $submission_date = true;
       $mitigation_planned = true;
       $management_review = true;
    }


    // // Session handler is database
    // if (USE_DATABASE_FOR_SESSIONS == "true")
    // {
    //     session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy', 'sess_gc');
    // }

    // Start the session
    //session_set_cookie_params(0, '/', '', isset($_SERVER["HTTPS"]), true);

    //session_name('SimpleRisk');
    //session_start();





function gettheheader($status, $sort, $group,$processed_affected_assets_filter, $affected_assets_filter, $tags_filter, $locations_filter, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $custom_values)
{
    // global $lang;
    // global $escaper;
    $html ='<!DOCTYPE html>';
    $html ='<html lang="en" xml:lang="en">';     
        $html .='<head>'; 
            $html .='<script src="../js/jquery.min.js"></script>';
            $html .='<script src="../js/bootstrap.min.js"></script>';
            $html .='<script src="../js/sorttable.js"></script>';
            $html .='<script src="../js/obsolete.js"></script>';
            $html .='<script src="../js/jquery.dataTables.js"></script>';
            $html .='<script src="../js/dynamic.js"></script>';
            $html .='<script src="../js/common.js"></script>';
            $html .='<script src="../js/bootstrap-multiselect.js"></script>';
            $html .='<title>Risk Management System</title>';
            $html .='<meta name="viewport" content="width=device-width, initial-scale=1">';
            $html .='<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">';
            $html .='<link rel="stylesheet" href="../css/bootstrap.css">';
            $html .='<link rel="stylesheet" href="../css/bootstrap-responsive.css">';
            $html .='<link rel="stylesheet" href="../css/jquery.dataTables.css">';   
            $html .='<link rel="stylesheet" href="../css/divshot-canvas.css">';
            $html .='<link rel="stylesheet" href="../bower_components/font-awesome/css/font-awesome.min.css">';
            $html .='<link rel="stylesheet" href="../css/theme.css">';
            $html .='<link rel="stylesheet" href="../css/print.css">';
        $html .='</head>';      
        $html .='<body>';
            $html .='<div class="container-fluid">';
                $html .='<div class="row-fluid">';
                    $html .='<div class="span12">';

                        $html .='<div class="row-fluid>';
                            $html .='<div id="selections" class="span12">';
                                $html .='<div class="well">';
                                //     $html .='<div id="selection-container">';
                                            
                                //   //     view_get_risks_by_selections($status, $group, $sort, $affected_assets_filter, $tags_filter, $locations_filter, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $custom_values);
                                //     $html .='</div>';  
                                                                
                                    $html .='<div id="save-container">';                             
                                        display_save_dynamic_risk_selections();                                    
                                    $html .='</div>';
                                $html .='</div>';
                            $html .='</div>'; 
                        $html .='</div>';    

                        $html .='<div class="row-fluid bottom-offset-11">';
                            $html .='<div class="span6 text-left top-offset-15">';
                                $html .='<button class="expand-all">Expand All</button>';
                                 // <!-- <button class="printdoc">< ?php echo $lang['Printdoc'] ? ></button> -->                 
                            $html .='</div>';
                        $html .='</div>';

                        $html .='<div class="row-fluid>';
                                $html .='<div class="span12">';
                                    $html .='<div id="risk-table-container">';
                                        // $output = getheader();
                                        get_risks_by_table($status, $sort, $group, $processed_affected_assets_filter, $tags_filter, $locations_filter, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $custom_values);
                                    $html .='</div>';
                                $html .='</div>';
                        $html .='</div>';

                    $html .='</div>';
                $html .='</div>';
            $html .='</div>';
        $html .='</body>';

    $html .='</html>';


    return $html;
}     

      
      $output = html_entity_decode(gettheheader($status, $sort, $group, $processed_affected_assets_filter,$affected_assets_filter ,$tags_filter, $locations_filter, $id, $risk_status, $subject, $reference_id, $regulation, $control_number, $location, $source, $category, $team, $additional_stakeholders, $technology, $owner, $manager, $submitted_by, $scoring_method, $calculated_risk, $residual_risk, $submission_date, $review_date, $project, $mitigation_planned, $management_review, $days_open, $next_review_date, $next_step, $affected_assets, $planning_strategy, $planning_date, $mitigation_effort, $mitigation_cost, $mitigation_owner, $mitigation_team, $mitigation_accepted, $mitigation_date, $mitigation_controls, $risk_assessment, $additional_notes, $current_solution, $security_recommendations, $security_requirements, $risk_tags, $closure_date, $custom_values));

       //print ($output);
       //  
       $output2 = htmlspecialchars($output);
       //ob_flush();

        header('Access-Control-Allow-Origin: *'); //to get data from firefox addon
        $dompdf = new DOMPDF();
        $dompdf->loadhtml($output); 
        $dompdf->setPaper('A4');
        $dompdf->render();    
        //$pdf = $dompdf->output();
        $invnoabc = 'Risk report.pdf';
        // ob_end_clean();
        if(!empty($_GET['print']))
        {
                $dompdf->stream($invnoabc, array('compress'=>1,"Attachment" => 0));
            }
            else {
            //   ob_end_clean(); 
                $dompdf-> stream($invnoabc, array("Attachment" => 1));
            }
            exit;

?>