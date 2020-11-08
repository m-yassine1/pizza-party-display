<?php

$setDate = date_create();

//Date format should be 2013-03-15
if(isset($_GET["date"])) {
    $setDate = date_create_from_format("Y-m-d", $_GET["date"]);
}

$tomorrowDate = date('Y-m-d H:i:s.u',strtotime('+1 day', strtotime($setDate->format("Y-m-d H:i:s.u"))));
$tomorrowDate = date_create_from_format("Y-m-d H:i:s.u", $tomorrowDate);

$yesterdayDate = date('Y-m-d H:i:s.u',strtotime('-1 day', strtotime($setDate->format("Y-m-d H:i:s.u"))));
$yesterdayDate = date_create_from_format("Y-m-d H:i:s.u", $yesterdayDate);

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

function isLine($line) {
	return preg_match('/^"BKN.*$/i', $line) == 1;
}

function getValue($quantity) {
	return $quantity == null || $quantity == 0 ? "-" : $quantity;
}

function getDeliveryTotals($productName, $deliveries) {
	$total = 0;
	if($productName == null) {
		foreach($deliveries as $delivery) {
			foreach ($delivery->getProducts() as $product) {
				$total += $product->getQuantity();
			}
		}
	} else {
		foreach($deliveries as $delivery) {
			if(array_key_exists($productName, $delivery->getProducts())) {
				$total += $delivery->getProducts()[$productName]->getQuantity();
			}
		}
	}
	return $total;
}

?>