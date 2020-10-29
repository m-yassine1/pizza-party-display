<?php

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

$pizzaTitleMappings = [
	"Pizza Skinke (3-4 personer)" => "Skinke",
	"Pizza Pepperoni (3-4 personer)" => "Pepperoni",
	"Pizza Kjøttboller (3-4 personer)" => "Kjøttboller",
	"Pizza Margherita (3-4 personer)" => "Margherita",
	"Glutenfri Pizza Pepperoni (1 person)" => "GF Pepperoni",
	"Glutenfri Pizza Skinke (1 person)" => "GF Skinke",
	"Glutenfri Pizza Kjøttboller (1 person)" => "GF Kjøttboller",
	"Glutenfri Pizza Margherita (1 person)" => "GF Margherita",
	"Pizzabakerens meny (3-4 personer)" => "PB Meny"
];

function getPizzaTitle($productName, $pizzaTitleMappings) {
	return array_key_exists($productName, $pizzaTitleMappings) ? $pizzaTitleMappings[$productName] :  null;
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
			$this->products[$product->getName()].setQuantity($total) ;
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
	private $title;
	private $quantity;
	private $note;

	function __construct($name, $title, $quantity, $note) {
		$this->name = $name;
		$this->quantity = $quantity == null ? 0 : $quantity;
		$this->note = $note;
		$this->title = $title == null ? $name : $title;
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

	function getTitle() {
		return $this->title;
	}

	function setTitle($title) {
		$this->name = $title == null ? $name : $title;
	}

	function setName($name) {
		$this->name = $name;
	}

	function setQuantity($quantity) {
		$this->quantity = $quantity == null ? 0 : $quantity < 0 ? 0 : $quantity;
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

	function __construct($id, $parentName, $startTime, $foodDeliveryTime, $date) {
		$this->id = $id;
		$this->parentName = $parentName;
		$this->startTime = $startTime;
		$this->foodDeliveryTime = $foodDeliveryTime;
		$this->date = $date;
		$this->products = [];
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