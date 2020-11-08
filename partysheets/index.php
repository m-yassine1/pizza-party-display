<?php
require_once("../scripts/functions.php");
require_once("../scripts/models.php");

$bookings = [];

$partySheetLines = file('https://altitude-ce-aws.s3.eu-central-1.amazonaws.com/Pizza_Sheet/Party_Sheet_S3.csv');
$hasMoreNotes = false;
$specialLine = "";
foreach ($partySheetLines as $line) {
    if(!$hasMoreNotes and !isLine($line)) {
        continue;
    }

    $specialLine .= $line;

    if(!endsWith($specialLine, "\"\n")) {
        $hasMoreNotes = true;
        continue;
    }

    list($partyId, $parentName, $guestOfHonor, $partyStartTimeString, $partyEndTimeString, $partyDate, $roomTimeString, $roomBookingDuration, $partyHost, $numberOfPeople, $partyRoom, $bookingNote, $note, $dietaryNote) = explode("\",\"", $specialLine);

    $hasMoreNotes = false;
    $specialLine = "";
    $numberOfPeople = str_replace("\"", "", $numberOfPeople);
    $partyId = str_replace("\"", "", $partyId);
    $parentName = str_replace("\"", "", $parentName);
    $guestOfHonor = str_replace("\"", "", $guestOfHonor);
    $partyStartTimeString = str_replace("\"", "", $partyStartTimeString);
    $partyEndTimeString = str_replace("\"", "", $partyEndTimeString);
    $roomTimeString = str_replace("\"", "", $roomTimeString);
    $roomBookingDuration = str_replace("\"", "", $roomBookingDuration);
    $partyHost = str_replace("\"", "", $partyHost);
    $partyRoom = str_replace("\"", "", $partyRoom);
    $bookingNote = str_replace("\"", "", $bookingNote);
    $note = str_replace("\"", "", $note);
    $dietaryNote = str_replace("\"", "", $dietaryNote);
    $partyDate = str_replace("\"", "", $partyDate);
    
    $partyStartTime = date_create_from_format("d.m.Y H:i", $partyDate . ' ' . $partyStartTimeString);
    $partyEndTime = date_create_from_format("d.m.Y H:i", $partyDate . ' ' . $partyEndTimeString);
    $roomTime = date_create_from_format("d.m.Y H:i", $partyDate . ' ' . $roomTimeString);
    
    if($partyStartTime->format("Y-m-d") == $setDate->format("Y-m-d") and !array_key_exists($partyId, $bookings))
    {
        $bookings[$partyId] = new Booking(
            $partyId,
            $parentName,
            $partyStartTime->format("H:i"),
            null,
            $setDate->format("Y-m-d"),
            $guestOfHonor,
            $partyEndTime->format("H:i"),
            $partyHost,
            intval($numberOfPeople),
            $bookingNote,
            $note,
            $dietaryNote,
            intval($roomBookingDuration),
            $roomTime->format("H:i"),
            $partyRoom
        );
    }
}

$lines = file('https://altitude-ce-aws.s3.eu-central-1.amazonaws.com/Pizza_Sheet/Birthday_Booking_Products_S3.csv');
foreach ($lines as $line) {
    if(!isLine($line)) {
        continue;
    }

    list($partyId, $productName, $quantity, $note) = explode("\",\"", $line);

    $productName = str_replace("\"", "", $productName);
    $partyId = str_replace("\"", "", $partyId);
    $quantity = str_replace("−", "-", str_replace("\"", "", $quantity));
    $note = str_replace("\"", "", $note);

    if(array_key_exists($partyId, $bookings)) {
        $product = new Product(
            $productName,
            intval($quantity),
            $note
        );
        $bookings[$partyId]->addProduct($product);
    }
}

function booking_sort($a, $b)
{
    return strcmp($a->getStartTime(), $b->getStartTime());
}
usort($bookings, "booking_sort");

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Party Sheets</title>
        <link rel="shortcut icon" type="image/png" href="../img/altitude.png"/>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
        <link rel="stylesheet" href="../css/bootstrap-datetimepicker.min.css">
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

                tr:nth-child(even) th {
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
            <?php foreach($bookings as $booking): ?>
                <div class="row align-items-center mb-5" style="page-break-before:always">
                    <div class="col-sm-3">
                        <img src="../img/altitude.png" class="img-fluid rounded mx-auto d-block" width="150px" alt="Altitude Norway">
                    </div>
                    <div class="col-sm-6">
                        <h1 class="text-center">Party Sheet</h1>
                        <h1 class="text-center"><?php echo $booking->getId(); ?></h1>
                        <h1 class="text-center"><strong class="h3">Party Host: </strong><?php echo $booking->getPartyHost(); ?></h1>
                    </div>
                    <div class="col-sm-3">
                        <img src="../img/altitude.png" class="img-fluid rounded mx-auto d-block" width="150px" alt="Altitude Norway">
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="col-sm-4">
                        <p class="h3 font-weight-light"><strong class="h3">Dato: </strong><?php echo $booking->getDate(); ?></p>
                    </div>
                    <div class="col">
                        <p class="h3 font-weight-light"><strong class="h3">Tid: </strong><?php echo $booking->getStartTime(); ?>-<?php echo $booking->getEndtime(); ?></p>
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="col-sm-4">
                        <p class="h3 font-weight-light"><strong class="h3">Rom: </strong><?php echo $booking->getRoomName(); ?> </p>
                    </div>
                    <div class="col">
                        <p class="h3 font-weight-light"><strong class="h3">Rom Tid: </strong><?php echo $booking->getRoomStarTime(); ?> (<?php echo $booking->getRoomTotalTime(); ?> min)</p>
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="col-sm-4">
                        <p class="h3 font-weight-light"><strong class="h3">Foreldre: </strong><?php echo $booking->getParentName(); ?></p>
                    </div>
                    <div class="col">
                        <p class="h3 font-weight-light"><strong class="h3">Bursdagsbarn: </strong><?php echo $booking->getBirthdayName(); ?></p>
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="col-sm-12">
                        <p><strong class="h3"><hr/>Gave:<hr/></strong></p>
                    </div>
                </div>            
                <table class="table table-striped table-bordered table-hover table-sm h4">
                    <thead>
                        <th scope="col">Product Name</th>
                        <th scope="col" class="text-center">Quantity</th>
                    </thead>
                    <tbody>
                        <?php foreach($booking->getProducts() as $product): ?>
                            <tr class="text-center">
                                <th scope="row" class="text-left"><?php echo $product->getName(); ?></th>
                                <td><?php echo getValue($product->getQuantity()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="row mb-4">
                    <div class="col-sm-12">
                        <p class="h3 font-weight-light"><strong class="h3">Pizzabakeren Notes: </strong><?php echo $booking->getProductNote("Pizzabakerens meny (3-4 personer)"); ?></p>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-sm-12">
                        <p class="h3 font-weight-light"><strong class="h3">Booking Notes: </strong><?php echo $booking->getBookingNote(); ?></p>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-sm-12">
                        <p class="h3 font-weight-light"><strong class="h3">Notes: </strong><?php echo $booking->getNote(); ?></p>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-sm-12">
                        <p class="h3 font-weight-light"><strong class="h3">Dietary Notes: </strong><?php echo $booking->getDietaryNote(); ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 text-left">
                        <p>COPYRIGHT &#169; <?php echo date("Y"); ?> ALTITUDE ASKER - ALL RIGHTS RESERVED</p>
                    </div>
                    <div class="col-sm-3 text-center">
                        <p>Tel: 66 98 22 00</p>
                    </div>
                    <div class="col-sm-3 text-right">
                        <p>email: info@altitude.no</p>
                    </div>
                </div>
            <?php endforeach; ?>
            
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous"></script>        
        <script src="../js/bootstrap-datetimepicker.min.js"></script>
        <script type="text/javascript">
            $(function() {
                $('#datetimepicker1').datetimepicker({
                    'format': 'YYYY-MM-DD',
                    'date': new Date("<?php echo $setDate->format("Y-m-d"); ?>")
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
