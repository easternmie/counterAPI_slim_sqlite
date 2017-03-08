<?php
$py_code_path = "/home/pi/pc_final";
$collector_server_ip = "";
// include($py_code_path.'/collector_server_ip.php'); // load new collector_server_ip
include($py_code_path.'/collector_server_ip.php'); // load new collector_server_ip

if($collector_server_ip == "" || $collector_server_ip == '' || $collector_server_ip == null){
  $collector_server_ip = "10.20.125.3"; // default ip cloud
}


// header('Content-Type: application/json');
date_default_timezone_set("Asia/Kuala_Lumpur"); 
  try
  {
    //open the database
    $db = new PDO('sqlite:/var/www/html/counterAPI_slim_sqlite/v1/db.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT id_location, last_backup FROM location LIMIT 1;");

    $stmt->execute();

    $response = array();
    $currenttimestamp = ""; 
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      // echo $row['id_location'];
      // echo $row['last_backup'];
      $id_location = $row['id_location'];
      $last_backup = $row['last_backup'];
      if($last_backup == null || $last_backup == '' || empty($last_backup) || $last_backup == "null"){
          $currenttimestamp = "2000-06-28 12:50:00";
      }
      else{
          $currenttimestamp = $last_backup;
      }
      // TODO process backup goes here....


      // $sth = $db->prepare('SELECT * FROM livefeed WHERE currenttimestamp >= :currenttimestamp');
      $sth = $db->prepare('SELECT * FROM livefeed WHERE currenttimestamp > :currenttimestamp');

      $sth->bindParam(":currenttimestamp", $currenttimestamp);

      $sth->execute();
      $response = array();
      $tmp = array();

      $a = 0;

      while ($row_livefeed = $sth->fetch(PDO::FETCH_ASSOC)) {
          $a++;
          $response[] = $row_livefeed;
      }

      $response = array('livefeed'=>$response);

      if($a != 0){ 
          $response["error"] = false;
          $response["message"] = "Dump data success";



          $data_string = json_encode($response);

          echo $data_string;

          $tmp_currenttimestamp = date("Y-m-d H:i:s");

          $ch = curl_init();

          curl_setopt($ch, CURLOPT_URL,"http://".$collector_server_ip."/receivebackup" );
          // curl_setopt($ch, CURLOPT_URL,"http://10.20.126.4/receivebackup" );
          curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
          curl_setopt($ch, CURLOPT_POST,1 );
          curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string); 
          curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: text/text')); 
                                                                                                     
          $result = curl_exec($ch);

          $result = json_decode($result,true);

          // var_dump($result);
          // echo $result['processbackup']['error'];
          // echo $result['processbackup']['message'];
          // TODO
          if ($result['processbackup']['error'] == false){
            echo $result['processbackup']['message'];
            // TODO update last backup datetime in config/location table
            $sb = $db->prepare("UPDATE location SET last_backup = ? WHERE id_location = ?");

            $sb->execute([$tmp_currenttimestamp,$id_location]);
          }
          else{
            echo $result['processbackup']['message'];
          }
          curl_close($ch);

      }
      else{
          $response["error"] = true;
          $response["message"] = "No data";
          $data_string = json_encode($response);
          echo $data_string;
      }

      

    } else {
        $stmt->close();
        echo "no data location";
        // $currenttimestamp = "2000-06-28 12:50:00";
        $currenttimestamp = date("Y-m-d H:i:s");
    }



  }
  catch(PDOException $e)
  {
    print 'Exception : '.$e->getMessage();
  }
?>