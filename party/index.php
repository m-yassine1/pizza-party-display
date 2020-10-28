<?php
$setDate = date_create();

//Must read as 2013-03-15
if(isset($_GET["date"])) {
    $setDate = date_create_from_format("Y-m-d",$_GET["date"]);
}

$tomorrowDate = date('Y-m-d H:i:s.u',strtotime('+1 day',strtotime($setDate->format("Y-m-d H:i:s.u"))));
$tomorrowDate = date_create_from_format("Y-m-d H:i:s.u", $tomorrowDate);

$yesterdayDate = date('Y-m-d H:i:s.u',strtotime('-1 day',strtotime($setDate->format("Y-m-d H:i:s.u"))));
$yesterdayDate = date_create_from_format("Y-m-d H:i:s.u", $yesterdayDate);

//TODO: populate the data in the list based on delivery time     
$pizzaSheetLines = gzfile('https://altitude-ce-aws.s3.eu-central-1.amazonaws.com/Pizza_Sheet/AltitudeNorwayPizzaSheetAWS.csv.gz');
$partySheetLines = gzfile('https://altitude-ce-aws.s3.eu-central-1.amazonaws.com/Pizza_Sheet/AltitudeNorwayPartyInformationSheetAWS.csv.gz');


$partyData = array();

$firstLine = true;
foreach ($pizzaSheetLines as $line) {
    if($firstLine){
        $firstLine = false;
        continue;
    }
    if(empty(trim($line))) {
        continue;
    }
    list($partyId, $eventStatus, $parentName, $partyStartTime, $partyEndTime, $foodDeliveryTime, $pizzaType, $numberOfPizzas) = explode(",", $line);
    $partyStartTime = explode("+", $partyStartTime)[0];
    $foodDeliveryTime = explode("+", $foodDeliveryTime)[0];
    $partyEndTime = explode("+", $partyEndTime)[0];

    $partyStartTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($partyStartTime)));
    $partyEndTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($partyEndTime)));

    if(strtolower($foodDeliveryTime) == "null") {
        $foodDeliveryTime = date('Y-m-d H:i:s.u',strtotime('+30 minutes',strtotime($partyStartTime->format("Y-m-d H:i:s.u"))));
        $foodDeliveryTime = date_create_from_format("Y-m-d H:i:s.u", $foodDeliveryTime);
    } else {
        $foodDeliveryTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($foodDeliveryTime)));
    }

    if($partyStartTime->format("Y-m-d") == $setDate->format("Y-m-d") && $eventStatus == "Active") {
         if(!array_key_exists($partyId, $partyData)) {
            $partyData[$partyId] = array(
                "partyStartTime" => $partyStartTime->format("H:i"),
                "partyStartDate" => $partyStartTime->format("Y-m-d"),
                "partyEndTime" => $partyEndTime->format("H:i"),
                "parentName" => $parentName,
                "partyId" => $partyId,
                "PB Glutenfri Skinke" => 0,
                "PB Glutenfri Kjøttboller" => 0,
                "PB Glutenfri Pepperoni" => 0,
                "PB Glutenfri Margherita" => 0,
                "PB Pizza Kjøttboller" => 0,
                "PB Pizza Margherita" => 0,
                "PB Pizza Pepperoni" => 0,
                "PB Pizza Skinke" => 0,
                "Pølse" => 0,
                "Kylling og kalkun pølse" => 0,
                "guestOfHonor" => "",
                "birthdayPackage" => "",
                "jumpers" => 0,
                "partyRoom" => "",
                "kitchenNotes" => "",
                "eventNotes" => "",
                "privateNotes" => "",
                "partyHost" => "",
                "totalPizza" => 0,
                "totalGfPizza" => 0,
                "Bursdags Slush" => 0,
                "Bursdagsbrus" => 0,
                "Bursdagsis" => 0
            );
        }

        if (strpos($pizzaType, 'Glutenfri') !== false) {
            $partyData[$partyId]["totalGfPizza"] = $partyData[$partyId]["totalGfPizza"] + intval($numberOfPizzas);
        } else if(strpos($pizzaType, 'Pizza') !== false){
            $partyData[$partyId]["totalPizza"] = $partyData[$partyId]["totalPizza"] + intval($numberOfPizzas);
        }
        $partyData[$partyId][$pizzaType] = $partyData[$partyId][$pizzaType] + intval($numberOfPizzas);    
    }
}

$firstLine = true;
foreach ($partySheetLines as $line) {
    if($firstLine){
        $firstLine = false;
        continue;
    }
    if(empty(trim($line))) {
        continue;
    }
    list($partyId, $eventStatus, $parentName, $guestOfHonor, $partyStartTime, $partyEndTime, $partyHost, $birthdayPackage, $jumpers, $partyRoom, $kitchenNotes, $privateNotes) = explode(",", $line);
    $partyStartTime = explode("+", $partyStartTime)[0];
    $partyEndTime = explode("+", $partyEndTime)[0];
    $partyStartTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($partyStartTime)));
    $partyEndTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($partyEndTime)));
    if($partyStartTime->format("Y-m-d") == $setDate->format("Y-m-d") && $eventStatus == "Active") 
    {
         if(array_key_exists($partyId, $partyData)) 
         {
            $partyData[$partyId]["jumpers"] = $jumpers;
            $partyData[$partyId]["partyRoom"] = $partyRoom;
            $partyData[$partyId]["kitchenNotes"] = $kitchenNotes;
            $partyData[$partyId]["eventNotes"] = "";
            $partyData[$partyId]["privateNotes"] = $privateNotes;
            $partyData[$partyId]["birthdayPackage"] = $birthdayPackage;
            $partyData[$partyId]["partyHost"] = strtolower($partyHost) == "unassigned" ? "-" : $partyHost;
            if($partyData[$partyId]["guestOfHonor"] == "") { 
                $partyData[$partyId]["guestOfHonor"] = $guestOfHonor;
            } else {
                $partyData[$partyId]["guestOfHonor"] = $partyData[$partyId]["guestOfHonor"] . ", " . $guestOfHonor;
            }
        }
        else {
            $partyData[$partyId] = array(
                "partyStartTime" => $partyStartTime->format("H:i"),
                "partyStartDate" => $partyStartTime->format("Y-m-d"),
                "partyEndTime" => $partyEndTime->format("H:i"),
                "parentName" => $parentName,
                "partyId" => $partyId,
                "PB Glutenfri Skinke" => 0,
                "PB Glutenfri Kjøttboller" => 0,
                "PB Glutenfri Pepperoni" => 0,
                "PB Glutenfri Margherita" => 0,
                "PB Pizza Kjøttboller" => 0,
                "PB Pizza Margherita" => 0,
                "PB Pizza Pepperoni" => 0,
                "PB Pizza Skinke" => 0,
                "Pølse" => 0,
                "Kylling og kalkun pølse" => 0,
                "guestOfHonor" => $guestOfHonor,
                "birthdayPackage" => $birthdayPackage,
                "jumpers" => $jumpers,
                "partyRoom" => $partyRoom,
                "kitchenNotes" => $kitchenNotes,
                "eventNotes" => "",
                "privateNotes" => $privateNotes,
                "partyHost" => strtolower($partyHost) == "unassigned" ? "-" : $partyHost,
                "totalPizza" => 0,
                "totalGfPizza" => 0,
                "Bursdags Slush" => 0,
                "Bursdagsbrus" => 0,
                "Bursdagsis" => 0
            );        
        }
    }
}

function my_sort($a, $b)
{
    return strcmp($a["partyStartTime"], $b["partyStartTime"]);
}
usort($partyData, "my_sort");

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Party Sheets</title>
        <link rel="shortcut icon" type="image/png" href="altitude.png"/>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="bootstrap-datetimepicker.min.css">
        <script src="https://kit.fontawesome.com/a6bde2c958.js" crossorigin="anonymous"></script>
        <style>
            @page {
                size: A4;
                margin: 0;
            }
            @media print {
                tr:nth-child(even) td {
                    background-color: #dee2e6 !important;
                    -webkit-print-color-adjust: exact;
                }
                footer {
                    position: fixed;
                    bottom: 0;
                }
            }
            html {
                font-size: 13px;
            } 
        </style>
    </head>
    <body>
        <div class="container">
            <div class="col-lg-4 d-print-none">
                <form class="form-row align-items-end">
                    <div class="col text-right mb-2"><a href="?date=<?php echo $yesterdayDate->format("Y-m-d"); ?>"><i class="fas fa-less-than"></i></a></div>
                    <div class="col">
                        <label for="datetimepicker1"><strong>Dato</strong></label>
                        <input type='text' class="form-control" id='datetimepicker1' name="date" placeholder="2019-10-27" />
                    </div>
                    <div class="col mb-2"><a href="?date=<?php echo $tomorrowDate->format("Y-m-d"); ?>"><i class="fas fa-greater-than"></i></a></div>
                </form>
            </div>
            <?php
                foreach($partyData as $partyId => $party) {
                    if($party["totalPizza"] == 0) {
                        $party["totalPizza"] = "-";
                    }
                    if($party["jumpers"] == 0) {
                        $party["jumpers"] = "-";
                    }
                    if($party["totalGfPizza"] == 0) {
                        $party["totalGfPizza"] = "-";
                    }
                    if($party["PB Pizza Skinke"] == 0) {
                        $party["PB Pizza Skinke"] = "-";
                    }
                    if($party["PB Pizza Pepperoni"] == 0) {
                        $party["PB Pizza Pepperoni"] = "-";
                    }
                    if($party["PB Pizza Margherita"] == 0) {
                        $party["PB Pizza Margherita"] = "-";
                    }
                    if($party["PB Pizza Kjøttboller"] == 0) {
                        $party["PB Pizza Kjøttboller"] = "-";
                    }
                    if($party["PB Glutenfri Skinke"] == 0) {
                        $party["PB Glutenfri Skinke"] = "-";
                    }
                    if($party["PB Glutenfri Pepperoni"] == 0) {
                        $party["PB Glutenfri Pepperoni"] = "-";
                    }
                    if($party["PB Glutenfri Margherita"] == 0) {
                        $party["PB Glutenfri Margherita"] = "-";
                    }
                    if($party["PB Glutenfri Kjøttboller"] == 0) {
                        $party["PB Glutenfri Kjøttboller"] = "-";
                    }
                    if($party["Pølse"] == 0) {
                        $party["Pølse"] = "-";
                    }
                    if($party["Kylling og kalkun pølse"] == 0) {
                        $party["Kylling og kalkun pølse"] = "-";
                    }
                    if($party["Bursdags Slush"] == 0) {
                        $party["Bursdags Slush"] = "-";
                    }
                    if($party["Bursdagsbrus"] == 0) {
                        $party["Bursdagsbrus"] = "-";
                    }
                    if($party["Bursdagsis"] == 0) {
                        $party["Bursdagsis"] = "-";
                    }

                    echo "<div class=\"row align-items-center mb-5\" style=\"page-break-before:always\"><div class=\"col-sm-4\"><img src=\"altitude.png\" class=\"img-fluid rounded mx-auto d-block\" width=\"150px\" alt=\"Altitude Norway\"></div><div class=\"col-sm-4\"><h1 class=\"text-center\">Party Sheet</h1><h1 class=\"text-center\">" . $party["partyId"] ."</h1></div><div class=\"col-sm-4\"><img src=\"altitude.png\" class=\"img-fluid rounded mx-auto d-block\" width=\"150px\" alt=\"Altitude Norway\"></div></div>";
                    echo "<div class=\"row mb-5\"><div class=\"col-sm-4 text-center\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Dato:</strong> ". $party["partyStartDate"] . "</p></div><div class=\"col-sm-4 text-center\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Tid:</strong> " . $party["partyStartTime"] . " - " . $party["partyEndTime"] . "</p></div><div class=\"col-sm-4 text-center\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Rom:</strong> ". $party["partyRoom"] ."</p></div></div>";
                    echo "<div class=\"row mb-5\"><div class=\"col-sm-4 text-center\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Party Host:</strong> " . $party["partyHost"] ."</p></div><div class=\"col-sm-4 text-center\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Qty:</strong> " . $party["jumpers"] . "</p></div><div class=\"col-sm-4 text-center\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Pakke:</strong> " . $party["birthdayPackage"] . "</p></p></div></div>";
                    echo "<div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Bursdagsbarn:</strong> " . $party["guestOfHonor"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Foreldre:</strong> " . $party["parentName"] . "</p></div></div>";
                    echo "<div class=\"row justify-content-between\"><div class=\"col-sm-6 text-center border-right border-top border-dark\"><div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">Pizza:</strong> " . $party["totalPizza"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Skinke:</strong> ". $party["PB Pizza Skinke"] . "</p></div><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Pepperoni:</strong> ". $party["PB Pizza Pepperoni"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Margherita:</strong> " . $party["PB Pizza Margherita"] . "</p></div><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Kjøttboller:</strong> " . $party["PB Pizza Kjøttboller"] . "</p></div></div></div>";
                    echo "<div class=\"col-sm-6 text-center border-left border-top border-dark\"><div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">Glutenfri Pizza:</strong> " . $party["totalGfPizza"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">GF Skinke:</strong> ". $party["PB Glutenfri Skinke"] . "</p></div><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">GF Pepperoni:</strong> " . $party["PB Glutenfri Pepperoni"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">GF Margherita:</strong> " . $party["PB Glutenfri Margherita"] . "</p></div><div class=\"col-sm-6\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">GF Kjøttboller:</strong> " . $party["PB Glutenfri Kjøttboller"] . "</p></div></div></div></div>";
                    echo "<div class=\"row border-top border-bottom border-dark\"><div class=\"col-sm-6 text-center border-right border-dark\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">PØLSER:</strong> " . $party["Pølse"] . "</p></div><div class=\"col-sm-6 text-center border-left border-dark\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">Pølser (kylling):</strong> " . $party["Kylling og kalkun pølse"] . "</p></div></div>";
                    echo "<div class=\"row border-top border-bottom border-dark mb-5\"><div class=\"col-sm-4 text-center border-right border-dark\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">Bursdags Slush:</strong> " . $party["Bursdags Slush"] . "</p></div><div class=\"col-sm-4 text-center border-right border-left border-dark\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">Bursdagsbrus:</strong> " . $party["Bursdagsbrus"] . "</p></div><div class=\"col-sm-4 text-center border-left border-dark\"><p class=\"h1 font-weight-light\"><strong class=\"h1\">Bursdagsis:</strong> " . $party["Bursdagsis"] . "</p></div></div>";
                    echo "<div class=\"row mb-5\"><div class=\"col-sm-12\"><p><strong class=\"h3\"><hr/>Gave:<hr/></strong></p></div></div>";
                    echo "<div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Kitchet Notes:</strong> " . $party["kitchenNotes"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Event Notes:</strong> " . $party["eventNotes"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-12\"><p class=\"h3 font-weight-light\"><strong class=\"h3\">Private Notes:</strong> " . $party["privateNotes"] . "</p></div></div><div class=\"row mb-5\"><div class=\"col-sm-12\"><p><strong class=\"h3\"><hr/>Notes:<hr/></strong></p></div></div>";
                    echo "<div class=\"row\"><div class=\"col-sm-4 text-left\"><p>COPYRIGHT © 2019 ALTITUDE ASKER - ALL RIGHTS RESERVED</p></div><div class=\"col-sm-4 text-center\"><p>Tel: 66 98 22 00</p></div><div class=\"col-sm-4 text-right\"><p>email: info@altitude.no</p></div></div>";
                }
            ?>
            
        </div>

        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
        <script src="bootstrap-datetimepicker.min.js"></script>
        <script type="text/javascript">
            $(function() {
                $('#datetimepicker1').datetimepicker({
                    'format': 'YYYY-MM-DD',
                    'date': new Date(<?php echo "\"" . $setDate->format("Y-m-d") . "\""; ?>)
                });
                $("#datetimepicker1").on("dp.change", function (e) {
                    var date = e.date.toDate();
                    var dateValue = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
                    window.location.replace("?date=" + dateValue);
                });
            });
        </script>
    </body>
</html>
