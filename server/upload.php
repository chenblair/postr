<?php

require_once('/var/www/html/mailer.php');

$entityBody = file_get_contents('php://input');
$entityBody = str_replace(["-", "_"], ["+", "/"], $entityBody);
$entityBody = base64_decode($entityBody);
$inputDirectory = "/var/www/html/input/" . uniqid() . ".jpg";
$fileHandle = fopen($inputDirectory, 'w');
fwrite($fileHandle, $entityBody);
fclose($fileHandle);

$uniqueFileName = uniqid();
$outputDir = "/var/www/html/scanned/" . $uniqueFileName . '/';
$serverURL = $serverURL . $uniqueFileName . "/";
if(!file_exists($outputDir)) {
   mkdir($outputDir);
}

$command = "/root/anaconda2/bin/python find_posters.py -i " . $inputDirectory . " -o " . $outputDir . "";
exec($command, $output);

$outputBody = [];
$filesInOutput = scandir($outputDir);
for ($i = 0; $i < count($filesInOutput); $i++) {
    if (strcmp($filesInOutput[$i], ".") == 0) {
        unset($filesInOutput[$i]);
        $i--;
    }

    if (strcmp($filesInOutput[$i], "..") == 0) {
        unset($filesInOutput[$i]);
        $i--;
    }
}

$fp = fopen("/var/www/html/data.txt", "w");
$chunks = array_chunk($filesInOutput, 4);
for ($i = 0; $i < count($chunks); $i++) {
    $url = 'https://vision.googleapis.com/v1/images:annotate?key=AIzaSyAeZG6ruH8QJtlcPfzqhI_ZkTKhbd3jDiA';
    $ch = curl_init($url);

    $requests = [];
    for ($j = 0; $j < count($chunks[$i]); $j++) {
        $filePath = $outputDir . $chunks[$i][$j];
        $contents = file_get_contents($filePath);
        $base64Content = base64_encode($contents);

        $request = ["image" => ["content" => $base64Content], "features" => [["type" => "TEXT_DETECTION"]]];
        $requests[] = $request;
    }

    $encode = json_encode(["requests" => $requests]);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $encode);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $response = curl_exec($ch);
    fwrite($fp, $response);
    curl_close($ch);

    $jsonDecoded = json_decode($response);
    $responses = $jsonDecoded->responses;
    for ($i = 0; $i < count($responses); $i++) {
        $obj = $responses[$i];
        if (is_null($obj) || !isset($obj->textAnnotations)) {
            continue;
        }

        $desc = $obj->textAnnotations[0]->description;
        $desc = str_replace("\n", " ", $desc);
        $cmd = "/root/anaconda2/bin/python /var/www/html/parser.py \"" . $desc . "\"";
        exec($cmd, $output);
        if (count($output) > 0) {
           $outputBody[] = ["time" => $output[0], "desc" => $desc];
        }
    }
}

fclose($fp);

//unlink($inputDirectory);
//rmdir($outputDir);
//$jsonOutput = json_encode($outputBody);
//echo $jsonOutput;

$email = new PHPMailer();
$bodytext = "You have received a new event!";
$email->From = 'wallcal@wallcal.com';
$email->FromName = 'Notification@WallCal';
$email->Subject = 'Event Received';
$email->Body = $bodytext;
$email->AddAddress( 'unbrace3@gmail.com' );
//header('Content-type: text/calendar; charset=utf-8');
//header('Content-Disposition: attachment; filename=invite.ics');

$icsDir = "/var/www/html/ics/" . uniqid() . "/";
if (!file_exists($icsDir)) {
   mkdir($icsDir);
}

var_dump($outputBody);
for ($i = 0; $i < count($outputBody); $i++) {
    $body = $outputBody[$i];
    $components = explode(" ", $body["time"]);
    $start = $body["time"];
    $end = $body["time"];
    trim($start);
    trim($end);

    if (count($components) > 1) {
       if (strpos($components[1], "-") !== false) {
          $section = explode("-", $components[1]);
          $start = $components[0] . " " . $section[0];
          $end = $components[0] . " " . $section[1];
          trim($start);
          trim($end);
       }
    }

    //if (strpos(strtolower($body["desc"]), "pm ") !== false) {
    //    $start = $start . " pm";
    //    $end = $end . " pm";
    //}

    $t = date_parse($start);
    $st = date('H:i', strtotime($t['hour'] . ':' . $t['minute']));

    $p = date_parse($end);
    $et = date('H:i', strtotime($p['hour'] . ':' . $t['minute']));

    $ics = new ICS(array('dtstart' => $st, 'dtend' => $et, 'description' => $body["desc"]));
    $sICS = $ics->to_string();
    $sICSPath = $icsDir . uniqid() . ".ics";
    $handle = fopen($sICSPath, "w");
    fwrite($handle, $sICS);
    fclose($handle);
    $email->addAttachment($sICSPath);
}

$email->Send();
