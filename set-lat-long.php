<html>
<head>
  <meta charset="UTF-8">
  <title>test</title>
</head>
<body>
<?php
require 'PhpSpreadsheet/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$api_key = 'PUT_KEY_HERE'; //temp key

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load("real.xls");

/*
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load("patients.xlsx");
*/

$worksheet = $spreadsheet->getActiveSheet();
$rows = $worksheet->toArray();

foreach($rows as $key => $value) {
    // key is the row count(starts from 0)
    // array of values
    
    //skip 1st row
    if($key == 0) {
      continue;
    } 
    //else if ($key == 10) { exit; }
    //echo "$value <br>";
    
    $project_code = $value[0];
    $study_id     = $value[1];
    $patient_id   = $value[2];
    $address      = $value[3];
    $city         = $value[4];
    $state        = $value[5];
    $zip          = $value[6];
    $country      = $value[7];
    $lat          = $value[8];
    $long         = $value[9];
/*
    echo <<< END
    project_code: $project_code <br/>
    study_id    : $study_id     <br/>
    patient_id  : $patient_id   <br/>
    address     : $address      <br/>
    city        : $city         <br/>
    state       : $state        <br/>    
    zip         : $zip          <br/>
    country     : $country      <br/>
    lat         : $lat          <br/>
    long        : $long         <br/>
END;
*/
    
    if($lat=='' || $lat=='0.0'){//only proceed for people who do not have sites
      //echo "PROCESS $patient_id $lat<br>";    
      
      //use on test-screener website:      
      $full_address = ($address=='' ? '' : (urlencode($address).',+') ) . urlencode($city)  .',+'. urlencode($state) .'+'. urlencode($zip);
      $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='. $full_address .'&region='. urlencode($country) .'&key='. $api_key;
      //$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=Toledo&region=es&key='. $api_key;
//echo $url .'<br/>';

      //$output = file_get_contents($url);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
      $output = curl_exec($ch);   
      // convert response
//echo $output;
      $output = json_decode($output, true);
      // handle error; error output
      if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
        var_dump($output);
        exit;
      }
       
      sleep(3);
      if (isset($output['status']) && ($output['status'] == 'OK')) {
        $latitude  = $output['results'][0]['geometry']['location']['lat']; // Latitude
        $longitude = $output['results'][0]['geometry']['location']['lng']; // Longitude
        echo "UPDATE patient SET latitude=$latitude, longitude=$longitude where patient_id=$patient_id;<br/>";
      } else {
        echo "-- SKIPPING $patient_id, prob bad address data or unusual country... country: $country ... address: $address, city: $city, state: $state, zip: $zip<br/>";
      }
    }
    //flush();
    //ob_flush();
        
//echo '##############################<br/>';
};



curl_close($ch);     
?>
</body>
</html>
