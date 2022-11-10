<?php 


$curl = curl_init();

//$userurl = "‪C:\json.txt"

//curl_setopt($curl,CURLOPT_URL,$userurl);
//curl_setopt($curl,CURLOPT_RETURNTRANSFER,true)

//$respon = curl_exec($curl);

if($e = curl_errno($curl))
{
    echo $e;
}
else{

    //$obj = json_decode($respon); //create objects
    //$obj2 = json_decode($respon,true); //create objects
} 


$employeesjson = file_get_contents("C:\json.json");
// Convert to array 
$employeesarray = json_decode($employeesjson, true);

$employees = $employeesarray['value'];

//echo $array->$values[1].LastName;
//echo $array[1].id;

foreach($employees as $employee) {
    echo 'ID      : '.$employee['EmployeeID'];
    echo 'surname : '.$employee['LastName']; 
    echo 'Name    : '.$employee['FirstName']; 
    echo 'email   : '.$employee['eMail']; 

    echo '/n';
    $department  = $employee['Department2'];

    echo 'Department   : '.$department['Name'];
}

?>