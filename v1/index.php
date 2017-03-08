<?php

header('Access-Control-Allow-Origin: *');
date_default_timezone_set("Asia/Kuala_Lumpur"); 



// require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';



define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$db = new PDO('sqlite:db.sqlite3');

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// User id from db - Global Variable
// $user_id = NULL;


/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}


        



// function getTitleFromUrl($url)
// {
//     preg_match('/<title>(.+)<\/title>/', file_get_contents($url), $matches);
//     return mb_convert_encoding($matches[1], 'UTF-8', 'UTF-8');
// }

function returnResult($action, $success = true, $id = 0)
{
    echo json_encode([
        'action' => $action,
        'success' => $success,
        'id' => intval($id),
    ]);
}

$app->get('/bookmark', function () use ($db, $app) {
    $sth = $db->query('SELECT * FROM bookmark;');
    echo json_encode($sth->fetchAll(PDO::FETCH_CLASS));
});

$app->get('/bookmark/:id', function ($id) use ($db, $app) {
    $sth = $db->prepare('SELECT * FROM bookmark WHERE id = ? LIMIT 1;');
    $sth->execute([intval($id)]);
    echo json_encode($sth->fetchAll(PDO::FETCH_CLASS)[0]);
});

$app->post('/bookmark', function () use ($db, $app) {
    $title = $app->request()->post('title');
    $sth = $db->prepare('INSERT INTO bookmark (url, title) VALUES (?, ?);');
    $sth->execute([
        $url = $app->request()->post('url'),
        empty($title) ? getTitleFromUrl($url) : $title,
    ]);

    returnResult('add', $sth->rowCount() == 1, $db->lastInsertId());
});

$app->put('/bookmark/:id', function ($id) use ($db, $app) {
    $sth = $db->prepare('UPDATE bookmark SET title = ?, url = ? WHERE id = ?;');
    $sth->execute([
        $app->request()->post('title'),
        $app->request()->post('url'),
        intval($id),
    ]);

    returnResult('edit', $sth->rowCount() == 1, $id);
});

$app->delete('/bookmark/:id', function ($id) use ($db) {
    $sth = $db->prepare('DELETE FROM bookmark WHERE id = ?;');
    $sth->execute([intval($id)]);

    returnResult('delete', $sth->rowCount() == 1, $id);
});


$app->post('/install', function () use ($db, $app) {
    
    // $py_code_path = "C:\wamp64\www\counterAPI_slim_sqlite\\v1\pc_final"; // \\v for escape \v for php in windows.
    $py_code_path = "/home/pi/pc_final";
    // $py_code_path = "/home/pi/python_learn/pc_final";
    $strConfig = "";
    $strStart = "";
    $strStop = "";


    $db->exec('DROP TABLE IF EXISTS livefeed;');
    $db->exec('CREATE TABLE livefeed (id_livefeed INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, currenttimestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, id_location INTEGER NOT NULL, event INTEGER NOT NULL);');

    $db->exec('DROP TABLE IF EXISTS location;');
    $db->exec('CREATE TABLE IF NOT EXISTS location (id_location INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, loc_name TEXT NULL, loc_description TEXT NULL, district TEXT NULL, state TEXT NULL, lat TEXT, lng TEXT, collector_server_ip TEXT, local_ip TEXT, last_update DATETIME NOT NULL, camera_distance TEXT NOT NULL DEFAULT "150", contours_width TEXT NOT NULL DEFAULT "26", contours_height TEXT NOT NULL DEFAULT "22", line1_point1_x TEXT NOT NULL DEFAULT "0", line1_point1_y TEXT NOT NULL DEFAULT "130", line1_point2_x TEXT NOT NULL DEFAULT "600", line1_point2_y TEXT NOT NULL DEFAULT "130", line2_point1_x TEXT NOT NULL DEFAULT "0", line2_point1_y TEXT NOT NULL DEFAULT "230", line2_point2_x TEXT NOT NULL DEFAULT "600", line2_point2_y TEXT NOT NULL DEFAULT "230", status INTEGER DEFAULT "0", last_backup DATETIME);');

    $db->exec('DROP TABLE IF EXISTS users;');
    $db->exec('CREATE TABLE users (id_user INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, uname TEXT NOT NULL, pword_hash TEXT NOT NULL, api_key TEXT NOT NULL, level INTEGER NOT NULL, last_login DATETIME DEFAULT CURRENT_TIMESTAMP);');


    $db->exec('
        INSERT INTO users (id_user, uname, pword_hash, api_key, level, last_login) VALUES (1,  "watch",    "$2a$10$781e5b813c947a76eebb9uJPFsvQEZMoQ3zmwOMYDRkwvFzIYsCZW", "dc1757e1c81475c26b3198d144d41ff0", 1,  "2016-06-14 11:34:24");
        INSERT INTO users (id_user, uname, pword_hash, api_key, level, last_login) VALUES (2, "view", "$2a$10$66481480e189b3733ae9aOLOLHaGflX/CAp0LgyI4GJX5M76ulzmO", "9be3ab599321fba51830d287f9bead18", 0,    "2016-06-13 08:39:49");');

            // check for required params
            verifyRequiredParams(array('id_location'));
            verifyRequiredParams(array('loc_name'));
            verifyRequiredParams(array('loc_description'));


            $response = array();
            
            $id_location = $app->request->post('id_location');
            $loc_name = $app->request->post('loc_name');
            $loc_description = $app->request->post('loc_description');



            $last_update = date("Y-m-d H:i:s");
            $stmt2 = $db->prepare("INSERT INTO location(id_location, loc_name, loc_description, last_update) VALUES(?,?,?,?)");
            $result2 = $stmt2->execute([
                            $id_location,
                            $loc_name,
                            $loc_description,
                            $last_update
                            ]);

            // $strConfig .= "def setup_param():\n";

            $strConfig .= "collector_server = \"http://goswatch.myvnc.com\"\n";
            // $strConfig .= "local_server = \"http://10.20.125.30\"\n";
            // $strConfig .= "local_server = \"http://10.20.125.26\"\n";
            $strConfig .= "local_server = \"http://localhost\"\n";
            // $strConfig .= "localAPI = \"/counterAPI_slim_sqlite/v1/livefeed\" # insert in sqlite3 in local raspberry pi\n";
            $strConfig .= "localAPI = \"/livefeed\" # insert in sqlite3 in local raspberry pi\n";
            $strConfig .= "id_location = $id_location\n";
            $strConfig .= "countour_width = 26\n";
            $strConfig .= "countour_height = 22\n";
            $strConfig .= "line1_point1_x = 0\n";
            $strConfig .= "line1_point1_y = 130\n";
            $strConfig .= "line1_point2_x = 600\n";
            $strConfig .= "line1_point2_y = 130\n";
            $strConfig .= "line2_point1_x = 0\n";
            $strConfig .= "line2_point1_y = 230\n";
            $strConfig .= "line2_point2_x = 600\n";
            $strConfig .= "line2_point2_y = 230\n";


            // $strStart .= "source ~/.profile\n";
            // $strStart .= "workon cv\n";
            $strStart .= "python $py_code_path/pc.py\n";



            // $fp = fopen($py_code_path.'\config.py', 'w'); // for windows
            $fp = fopen($py_code_path.'/config.py', 'w'); // for raspbian
            $fp_start = fopen($py_code_path.'/start.sh', 'w'); // for raspbian

            fwrite($fp, $strConfig);
            fwrite($fp_start, $strStart);

            fclose($fp);
            fclose($fp_start);

            if ($result2) {
                $response["error"] = false;
                $response["message"] = "insert new location successfully";
                $output = array('install'=>$response);
                echoRespnse(201, $output);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to insert new location.";
                $output = array('install'=>$response);
                echoRespnse(200, $output);
            }

});



$app->post('/start', function () use ($db, $app) {
            // $py_code_path = "C:\wamp64\www\counterAPI_slim_sqlite\\v1\pc_final"; // \\v for escape \v for php in windows.
            $py_code_path = "/home/pi/pc_final";
            // $py_code_path = "/home/pi/python_learn/pc_final";
            
            // system("whoami");
            // shell_exec($py_code_path."/start.sh >/dev/null 2>&1 &"); # return  all output as string
            exec($py_code_path."/start.sh >/dev/null 2>&1 &", $output, $return); # return last line output as string by default
            // print_r($output);
            if (!$return) {
                $response["error"] = false;
                $response["message"] = "service started...";
                $output = array('start'=>$response);
                echoRespnse(200, $output);
            }
            else{
                $response["error"] = true;
                $response["message"] = "Cannot start service...";
                $output = array('start'=>$response);
                echoRespnse(200, $output);
            }


});


$app->post('/register', function() use ($db, $app) {
            // check for required params
            verifyRequiredParams(array('uname', 'pword', 'level'));

            $response = array();

            // reading post params
            $uname = $app->request->post('uname');
            $pword = $app->request->post('pword');
            $level = $app->request->post('level');

            //$res = $db->createUser($name, $email, $password);



        // First check if user already existed in db


        $stmt = $db->prepare("SELECT id_user from users WHERE uname = :uname");
        $stmt->bindParam(":uname", $uname);
        $stmt->execute();

        $a = 0;

        while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
            $a++;
        }
        //echo $a;

        if (!$a > 0) {
            // Generating password hash
            $pword_hash = PassHash::hash($pword);

            // Generating API key
            $api_key = generateApiKey();

            // insert query
            $stmt = $db->prepare("INSERT INTO users(uname, pword_hash, api_key, level, last_login) VALUES(:uname, :pword_hash, :api_key, :level, NULL)");
            $stmt->bindParam(":uname", $uname);
            $stmt->bindParam(":pword_hash", $pword_hash);
            $stmt->bindParam(":api_key", $api_key);
            $stmt->bindParam(":level", $level);
            $stmt->execute();
            $rowCount = $stmt->rowCount();


            // Check for successful insertion
            if ($rowCount > 0) {
                // User successfully inserted
                //echo "Success";
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                //return USER_CREATED_SUCCESSFULLY;
            } else {
                //echo "Failed 1";
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
                // Failed to create user
                //return USER_CREATE_FAILED;
            }
        } else {
            //echo "Failed 2";
            $response["error"] = true;
            $response["message"] = "Sorry, this user already existed";
            // User with same email already existed in the db
            //return USER_ALREADY_EXISTED;
        }


        // echo json response
        $output = array('user'=>$response);
        echoRespnse(201, $output);
        });


$app->post('/login', function() use ($db, $app) {
            // check for required params
            verifyRequiredParams(array('uname'));
            verifyRequiredParams(array('pword'));

            $response = array();
            $responseError = array();

            $uname = $app->request->post('uname');
            $pword = $app->request->post('pword');

            $stmt = $db->prepare("SELECT id_user, uname, pword_hash, api_key, level, last_login FROM users WHERE uname = :uname LIMIT 1");
            $stmt->bindParam(":uname", $uname);
            
            $stmt->execute();

            /* Bind by column name */
            $stmt->bindColumn('id_user', $id_user);
            $stmt->bindColumn('uname', $uname);
            $stmt->bindColumn('pword_hash', $pword_hash);
            $stmt->bindColumn('api_key', $api_key);
            $stmt->bindColumn('level', $level);
            $stmt->bindColumn('last_login', $last_login);

            $i = 0;

            while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
                $i++;

                $response["id_user"] = $id_user;
                $response["uname"] = $uname;
                $response["pword_hash"] = $pword_hash;
                $response["api_key"] = $api_key;
                $response["level"] = $level;
                $response["last_login"] = $last_login;
            }
            //echo $i;
            //echo "hash from db : ".$response["pword_hash"]."\n";
            //echo "Test " . PassHash::check_password($response["pword_hash"], $pword);

            if($i != 0 AND (PassHash::check_password($response["pword_hash"], $pword))){
                $response["error"] = false; 
                $output = array('user'=>$response);

                //$last_login = new DateTime('Y-m-d H:i:s');
                $last_login = date("Y-m-d H:i:s");
                //echo $last_login;
                $stmt2 = $db->prepare("UPDATE users SET last_login = ? WHERE id_user = ?;");
                $result2 = $stmt2->execute([
                                $last_login,
                                $id_user
                                ]);




                echoRespnse(200, $output);
                //print_r($output);
            }
            else{
                $responseError["error"] = true;
                $responseError["message"] = "login failed";
                $output = array('user'=>$responseError);
                echoRespnse(200, $output); 
            }


        });





$app->post('/passwd', function() use ($db, $app) {
            // check for required params
            verifyRequiredParams(array('id_user'));
            verifyRequiredParams(array('cpword'));
            verifyRequiredParams(array('pword1'));
            verifyRequiredParams(array('pword2'));

            $response = array();

            $id_user = $app->request->post('id_user');
            $cpword = $app->request->post('cpword');
            $pword1 = $app->request->post('pword1');
            $pword2 = $app->request->post('pword2');


            $stmt = $db->prepare("SELECT id_user, pword_hash FROM users WHERE id_user = :id_user");
            $stmt->bindParam(":id_user", $id_user);
        

            $stmt->bindColumn('pword_hash', $pword_hash);


            $result = $stmt->execute();

            $i = 0;

            while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
                //check if old pword_hash valid with the new password entered
                if(PassHash::check_password($pword_hash, $cpword)){
                    $i++;
                }
            }
            //echo $i;
            if($i != 0){
                if($pword1 != '' OR $pword1 != ' '){
                    if($pword1 == $pword2){


                        // Generating password hash
                        $pword_hash = PassHash::hash($pword1);

                        $stmt2 = $db->prepare("UPDATE users SET pword_hash = ? WHERE id_user = ?;");
                        $result2 = $stmt2->execute([
                                        $pword_hash,
                                        $id_user
                                        ]);

                        if ($result2) {
                            $response["error"] = false;
                            $response["message"] = "Password updated successfully";
                            $output = array('passwd'=>$response);
                            echoRespnse(201, $output);
                        } else {
                            $response["error"] = true;
                            $response["message"] = "Failed to update password";
                            $output = array('passwd'=>$response);
                            echoRespnse(200, $output);
                        }
                    }
                    else{
                        $response["error"] = true;
                        $response["message"] = "New password not match";
                        $output = array('passwd'=>$response);
                        echoRespnse(200, $output);
                    }
                }
                else{
                    $response["error"] = true;
                    $response["message"] = "New password cannot be blank";
                    $output = array('passwd'=>$response);
                    echoRespnse(200, $output);
                }
            }
            else{
                $response["error"] = true;
                $response["message"] = "You are not authorize person";
                $output = array('passwd'=>$response);
                echoRespnse(200, $output); 
            }


        });





$app->post('/processbackup', function() use ($db, $app) {
// echo "test";
            $stmt = $db->prepare("SELECT last_backup FROM location LIMIT 1;");

            $stmt->execute();

            $response = array();
            $currenttimestamp = ""; 
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $last_backup = $row['last_backup'];
                if($last_backup == null || $last_backup == '' || empty($last_backup) || $last_backup == "null"){
                    $currenttimestamp = "2000-06-28 12:50:00";
                }
                else{
                    $currenttimestamp = $last_backup;
                }
            } else {
                $stmt->close();
                echo "no data";
                $currenttimestamp = "2000-06-28 12:50:00";
            }

            // echo "timestamp : ".$currenttimestamp;

             

            $sth = $db->prepare('SELECT * FROM livefeed WHERE currenttimestamp >= :currenttimestamp');

            $sth->bindParam(":currenttimestamp", $currenttimestamp);

            $sth->execute();
            $response = array();
            $tmp = array();

            $a = 0;

            while ($row_livefeed = $sth->fetch(PDO::FETCH_ASSOC)) {
                $a++;

                // $tmp["id_livefeed"] = $row_livefeed['id_livefeed'];
                // $tmp["currenttimestamp"] = $row_livefeed['currenttimestamp'];
                // $tmp["id_location"] = $row_livefeed['id_location'];
                // $tmp["event"] = $row_livefeed['event'];
                $response[] = $row_livefeed;

            }


// $data = array("name" => "Hagrid", "age" => "36");                                                                    
// $data_string = json_encode($data);
// $response[] = $data_string;


            // echo " a : ".$a;
            // print_r($row_livefeed);
            $response = array('livefeed'=>$response);
            //echo $a;
            if($a != 0){ 
                $response["error"] = false;
                $response["message"] = "Dump data success";
            }
            else{
                $response["error"] = true;
                $response["message"] = "No data";
            }

            //echoRespnse(200, $sth->fetchAll(PDO::FETCH_CLASS)[0]);
            //echoRespnse(200, $sth->fetchAll(PDO::FETCH_BOUND));
            //echo json_encode($sth->fetchAll(PDO::FETCH_CLASS)[0]);
            echoRespnse(200, $response);
                


        });






$app->post('/updatestatus', function() use ($db, $app) {
            // check for required params
            verifyRequiredParams(array('status'));

            $response = array();

            $status = $app->request->post('status');

            //echo $last_login;
            $stmt = $db->prepare("UPDATE config SET status = ?");
            $stmt->execute([
                            $status
                            ]);

            if($stmt->rowCount() != 0){
                $response["error"] = false;
                $response["message"] = "Status updated successfully";
                $output = array('updatestatus'=>$response);
                echoRespnse(200, $output);     
            }
            else{
                $response["error"] = true;
                $response["message"] = "Cannot update status";
                $output = array('updatestatus'=>$response);
                echoRespnse(200, $output);
            }
        });












// /**
//  * Creating new task in db
//  * method POST
//  * params - name
//  * url - /tasks/
//  */
$app->post('/livefeed', function() use ($db, $app) {
            // check for required params
            verifyRequiredParams(array('currenttimestamp'));
            verifyRequiredParams(array('id_location'));
            verifyRequiredParams(array('event'));

            $response = array();
            $currenttimestamp = $app->request->post('currenttimestamp');
            $id_location = $app->request->post('id_location');
            $event = $app->request->post('event');

            $stmt = $db->prepare("INSERT INTO livefeed(currenttimestamp, id_location, event) VALUES(?,?,?)");
            $result = $stmt->execute([
                            $currenttimestamp,
                            $id_location,
                            $event,
                            ]);

            if ($result) {
                $response["error"] = false;
                $response["message"] = "livefeed created successfully";
                $output = array('livefeed'=>$response);
                echoRespnse(201, $output);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create livefeed. Do something for local storage";
                $output = array('livefeed'=>$response);
                echoRespnse(200, $output);
            }
        });





$app->post('/processconfig', function() use ($db, $app) {
            $strConfig = "";
            // $py_code_path = "C:\wamp64\www\counterAPI_slim_sqlite\\v1\pc_final"; // \\v for escape \v for php in windows.
            $py_code_path = "/home/pi/pc_final";
            $strConfig = "";
            $strStart = "";
            $strStop = "";
            $strCollectorServerIP = "";

            // check for required params
            verifyRequiredParams(array('id_location'));
            verifyRequiredParams(array('loc_name'));
            verifyRequiredParams(array('loc_description'));
            // verifyRequiredParams(array('district'));
            // verifyRequiredParams(array('state'));
            // verifyRequiredParams(array('lat'));
            // verifyRequiredParams(array('lng'));
            verifyRequiredParams(array('collector_server_ip'));
            // verifyRequiredParams(array('local_ip'));
            // verifyRequiredParams(array('last_update'));

            verifyRequiredParams(array('camera_distance'));
            verifyRequiredParams(array('contours_width'));
            verifyRequiredParams(array('contours_height'));
            verifyRequiredParams(array('line1_point1_x'));
            verifyRequiredParams(array('line1_point1_y'));
            verifyRequiredParams(array('line1_point2_x'));
            verifyRequiredParams(array('line1_point2_y'));
            verifyRequiredParams(array('line2_point1_x'));
            verifyRequiredParams(array('line2_point1_y'));
            verifyRequiredParams(array('line2_point2_x'));
            verifyRequiredParams(array('line2_point2_y'));
            // verifyRequiredParams(array('status'));

            $response = array();
            
            $id_location = $app->request->post('id_location');
            $loc_name = $app->request->post('loc_name');
            $loc_description = $app->request->post('loc_description');
            $district = $app->request->post('district');
            $state = $app->request->post('state');
            $lat = $app->request->post('lat');
            $lng = $app->request->post('lng');
            // $collector_server_ip = $_SERVER['REMOTE_ADDR'];
            $collector_server_ip = $app->request->post('collector_server_ip');
            // $local_ip = getHostByName(getHostName()); // if process is update, local_ip is on text value
            $last_update = date("Y-m-d H:i:s");

            $camera_distance = $app->request->post('camera_distance');
            $contours_width = $app->request->post('contours_width');
            $contours_height = $app->request->post('contours_height');
            $line1_point1_x = $app->request->post('line1_point1_x');
            $line1_point1_y = $app->request->post('line1_point1_y');
            $line1_point2_x = $app->request->post('line1_point2_x');
            $line1_point2_y = $app->request->post('line1_point2_y');
            $line2_point1_x = $app->request->post('line2_point1_x');
            $line2_point1_y = $app->request->post('line2_point1_y');
            $line2_point2_x = $app->request->post('line2_point2_x');
            $line2_point2_y = $app->request->post('line2_point2_y');
            $status = $app->request->post('status');

            $stmt = $db->prepare("SELECT id_location FROM location WHERE id_location = :id_location");
            $stmt->bindParam(":id_location", $id_location);
            
            $result = $stmt->execute();
            

            /* Bind by column name */
            $stmt->bindColumn('id_location', $id_location);

            $a = 0;

            while ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
                $a++;
            }
            //echo $a;
            if($a != 0){ // id_location dah wujud

                $local_ip = $app->request->post('local_ip');
                $stmt2 = $db->prepare("UPDATE location SET loc_name = ?, loc_description = ?, district = ?, state = ?, lat = ?, lng = ?, collector_server_ip = ?, local_ip = ?, last_update = ?, camera_distance = ?,  contours_width = ?,  contours_height = ?,  line1_point1_x = ?,  line1_point1_y = ?,  line1_point2_x = ?,  line1_point2_y = ?,  line2_point1_x = ?,  line2_point1_y = ?,  line2_point2_x = ?,  line2_point2_y = ?,  status = ?  WHERE id_location = ?;");
                $result2 = $stmt2->execute([
                                $loc_name,
                                $loc_description,
                                $district,
                                $state,
                                $lat,
                                $lng,
                                $collector_server_ip,
                                $local_ip,
                                $last_update,

                                $camera_distance,
                                $contours_width,
                                $contours_height,
                                $line1_point1_x,
                                $line1_point1_y,
                                $line1_point2_x,
                                $line1_point2_y,
                                $line2_point1_x,
                                $line2_point1_y,
                                $line2_point2_x,
                                $line2_point2_y,
                                $status,
                                
                                $id_location
                                ]);

                if ($result2) {



                    // $strConfig .= "collector_server = \"http://10.20.125.30\"\n";
                    $strConfig .= "collector_server = \"http://$collector_server_ip\"\n";
                    $strConfig .= "local_server = \"http://$local_ip\"\n";
                    // $strConfig .= "local_server = \"http://10.20.125.26\"\n";
                    // $strConfig .= "localAPI = \"/counterAPI_slim_sqlite/v1/livefeed\" # insert in sqlite3 in local raspberry pi\n";
                    $strConfig .= "localAPI = \"/livefeed\" # insert in sqlite3 in local raspberry pi\n";
                    $strConfig .= "id_location = $id_location\n";
                    $strConfig .= "countour_width = $contours_width\n";
                    $strConfig .= "countour_height = $contours_height\n";
                    $strConfig .= "line1_point1_x = $line1_point1_x\n";
                    $strConfig .= "line1_point1_y = $line1_point1_y\n";
                    $strConfig .= "line1_point2_x = $line1_point2_x\n";
                    $strConfig .= "line1_point2_y = $line1_point2_y\n";
                    $strConfig .= "line2_point1_x = $line2_point1_x\n";
                    $strConfig .= "line2_point1_y = $line2_point1_y\n";
                    $strConfig .= "line2_point2_x = $line2_point2_x\n";
                    $strConfig .= "line2_point2_y = $line2_point2_y\n";

                    // $strStart .= "source ~/.profile\n";
                    // $strStart .= "workon cv\n";
                    $strStart .= "python $py_code_path/pc.py\n";

                    $strCollectorServerIP .= "<?php ";
                    $strCollectorServerIP .= "\$collector_server_ip = \"$collector_server_ip\";";
                    $strCollectorServerIP .= "?>";



                    // $fp = fopen($py_code_path.'\config.py', 'w'); // for windows
                    $fp = fopen($py_code_path.'/config.py', 'w'); // for raspbian
                    $fp_start = fopen($py_code_path.'/start.sh', 'w'); // for raspbian
                    $fp_collector_server = fopen($py_code_path.'/collector_server_ip.php', 'w'); // for raspbian

                    fwrite($fp, $strConfig);
                    fwrite($fp_start, $strStart);
                    fwrite($fp_collector_server, $strCollectorServerIP);

                    fclose($fp);
                    fclose($fp_start);
                    fclose($fp_collector_server);


                    $response["error"] = false;
                    $response["message"] = "config update successfully";
                    $output = array('config'=>$response);
                    echoRespnse(200, $output);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to update config";
                    $output = array('config'=>$response);
                    echoRespnse(200, $output);
                }
            }
            else{
                

            $local_ip = getHostByName(getHostName());


                $stmt2 = $db->prepare("INSERT INTO location(id_location, loc_name, loc_description, district, state, lat, lng, collector_server_ip, local_ip, last_update) VALUES(?, ?,?,?,?,?,?,?,?,?)");
                $result2 = $stmt2->execute([
                                $id_location,
                                $loc_name,
                                $loc_description,
                                $district,
                                $state,
                                $lat,
                                $lng,
                                $collector_server_ip,
                                $local_ip,
                                $last_update
                                ]);

                if ($result2) {
                    $response["error"] = false;
                    $response["message"] = "insert new location successfully";
                    $output = array('config'=>$response);
                    echoRespnse(201, $output);
                } else {
                    $response["error"] = true;
                    $response["message"] = "Failed to insert new location.";
                    $output = array('config'=>$response);
                    echoRespnse(200, $output);
                }

            }


            
        });


$app->get('/getconfig/:id', function ($id) use ($db, $app) {
    $sth = $db->prepare('SELECT * FROM location WHERE id_location = ? LIMIT 1;');
    $sth->execute([intval($id)]);
    $response = array();

    $a = 0;

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $a++;

        $response["loc_name"] = $row['loc_name'];
        $response["loc_description"] = $row['loc_description'];
        $response["district"] = $row['district'];
        $response["state"] = $row['state'];
        $response["lat"] = $row['lat'];
        $response["lng"] = $row['lng'];
        $response["collector_server_ip"] = $row['collector_server_ip'];
        $response["local_ip"] = $row['local_ip'];
        $response["last_update"] = $row['last_update'];
        $response["camera_distance"] = $row['camera_distance'];
        $response["contours_width"] = $row['contours_width'];
        $response["contours_height"] = $row['contours_height'];
        $response["line1_point1_x"] = $row['line1_point1_x'];
        $response["line1_point1_y"] = $row['line1_point1_y'];
        $response["line1_point2_x"] = $row['line1_point2_x'];
        $response["line1_point2_y"] = $row['line1_point2_y'];
        $response["line2_point1_x"] = $row['line2_point1_x'];
        $response["line2_point1_y"] = $row['line2_point1_y'];
        $response["line2_point2_x"] = $row['line2_point2_x'];
        $response["line2_point2_y"] = $row['line2_point2_y'];
        $response["status"] = $row['status'];
    }
    // echo $a;
    // print_r($row);

    //echo $a;
    if($a != 0){ 
        $response["error"] = false;
    }
    else{
        $response["error"] = true;
        $response["message"] = "No data";
    }

    //echoRespnse(200, $sth->fetchAll(PDO::FETCH_CLASS)[0]);
    //echoRespnse(200, $sth->fetchAll(PDO::FETCH_BOUND));
    //echo json_encode($sth->fetchAll(PDO::FETCH_CLASS)[0]);
    echoRespnse(200, $response);
});


$app->post('/locationinfo', function () use ($db, $app) {
    header('Access-Control-Allow-Origin: *');
    $sth = $db->prepare('SELECT * FROM location LIMIT 1;');
    // $sth = $db->prepare('SELECT id_location FROM location LIMIT 1;');
    $sth->execute();
    echoRespnse(200, $sth->fetchAll(PDO::FETCH_CLASS)[0]);
    //echoRespnse(200, $sth->fetchAll(PDO::FETCH_BOUND));
    //echo json_encode($sth->fetchAll(PDO::FETCH_CLASS)[0]);
});



/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->get('/in/:id', function($id_location) use ($db, $app) {

            // ob_start();
            header('Access-Control-Allow-Origin: *');

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');

            
            $response = array();

            $stmt = $db->prepare("SELECT SUM(event) AS data FROM livefeed WHERE event > 0 AND id_location = ? AND strftime('%Y-%m-%d',currenttimestamp)=date('now')");
       
            $result = $stmt->execute([intval($id_location)]);

            if ($result != NULL) {
                // echo "data: " . $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0]."\n\n";;
                
                // echo PHP_EOL;
                // echo "data: " . "100"."\n\n";
                // $response["error"] = false;
                
                $response["in"] = $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0];
                // $output = array('count'=>$response);
                echoRespnse(200, $response);
            } else {
                // echo "data: 0\n\n";
                // $response["error"] = true;
                // $response["message"] = "The requested resource doesn't exists";
                
                echoRespnse(200, "{\"in\":0}");
            }

            // echo "data: ".$response['in']."\n\n";
            // echo "data: ".$row["countin"];
            //echo "data: " . $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0];

            ob_flush();
            flush();
        });



/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->get('/out/:id', function($id_location) use ($db, $app) {

            // ob_start();
            header('Access-Control-Allow-Origin: *');

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            
            $response = array();

            $stmt = $db->prepare("SELECT SUM(event) AS data FROM livefeed WHERE event < 0 AND id_location = ? AND strftime('%Y-%m-%d',currenttimestamp)=date('now')");

            $result = $stmt->execute([intval($id_location)]);
            
            if ($result != NULL) {
                // echo "data: " . abs($stmt->fetchAll(PDO::FETCH_COLUMN,0)[0]). PHP_EOL;
                // echo PHP_EOL;
                // echo "data: " . "100"."\n\n";
                // $response["error"] = false;
                $response["out"] = abs($stmt->fetchAll(PDO::FETCH_COLUMN,0)[0]);
                // $output = array('count'=>$response);
                echoRespnse(200, $response);
            } else {
                // $response["error"] = true;
                // $response["message"] = "The requested resource doesn't exists";
                echoRespnse(200, "{\"out\":0}");
            }

            // echo "data: ".$response['in']."\n\n";
            // echo "data: ".$row["countin"];
            //echo "data: " . $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0];

            flush();
            ob_flush();
        });






$app->get('/total/:id', function($id_location) use ($db, $app) {

            // ob_start();
            header('Access-Control-Allow-Origin: *');

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            // check for required params
            //verifyRequiredParams(array('id_location'));

            $response = array();

            $stmt = $db->prepare("SELECT SUM(event) AS countin FROM livefeed WHERE id_location = ? AND strftime('%Y-%m-%d',currenttimestamp)=date('now')");

            $result = $stmt->execute([intval($id_location)]);
            
            if ($result != NULL) {
                // echo "data: " . $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0]. PHP_EOL;
                // echo PHP_EOL;
                //echo "data: " . "100"."\n\n";
                // $response["error"] = false;
                $response["total"] = $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0];
                // $output = array('count'=>$response);
                echoRespnse(200, $response);
            } else {
                // $response["error"] = true;
                // $response["message"] = "The requested resource doesn't exists";
                echoRespnse(200, "{\"out\":0}");
            }

            // echo "data: ".$response['in']."\n\n";
            // echo "data: ".$row["countin"];
            //echo "data: " . $stmt->fetchAll(PDO::FETCH_COLUMN,0)[0];
            flush();
            ob_flush();
        });


function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";

    $request_params = array();

    $request_params = $_REQUEST;


    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {

        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);


    }

    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}


function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}


function checkLogin($uname, $pword) {
    // fetching user by email
    $stmt = $db->prepare("SELECT id_user FROM users WHERE uname = ? AND pword = ?");

    // $stmt->bind_param("ss", $uname, $psword);

    // $stmt->execute();
    $result = $stmt->execute([$uname,$pword]);
    
    $stmt->bind_result($id_user);

    $stmt->store_result();

    //echo $stmt->num_rows;

    if ($stmt->num_rows > 0) {
        // Found user with the email
        // Now verify the password

        //$stmt->fetch();

        //$stmt->close();

        //if (PassHash::check_password($password_hash, $password)) {
            // User password is correct
            return TRUE;
        //} else {
            // user password is incorrect
          //  return FALSE;
        //}
    } else {
        $stmt->close();

        // user not existed with the email
        return FALSE;
    }
}



function generateApiKey() {
    return md5(uniqid(rand(), true));
}


$app->run();
?>