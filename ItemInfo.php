<?php
class ItemInfo {
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
	
	function CheckItemID($item_id)
	{
		try {
			$p = $this->dbh->prepare("select * from item_info where id=:id");
			$p->bindParam(':id',$item_id,PDO::PARAM_STR);
			$p->execute();
			if($p->rowCount() == 0)
				return null;
			return $p->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return null;
		}
	}

	function CheckItemExists($itemType,$brickLinkId,$colorID=null)
	{
		try {
			$p = $this->dbh->prepare("select * from item_info where item_type=:item_type and bricklink=:bricklink");
			$p->bindParam(':item_type',$itemType,PDO::PARAM_STR);
			$p->bindParam(':bricklink',$brickLinkId,PDO::PARAM_STR);
			$p->execute();
			if($p->rowCount() == 0)
				return null;
			if($colorID != null)
			{
				$resData = $p->fetch(PDO::FETCH_OBJ);
				$librick_id = $resData->linker ."_". $colorID;
				$p2 = $this->dbh->prepare("select * from item_info where id=:id");
				$p2->bindParam(':id',$librick_id);
				$p2->execute();
				if($p2->rowCount() == 0)
					return null;
				return $p2->fetch(PDO::FETCH_OBJ);
			}
			else
				return $p->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n");
			return null;
		}
	}
}
?>
