<?php
class InventoryInfo {
	private $dbh = null;
	function __construct($dbh)
	{
		$this->dbh = $dbh;
//		$this->dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
//				array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
//					PDO::ATTR_PERSISTENT => false));
		# 錯誤的話, 就不做了
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	}

	function __destruct()
	{
		
	}
	function GetAvgPrice($shopID)
	{
		try {
			$p = $this->dbh->prepare("select avg(price) as avg from inventory where shop_id=:shopID
				and status != -1");
			$p->bindParam(':shopID',$shopID,PDO::PARAM_STR);
			$p->execute();
			if($p->rowCount() == 0)
				return 1;
			$resData = $p->fetch(PDO::FETCH_OBJ);
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
			$p = $this->dbh->prepare("delete inventory from inventory left join order_items on order_items.inventory_id = inventory.id where order_items.id is null and inventory.shop_id=:shopID");
			$p->bindParam(':shopID',$shopID,PDO::PARAM_STR);
			$p->execute();
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/InventoryInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return null;
		}
	}

	function InsertItem($item)
	{
		try {
			$p = $this->dbh->prepare("insert into `inventory` (`shop_id`,`item_id`,`qty`,`price`,
				`bulk_qty`,`sale`,`condition`,`note`,`remark`,`tier_qty1`,`tier_price1`,`tier_qty2`,
				`tier_price2`,`tier_qty3`,`tier_price3`,`my_cost`,`featured`,`force_quote`,`status`,
				`weight`,`createtime`) values (:shop_id,:item_id,:qty,:price,:bulk_qty,:sale,:condition,
				:note,:remark,:tier_qty1,:tier_price1,:tier_qty2,:tier_price2,:tier_qty3,:tier_price3,
				:my_cost,:featured,:force_quote,:status,:weight,now())");
#			$p->bindValue(':note',null,PDO::PARAM_STR);
#			$p->bindValue(':remark',null,PDO::PARAM_STR);
#			$p->bindValue(':tier_qty1',null,PDO::PARAM_STR);
#			$p->bindValue(':tier_price1',null,PDO::PARAM_STR);
#			$p->bindValue(':tier_qty2',null,PDO::PARAM_STR);
#			$p->bindValue(':tier_price2',null,PDO::PARAM_STR);
#			$p->bindValue(':tier_qty3',null,PDO::PARAM_STR);
#			$p->bindValue(':tier_price3',null,PDO::PARAM_STR);
#			$p->bindValue(':my_cost',null,PDO::PARAM_STR);
			
			$p->execute($item);
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/InventoryInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return null;
		}
	}
}
?>
