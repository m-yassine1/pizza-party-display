<?php
require "models.php";

$setDate = date_create();

//Date format should be 2013-03-15
if(isset($_GET["date"])) {
    $setDate = date_create_from_format("Y-m-d", $_GET["date"]);
}

$tomorrowDate = date('Y-m-d H:i:s.u',strtotime('+1 day', strtotime($setDate->format("Y-m-d H:i:s.u"))));
$tomorrowDate = date_create_from_format("Y-m-d H:i:s.u", $tomorrowDate);

$yesterdayDate = date('Y-m-d H:i:s.u',strtotime('-1 day', strtotime($setDate->format("Y-m-d H:i:s.u"))));
$yesterdayDate = date_create_from_format("Y-m-d H:i:s.u", $yesterdayDate);

$bookings = [];
$deliveries = [];
$num = 1;

$lines = gzfile('https://altitude-ce-aws.s3.eu-central-1.amazonaws.com/Pizza_Sheet/PB_Pizza_Delivery_List_S3.csv');
foreach ($lines as $line) {
    if(preg_match('/^"BKN.*$/i', $line) != 1) {
        continue;
    }

    if(empty(trim($line))) {
        continue;
    }

    list($partyId, $parentName, $partyStartTimeString, $partyDate, $foodDeliveryTimeString, $pizzaType, $numberOfPizzas, $notes) = explode(";", $line);

    $partyDate = explode(",", str_replace("\"", "", $partyDate))[0];
    $partyId = str_replace("\"", "", $partyId);
    $parentName = str_replace("\"", "", $parentName);
    $partyStartTimeString = str_replace("\"", "", $partyStartTimeString);
    $foodDeliveryTimeString = str_replace("\"", "", $foodDeliveryTimeString);
    $pizzaType = str_replace("\"", "", $pizzaType);
    $numberOfPizzas = str_replace("−", "-", str_replace("\"", "", $numberOfPizzas));
    $notes = str_replace("\"", "", $notes);

    if($pizzaType == "Pølse" || $pizzaType == "Kylling og kalkun pølse" || $pizzaType == "Bursdagsis" || $pizzaType == "Bursdagsbrus" || $pizzaType == "Bursdags Slush") {
        continue;
    }

    $partyStartTime = date_create_from_format("d.m.Y H:i", $partyDate . ' ' . $partyStartTimeString);

    if($partyStartTime->format("Y-m-d") == $setDate->format("Y-m-d")) {
        $foodDeliveryTime = date_create_from_format("d.m.Y H:i", $partyDate . ' ' . $foodDeliveryTimeString);

        $product = new Product(
            $pizzaType,
            getPizzaTitle($pizzaType, $pizzaTitleMappings),
            intval($numberOfPizzas),
            $notes
        );

        if(!array_key_exists($partyId, $bookings)) {
            $bookings[$partyId] = new Booking(
                $partyId,
                $parentName,
                $partyStartTime->format("H:i"),
                $foodDeliveryTime->format("H:i"),
                $setDate->format("Y-m-d")       
            );
        }

        if(!array_key_exists($foodDeliveryTime->format("H:i"), $deliveries)) {
            $deliveries[$foodDeliveryTime->format("H:i")] = new Delivery(
                $num++,
                $foodDeliveryTime->format("H:i")    
            );
        }

        $deliveries[$foodDeliveryTime->format("H:i")]->addProduct($product);
        $bookings[$partyId]->addProduct($product);
    }
}

function booking_sort($a, $b)
{
    return strcmp($a->getStartTime(), $b->getStartTime());
}

function delivery_sort($a, $b)
{
    return strcmp($a->getFoodDeliveryTime(), $b->getFoodDeliveryTime());
}


usort($bookings, "booking_sort");
usort($deliveries, "delivery_sort");

?>
<!DOCTYPE html>
<html lang="no">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Pizza Orders</title>
        <link rel="shortcut icon" type="image/png" href="altitude.png"/>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
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
                                <th>Start Tid</th>
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
                                <th scope="col">PB Meny</th>
                                <th scope="col">Total</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach($bookings as $booking) {
                                    echo "<tr class=\"text-center\">";
                                    echo "<th scope=\"row\">" . $booking->getStartTime() . "</th>";
                                    echo "<td>" . $booking->getId() . "</td>";
                                    echo "<td class=\"text-left\">" . $booking->getParentName() . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Pizza Skinke (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Pizza Kjøttboller (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Pizza Pepperoni (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Pizza Margherita (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Glutenfri Pizza Skinke (1 person)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Glutenfri Pizza Kjøttboller (1 person)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Glutenfri Pizza Pepperoni (1 person)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Glutenfri Pizza Margherita (1 person)")) . "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas("Pizzabakerens meny (3-4 personer)")). "</td>";
                                    echo "<td>" . getValue($booking->getTotalNumberOfPizzas(null)) . "</td>";
                                    echo "<td>" . getValue($booking->getProductNote("Pizzabakerens meny (3-4 personer)")). "</td></tr>";
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
                                <th scope="col">PB Meny</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $num = 1;
                                foreach($deliveries as $delivery) {
                                    echo "<tr class=\"text-center\">";
                                    echo "<td>" . $delivery->getId() . "</td>";
                                    echo "<th scope=\"row\">" . $delivery->getFoodDeliveryTime() . "</th>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Pizza Skinke (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Pizza Kjøttboller (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Pizza Pepperoni (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Pizza Margherita (3-4 personer)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Glutenfri Pizza Skinke (1 person)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Glutenfri Pizza Kjøttboller (1 person)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Glutenfri Pizza Pepperoni (1 person)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Glutenfri Pizza Margherita (1 person)")) . "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas("Pizzabakerens meny (3-4 personer)")). "</td>";
                                    echo "<td>" . getValue($delivery->getTotalNumberOfPizzas(null)) . "</td></tr>";
                                }
                                echo "<tr class=\"text-center\">";
                                echo "<td></td>";
                                echo "<th>Total</th>";
                                echo "<td>" . getValue(getDeliveryTotals("Pizza Skinke (3-4 personer)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Pizza Kjøttboller (3-4 personer)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Pizza Pepperoni (3-4 personer)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Pizza Margherita (3-4 personer)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Glutenfri Pizza Skinke (1 person)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Glutenfri Pizza Kjøttboller (1 person)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Glutenfri Pizza Pepperoni (1 person)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Glutenfri Pizza Margherita (1 person)", $deliveries)) . "</td>";
                                echo "<td>" . getValue(getDeliveryTotals("Pizzabakerens meny (3-4 personer)", $deliveries)). "</td>";
                                echo "<td>" . getValue(getDeliveryTotals(null, $deliveries)) . "</td></tr>";
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

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.5.3/umd/popper.min.js" integrity="sha512-53CQcu9ciJDlqhK7UD8dZZ+TF2PFGZrOngEYM/8qucuQba+a+BXOIRsp9PoMNJI3ZeLMVNIxIfZLbG/CdHI5PA==" crossorigin="anonymous"></script>        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous"></script>        <script src="bootstrap-datetimepicker.min.js"></script>
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
