<?php
class InventoryInfo {
	private $dbh = null;
	private $p1 = null;
	private $p2 = null;
	private $p3 = null;
	function __construct($dbh)
	{
		$this->dbh = $dbh;
//		$this->dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
//				array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
//					PDO::ATTR_PERSISTENT => false));
		# 錯誤的話, 就不做了
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		$this->p1 = $this->dbh->prepare("select avg(price) as avg from inventory where shop_id=:shopID
				and status != -1");
		$this->p2 = $this->dbh->prepare("delete inventory from inventory left join order_items on order_items.inventory_id = inventory.id where order_items.id is null and inventory.shop_id=:shopID");
		$this->p3 = $this->dbh->prepare("insert into `inventory` (`shop_id`,`item_id`,`qty`,`price`,
				`bulk_qty`,`sale`,`condition`,`note`,`remark`,`tier_qty1`,`tier_price1`,`tier_qty2`,
				`tier_price2`,`tier_qty3`,`tier_price3`,`my_cost`,`featured`,`force_quote`,`status`,
				`weight`,`createtime`,`modtime`) values (:shop_id,:item_id,:qty,:price,:bulk_qty,:sale,:condition,
				:note,:remark,:tier_qty1,:tier_price1,:tier_qty2,:tier_price2,:tier_qty3,:tier_price3,
				:my_cost,:featured,:force_quote,:status,:weight,unix_timestamp(now()),unix_timestamp(now()))");
	}

	function __destruct()
	{
		
	}
	function GetAvgPrice($shopID)
	{
		try {
			$this->p1->bindParam(':shopID',$shopID,PDO::PARAM_STR);
			$this->p1->execute();
			if($this->p1->rowCount() == 0)
				return 1;
			$resData = $this->p1->fetch(PDO::FETCH_OBJ);
			if(isset($resData->avg))
			{
				$value = $resData->avg;
				if($value > 0) $value = round($value,2);
				return $value;
			}
			return 1;
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/InventoryInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return 0;
		}
	}

	function DeleteItem($shopID)
	{
		try {
			$this->p2->bindParam(':shopID',$shopID,PDO::PARAM_STR);
			$this->p2->execute();
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/InventoryInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return null;
		}
	}

	function InsertItem($item)
	{
		try {
			$this->p3->bindParam(':shop_id',$item['shop_id'],PDO::PARAM_STR);
			$this->p3->bindParam(':item_id',$item['item_id'],PDO::PARAM_STR);
			$this->p3->bindParam(':qty',$item['qty'],PDO::PARAM_STR);
			$this->p3->bindParam(':price',$item['price'],PDO::PARAM_STR);
			$this->p3->bindParam(':bulk_qty',$item['bulk_qty'],PDO::PARAM_STR);
			$this->p3->bindParam(':sale',$item['sale'],PDO::PARAM_STR);
			$this->p3->bindParam(':condition',$item['condition'],PDO::PARAM_STR);
			$this->p3->bindParam(':note',$item['note'],PDO::PARAM_STR);
			$this->p3->bindParam(':remark',$item['remark'],PDO::PARAM_STR);
			$this->p3->bindParam(':tier_qty1',$item['tier_qty1'],PDO::PARAM_STR);
			$this->p3->bindParam(':tier_price1',$item['tier_price1'],PDO::PARAM_STR);
			$this->p3->bindParam(':tier_qty2',$item['tier_qty2'],PDO::PARAM_STR);
			$this->p3->bindParam(':tier_price2',$item['tier_price2'],PDO::PARAM_STR);
			$this->p3->bindParam(':tier_qty3',$item['tier_qty3'],PDO::PARAM_STR);
			$this->p3->bindParam(':tier_price3',$item['tier_price3'],PDO::PARAM_STR);
			$this->p3->bindParam(':my_cost',$item['my_cost'],PDO::PARAM_STR);
			$this->p3->bindParam(':featured',$item['featured'],PDO::PARAM_STR);
			$this->p3->bindParam(':force_quote',$item['force_quote'],PDO::PARAM_STR);
			$this->p3->bindParam(':status',$item['status'],PDO::PARAM_STR);
			$this->p3->bindParam(':weight',$item['weight'],PDO::PARAM_STR);
			$this->p3->execute($item);
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/InventoryInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return null;
		}
	}
}
?>
