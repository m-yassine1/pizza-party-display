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

class Delivery {
	private $id;
	private $foodDeliveryTime;
	private $products;

	function __construct($id, $foodDeliveryTime) {
		$this->id = $id;
		$this->foodDeliveryTime = $foodDeliveryTime;
		$this->products = [];
	}

	function getId() {
		return $this->id;
	}

	function getFoodDeliveryTime() {
		return $this->foodDeliveryTime;
	}

	function getProducts() {
		return $this->products;
	}

	function setId($id) {
		$this->id = $id;
	}

	function setFoodDeliveryTime($foodDeliveryTime) {
		$this->foodDeliveryTime = $foodDeliveryTime;
	}

	function addProduct($product) {
		if (!array_key_exists($product->getName(), $this->products)) {
			$this->products[$product->getName()] = $product; 
		} else {
			$total = $this->products[$product->getName()]->getQuantity() + $product->getQuantity();
			$this->products[$product->getName()]->setQuantity($total) ;
		}
	}

	function getTotalNumberOfPizzas($productName) {
		$total = 0;
		if($productName == null) {
			foreach($this->products as $product) {
				$total += $product->getQuantity();
			}
		} else {
			if(array_key_exists($productName, $this->products)) {
				$total +=  $this->products[$productName]->getQuantity();
			}
		}
		return $total;
	}
}

class Product {
	private $name;
	private $quantity;
	private $note;

	function __construct($name, $quantity, $note) {
		$this->name = $name;
		$this->quantity = $quantity == null ? 0 : $quantity;
		$this->note = $note;
	}

	function getName() {
		return $this->name;
	}

	function getQuantity() {
		return $this->quantity;
	}

	function getNote() {
		return $this->note;
	}

	function setName($name) {
		$this->name = $name;
	}

	function setQuantity($quantity) {
		$this->quantity = $quantity == null ? 0 : $quantity;
	}

	function setNote($note) {
		$this->note = $note;
	}
}

class Booking
{
	private $id;
	private $parentName;
	private $startTime;
	private $foodDeliveryTime;
	private $date;
	private $products;
	private $birthdayName;
	private $endTime;
	private $partyHost;
	private $numberOfPeople;
	private $bookingNote;
	private $note;
	private $dietaryNote;
	private $roomTotalTime;
	private $roomStartTime;
	private $roomName;


	function __construct($id, 
		$parentName, 
		$startTime, 
		$foodDeliveryTime, 
		$date,
		$birthdayName,
		$endTime,
		$partyHost,
		$numberOfPeople,
		$bookingNote,
		$note,
		$dietaryNote,
		$roomTotalTime,
		$roomStartTime,
		$roomName)
	{
		$this->id = $id;
		$this->parentName = $parentName;
		$this->startTime = $startTime;
		$this->foodDeliveryTime = $foodDeliveryTime;
		$this->birthdayName = $birthdayName;
		$this->date = $date;
		$this->products = [];
		$this->endTime = $endTime;
		$this->partyHost = $partyHost;
		$this->numberOfPeople = $numberOfPeople;
		$this->bookingNote = $bookingNote;
		$this->note = $note;
		$this->dietaryNote = $dietaryNote;
		$this->roomTotalTime = $roomTotalTime;
		$this->roomStartTime = $roomStartTime;
		$this->roomName = $roomName;
	}

	function getId() {
		return $this->id;
	}

	function getParentName() {
		return $this->parentName;
	}

	function getStartTime() {
		return $this->startTime;
	}

	function getFoodDeliveryTime() {
		return $this->foodDeliveryTime;
	}

	function getDate() {
		return $this->date;
	}

	function getProducts() {
		return $this->products;
	}

	function getBirthdayName() {
		return $this->birthdayName;
	}

	function getEndTime() {
		return $this->endTime;
	}

	function getPartyHost() {
		return $this->partyHost;
	}

	function getBookingNote() {
		return $this->bookingNote;
	}

	function getNote() {
		return $this->note;
	}

	function getDietaryNote() {
		return $this->dietaryNote;
	}

	function getRoomTotalTime() {
		return $this->roomTotalTime;
	}

	function getRoomStarTime() {
		return $this->roomStartTime;
	}

	function getRoomName() {
		return $this->roomName;
	}

	function setId($id) {
		$this->id = $id;
	}

	function setParentName($parentName) {
		$this->parentName = $parentName;
	}

	function setStartTime($startTime) {
		$this->startTime = $startTime;
	}

	function setFoodDeliveryTime($foodDeliveryTime) {
		$this->foodDeliveryTime = $foodDeliveryTime;
	}

	function setDate($date) {
		$this->date = $date;
	}

	function setEndTime($endTime) {
		$this->endTime = $endTime;
	}

	function setPartyHost($partyHost) {
		$this->partyHost = $partyHost;
	}

	function setNumberOfPeople($numberOfPeople) {
		$this->numberOfPeople = $numberOfPeople;
	}

	function setBookingNote($bookingNote) {
		$this->bookingNote = $bookingNote;
	}

	function setNote($note) {
		$this->note = $note;
	}

	function setDietaryNote($dietaryNote) {
		$this->dietaryNote = $dietaryNote;
	}

	function setRoomTotalTime($roomTotalTime) {
		$this->roomTotalTime = $roomTotalTime;
	}

	function setRoomStartTime($roomStartTime) {
		$this->roomStartTime = $roomStartTime;
	}

	function setRoomName($roomName) {
		$this->roomName = $roomName;
	}

	function getProductNote($productName) {
		return array_key_exists($productName, $this->products) ? $this->products[$productName]->getNote() : null;
	}

	function getProduct($productName) {
		return array_key_exists($productName, $this->products) ? $this->products[$productName] : null;
	}

	function addProduct($product) {
		if (!array_key_exists($product->getName(), $this->products)) {
			$this->products[$product->getName()] = $product; 
		} else {
			$total = $this->products[$product->getName()]->getQuantity() + $product->getQuantity();
			$this->products[$product->getName()]->setQuantity($total) ;
		}
	}

	function getTotalNumberOfPizzas($productName) {
		$total = 0;
		if($productName == null) {
			foreach($this->products as $product) {
				$total += $product->getQuantity();
			}
		} else {
			if(array_key_exists($productName, $this->products)) {
				$total +=  $this->products[$productName]->getQuantity();
			}
		}
		return $total;
	}
}

?>