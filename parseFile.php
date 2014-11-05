#!/usr/bin/php -q
<?php
require_once('autoload.php');
function LastModifiedCompare($a, $b)
{
	if(filemtime($a) === filemtime($b)) return 0;
	return filemtime($a) < filemtime($b) ? -1: 1;
}

function listdir_by_date($path)
{
	$dir = opendir($path);
	$files = array();
	while($file = readdir($dir))
	{
		if($file != '.' and $file != '..')
		{
			$files[] = $path.$file;
		}
	}
	closedir($dir);
	usort($files,'LastModifiedCompare');
	return $files;
}

function convertBSXintoLIK($shopInfoDB,$itemInfoDB,$inventoryInfoDB,$filename = null,$shop_id)
{
	if($filename == null) return -1;
	if(!file_exists($filename)) return -2;
	$shopInfo = $shopInfoDB->GetShopInfo($shop_id);
	if($shopInfo == null) return -3;
	if($shopInfo->default_price == "avg")
		$defaultPrice = $inventoryInfoDB->GetAvgPrice($shop_id);
	else
		$defaultPrice = $shopInfo->min_default_price;
	$errorArray = array();
	$xml = simplexml_load_file($filename);
	if($xml->Inventory->Item)
	{
		$num = 0;
		foreach($xml->Inventory->Item as $key=>$value)
		{
			switch($value->ItemTypeID)
			{
				case 'O':
					$legoType = "Boxes";
					break;
				case 'P':
					$legoType = "Parts";
					break;
				case 'I':
					$legoType = "Instructions";
					break;
				case 'S':
					$legoType = "Sets";
					break;
				case 'G':
					$legoType = "Gears";
					break;
				case 'M':
					$legoType = "Minifigs";
					break;
				default;
					$errorArray[] = array('ItemID'=>(string)$value->ItemID,
							'Qty'=>(string)$value->Qty,
							'Price'=>(string)$value->Price);
					continue;
			}
			$item = $itemInfoDB->CheckItemExists($legoType,$value->ItemID);
			if($item == null) 
			{
				$errorArray[] = array('ItemID'=>(string)$value->ItemID,
						'Qty'=>(string)$value->Qty,
						'Price'=>(string)$value->Price);
				continue;
			}
			$newItem = array(
				'shop_id'=>$shop_id,
				'item_id'=>$item->id,
				'qty'=> isset($value->Qty)?(string)$value->Qty:(string)0,
				'price'=>floatval($value->Price)>0?(string)$value->Price:$defaultPrice,
				'bulk_qty'=>(isset($value->Bulk) && intval($value->Bulk) > 0)?(string)$value->Bulk:(string)1,
				'sale'=>isset($value->Sale)?(string)$value->Sale:(string)0,
				'condition'=>(isset($value->Condition) && $value->Condition == 'Y')?(string)1:(string)2,
				'note'=>isset($value->Comments)?(string)$value->Comments:NULL,
				'remark'=>isset($value->Remarks)?(string)$value->Remarks:NULL,
				'tier_qty1'=>isset($value->TQ1)?(string)$value->TQ1:NULL,
				'tier_price1'=>isset($value->TP1)?(string)$value->TP1:NULL,
				'tier_qty2'=>isset($value->TQ2)?(string)$value->TQ2:NULL,
				'tier_price2'=>isset($value->TP2)?(string)$value->TP2:NULL,
				'tier_qty3'=>isset($value->TQ3)?(string)$value->TQ3:NULL,
				'tier_price3'=>isset($value->TP3)?(string)$value->TP3:NULL,
				'my_cost'=>NULL,
				'featured'=>(string)0,
				'force_quote'=>(string)0,
				'status'=>(string)1,
				'weight'=>$item->weight,
			);
			$inventoryInfoDB->InsertItem($newItem);
			break;
			$num++;	
		}//End of foreach
	}//End of If
}

$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
        array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_PERSISTENT => false));
$S3PATH = "/mnt/librick-data/inventory-import/";
$files = listdir_by_date($S3PATH);
$ItemInfoDB = new ItemInfo($dbh);
$ShopInfoDB = new ShopInfo($dbh);
$InventoryInfoDB = new InventoryInfo($dbh);
foreach($files as $file)
{
#	echo convertBSXIntoLIK($ShopInfoDB,$ItemInfoDB,$InventoryInfoDB,'brickstore.bsx','recca');
	$tmpFile = str_replace($S3PATH,"",$file);
	preg_match('/^([0-9]*)_([^_]*)_([^\.]*)\.(.*)$/',$tmpFile,$matches);
	var_dump($matches);
}
unset($InventoryInfoDB);
unset($ShopInfoDB);
unset($ItemInfoDB);
