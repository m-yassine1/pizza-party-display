<?php
$setDate = date_create();

//Must read as 2013-03-15
if(isset($_GET["date"])) {
    $setDate = date_create_from_format("Y-m-d", $_GET["date"]);
}

$tomorrowDate = date('Y-m-d H:i:s.u',strtotime('+1 day',strtotime($setDate->format("Y-m-d H:i:s.u"))));
$tomorrowDate = date_create_from_format("Y-m-d H:i:s.u", $tomorrowDate);

$yesterdayDate = date('Y-m-d H:i:s.u',strtotime('-1 day',strtotime($setDate->format("Y-m-d H:i:s.u"))));
$yesterdayDate = date_create_from_format("Y-m-d H:i:s.u", $yesterdayDate);

$lines = gzfile('https://altitude-ce-aws.s3.eu-central-1.amazonaws.com/Pizza_Sheet/AltitudeNorwayPizzaSheetAWS.csv.gz');

$partyData = array();
$pizzaSummary = array();

$pizzaSummary["Total"] = array(
    "partyStartTime" => "Total",
    "PB Glutenfri Skinke" => 0,
    "PB Glutenfri Kjøttboller" => 0,
    "PB Glutenfri Pepperoni" => 0,
    "PB Glutenfri Margherita" => 0,
    "PB Pizza Kjøttboller" => 0,
    "PB Pizza Margherita" => 0,
    "PB Pizza Pepperoni" => 0,
    "PB Pizza Skinke" => 0,
    "total" => 0
);

// Remove the first line
$firstLine = true;
foreach ($lines as $line) {
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
    if($pizzaType == "Pølse" || $pizzaType == "Kylling og kalkun pølse" || $pizzaType == "Bursdagsis" || $pizzaType == "Bursdagsbrus" || $pizzaType == "Bursdags Slush") {
        continue;
    }
    $partyStartTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($partyStartTime)));
    if(strtolower($foodDeliveryTime) == "null") {
        $foodDeliveryTime = date('Y-m-d H:i:s.u',strtotime('+30 minutes',strtotime($partyStartTime->format("Y-m-d H:i:s.u"))));
        $foodDeliveryTime = date_create_from_format("Y-m-d H:i:s.u", $foodDeliveryTime);
    }
    else {
        $foodDeliveryTime = date_create_from_format("Y-m-d H:i:s.u", date('Y-m-d H:i:s.u', strtotime($foodDeliveryTime)));
    }

    if($foodDeliveryTime->format("Y-m-d") == $setDate->format("Y-m-d") && $eventStatus == "Active") {
        $time = $foodDeliveryTime->format("H:i");
        if(!array_key_exists($time, $pizzaSummary)) {
            $pizzaSummary[$time] = array(
                "partyStartTime" => $time,
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
                "total" => 0
            );
        }
        $pizzaSummary[$time][$pizzaType] = $pizzaSummary[$time][$pizzaType] + intval($numberOfPizzas);
        $pizzaSummary[$time]["total"] = $pizzaSummary[$time]["total"] + intval($numberOfPizzas);
        $pizzaSummary["Total"][$pizzaType] = $pizzaSummary["Total"][$pizzaType] + intval($numberOfPizzas);
        $pizzaSummary["Total"]["total"] = $pizzaSummary["Total"]["total"] + intval($numberOfPizzas);
    }

    if($partyStartTime->format("Y-m-d") == $setDate->format("Y-m-d") && $eventStatus == "Active") {
        if(array_key_exists($partyId, $partyData)) {
            $partyData[$partyId][$pizzaType] = $partyData[$partyId][$pizzaType] + intval($numberOfPizzas);
            $partyData[$partyId]["total"] = $partyData[$partyId]["total"] + intval($numberOfPizzas);
        }
        else {
            $partyData[$partyId] = array(
                "partyStartTime" => $partyStartTime->format("H:i"),
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
                "total" => 0
            );
            $partyData[$partyId][$pizzaType] = $partyData[$partyId][$pizzaType] + intval($numberOfPizzas);
            $partyData[$partyId]["total"] = $partyData[$partyId]["total"] + intval($numberOfPizzas);
        }
    }
}

function my_sort($a,$b)
{
    return strcmp($a["partyStartTime"], $b["partyStartTime"]);
}

usort($partyData, "my_sort");

$totals = array_shift($pizzaSummary);
array_push($pizzaSummary, $totals);
usort($pizzaSummary, "my_sort");

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Pizza Orders</title>
        <link rel="shortcut icon" type="image/png" href="altitude.png"/>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="bootstrap-datetimepicker.min.css">
        <script src="https://kit.fontawesome.com/a6bde2c958.js" crossorigin="anonymous"></script>
        <style>
            @page {
                size: A4 landscape;
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
        <div class="container-fluid">
            <div class="row align-items-center justify-content-between">
                <div class="col-lg-2">
                    <img src="altitude.png" class="img-fluid rounded mx-auto d-block" width="150px" alt="Altitude Norway">
                </div>
                <div class="col-lg-4">
                    <form class="form-row align-items-end d-print-none">
                        <div class="col text-right mb-2"><a href="?date=<?php echo $yesterdayDate->format("Y-m-d"); ?>"><i class="fas fa-less-than"></i></a></div>
                        <div class="col">
                            <label for="datetimepicker1"><strong>Dato</strong></label>
                            <input type='text' class="form-control" id='datetimepicker1' name="date" placeholder="2019-10-27" />
                        </div>
                        <div class="col mb-2"><a href="?date=<?php echo $tomorrowDate->format("Y-m-d"); ?>"><i class="fas fa-greater-than"></i></a></div>
                    </form>
                </div>
            </div>

            <div class="row page">
                <div class="col-sm-12">
                    <h3 class="text-center">Party List</h3>
                    <h5 class="text-center"><?php echo $setDate->format("l Y-m-d"); ?></h5>
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr class="text-center">
                                <th scope="col">Start Tid</th>
                                <th scope="col">Party ID</th>
                                <th scope="col">Party Name</th>
                                <th scope="col">Skinke</th>
                                <th scope="col">Kjøttboller</th>
                                <th scope="col">Pepperoni</th>
                                <th scope="col">Margherita</th>
                                <th scope="col">GF Skinke</th>
                                <th scope="col">GF Kjøttboller</th>
                                <th scope="col">GF Pepperoni</th>
                                <th scope="col">GF Margherita</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach($partyData as $partyId => $party) {
                                    echo "<tr class=\"text-center\"><th scope=\"row\">" . $party["partyStartTime"] . "</th>";
                                    echo "<td>" . $party["partyId"] . "</td>";
                                    echo "<td class=\"text-left\">" . $party["parentName"] . "</td>";
                                    echo "<td>" . ($party["PB Pizza Skinke"] == 0 ? "-" : $party["PB Pizza Skinke"]) . "</td>";
                                    echo "<td>" . ($party["PB Pizza Kjøttboller"] == 0 ? "-" : $party["PB Pizza Kjøttboller"]) . "</td>";
                                    echo "<td>" . ($party["PB Pizza Pepperoni"] == 0 ? "-" : $party["PB Pizza Pepperoni"]) . "</td>";
                                    echo "<td>" . ($party["PB Pizza Margherita"] == 0 ? "-" : $party["PB Pizza Margherita"]) . "</td>";
                                    echo "<td>" . ($party["PB Glutenfri Skinke"] == 0 ? "-" : $party["PB Glutenfri Skinke"]) . "</td>";
                                    echo "<td>" . ($party["PB Glutenfri Kjøttboller"] == 0 ? "-" : $party["PB Glutenfri Kjøttboller"]) . "</td>";
                                    echo "<td>" . ($party["PB Glutenfri Pepperoni"] == 0 ? "-" : $party["PB Glutenfri Pepperoni"]) . "</td>";
                                    echo "<td>" . ($party["PB Glutenfri Margherita"] == 0 ? "-" : $party["PB Glutenfri Margherita"]) . "</td>";
                                    echo "<td>" . ($party["total"] == 0 ? "-" : $party["total"]) . "</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                  </div>
                </div>
            </div>
            <hr style="color: black" class="d-print-none" />
            <div class="row align-items-center justify-content-between" style="page-break-before:always">
                <div class="col-lg-2">
                    <img src="altitude.png" class="img-fluid rounded mx-auto d-block" width="150px" alt="Altitude Norway">
                </div>
            </div>
            <div class="row page">
                <div class="col-sm-12">
                    <h3 class="text-center">Leveranse List</h3>
                    <h5 class="text-center"><?php echo $setDate->format("l Y-m-d"); ?></h5>
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover table-sm">
                        <thead>
                            <tr class="text-center">
                                <th scope="col">Leveranse Nummer</th>
                                <th scope="col">Leveranse Tid</th>
                                <th scope="col">Skinke</th>
                                <th scope="col">Kjøttboller</th>
                                <th scope="col">Pepperoni</th>
                                <th scope="col">Margherita</th>
                                <th scope="col">GF Skinke</th>
                                <th scope="col">GF Kjøttboller</th>
                                <th scope="col">GF Pepperoni</th>
                                <th scope="col">GF Margherita</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $num = 1;
                                foreach($pizzaSummary as $time => $pizzas) {
                                    if($pizzas["total"] == 0 && $time != "Total") {
                                        continue;
                                    }
                                    $index = "<td></td>";
                                    if($pizzas["partyStartTime"] != "Total") {
                                        $index = "<td>" . $num++ . "</td>";
                                    }
                                    echo "<tr class=\"text-center\">" . $index . "<th scope=\"row\">" . $pizzas["partyStartTime"] . "</th>";
                                    echo "<td>" . ($pizzas["PB Pizza Skinke"] == 0 ? "-" : $pizzas["PB Pizza Skinke"]) . "</td>";
                                    echo "<td>" . ($pizzas["PB Pizza Kjøttboller"] == 0 ? "-" : $pizzas["PB Pizza Kjøttboller"]) . "</td>";
                                    echo "<td>" . ($pizzas["PB Pizza Pepperoni"] == 0 ? "-" : $pizzas["PB Pizza Pepperoni"]) . "</td>";
                                    echo "<td>" . ($pizzas["PB Pizza Margherita"] == 0 ? "-" : $pizzas["PB Pizza Margherita"]) . "</td>";
                                    echo "<td>" . ($pizzas["PB Glutenfri Skinke"] == 0 ? "-" : $pizzas["PB Glutenfri Skinke"]) . "</td>";
                                    echo "<td>" . ($pizzas["PB Glutenfri Kjøttboller"] == 0 ? "-" : $pizzas["PB Glutenfri Kjøttboller"]) . "</td>";
                                    echo "<td>" . ($pizzas["PB Glutenfri Pepperoni"] == 0 ? "-" : $pizzas["PB Glutenfri Pepperoni"]) ."</td>";
                                    echo "<td>" . ($pizzas["PB Glutenfri Margherita"] == 0 ? "-" : $pizzas["PB Glutenfri Margherita"]) . "</td>";
                                    echo "<td>" . ($pizzas["total"] == 0 ? "-" : $pizzas["total"]) . "</td></tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                  </div>
                </div>
            </div>
            <footer class="row">
                <div class="col-sm-4 text-left">COPYRIGHT © 2019 ALTITUDE ASKER - ALL RIGHTS RESERVED</div>
                <div class="col-sm-4 text-center">Tel: 66 98 22 00</div>
                <div class="col-sm-4 text-right">email: info@altitude.no</div>
            </footer>
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
