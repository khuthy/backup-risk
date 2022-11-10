<?php



function get_employees(){
   
   //dummy api localized
  $api_url = 'C:\users.json';

  //real sap b1 api
 $api_url = 'http://172.16.6.115:8084/api/users';

  $employeesjson = file_get_contents($api_url);  

  $employeesarray = json_decode($employeesjson, true);
  
  $employees = $employeesarray['value'];

  return $employees;
}

function get_departments(){
  
   //dummy api localized
  $api_url = 'C:\departments.json';

  //real sap b1 api
  $api_url = 'http://172.16.6.115:8084/api/departments';

  $departmentsjson = file_get_contents($api_url);  

  $departmentsarray = json_decode($departmentsjson, true);
  
  $departments = $departmentsarray['value'];

  return $departments;
}

?>