#!/usr/bin/php -q
<?php
require_once('autoload.php');
define('APIURL',$DB['APIURL']);
define('S3PATH',$DB['S3PATH']);
define('BACKUPPATH',$DB['BACKUPPATH']);
function tryLock($lock_file)
{
        if(@symlink('/proc/'.getmypid(),$lock_file) !== FALSE)
                return true;
        if(is_link($lock_file) && !is_dir($lock_file))
        {
                unlink($lock_file);
                return tryLock($lock_file);
        }
        return false;
}
function CheckLock($filename)
{
        $lock_file = '/tmp/'.basename($filename).'.lock';
        if(!tryLock($lock_file))
	{
		error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.basename($filename).' is running'."\n",3,"./log/log.txt");
		#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.basename($filename).' is running'."\n");
		exit;
	}
        register_shutdown_function('unlink',$lock_file);
}

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

function LIKintoDB($shop_id,$defaultPrice,$timestamp,$itemInfoDB,$inventoryInfoDB,
	$historyPriceDB,$filename)
{
	$errorArray = array();
	$content = file_get_contents(S3PATH.$filename);
	$lik = json_decode($content);
	$num = 0;
	//determine the lik is object or not
	foreach($lik as $key => $value)
	{
		$item = $itemInfoDB->CheckItemID($value->item_id);
		if($item == null) 
		{
			$errorArray[] = (object)array('ItemID'=>(string)$value->item_id,
					'Qty'=>(string)$value->qty,
					'Price'=>(string)$value->price);
			continue;
		}
		switch($item->item_type)
		{
			case "Sets":
			case "Gears":
				if($value->condition >=1 &&  $value->condition <= 5)
					$condition = $value->condition;
				else
					$condition = 2;
				break;
			default:
				if($value->condition >=2 && $value->condition <= 5)
					$condition = $value->condition;
				else
					$condition = 2;
				break;
		}	
		$newItem = array(
			'shop_id'=>$shop_id,
			'item_id'=>$value->item_id,
			'qty'=> isset($value->qty)?$value->qty:0,
			'price'=>floatval($value->price)>0?$value->price:$defaultPrice,
			'bulk_qty'=>intval($value->bulk_qty)>0?$value->bulk_qty:1,
			'sale'=>$value->sale,
			'condition'=>$condition,
			'note'=>$value->note,
			'remark'=>$value->remark,
			'tier_qty1'=>$value->tier_qty1,
			'tier_price1'=>$value->tier_price1,
			'tier_qty2'=>$value->tier_qty2,
			'tier_price2'=>$value->tier_price2,
			'tier_qty3'=>$value->tier_qty3,
			'tier_price3'=>$value->tier_price3,
			'my_cost'=>$value->my_cost,
			'featured'=>$value->featured,
			'force_quote'=>$value->force_quote,
			'status'=>$value->status,
			'weight'=>isset($value->weight)?$value->weight:0,
		);
		$inventoryInfoDB->InsertItem($newItem);
		unset($newItem);
		$num++;
	}//End of foreach
	return array('successAmount'=>$num,
		'failData'=>$errorArray);
}

function BSXintoDB($shop_id,$defaultPrice,$timestamp,$itemInfoDB,$inventoryInfoDB,
	$historyPriceDB,$filename)
{
	$errorArray = array();
	$xml = simplexml_load_file(S3PATH.$filename);
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
					$errorArray[] = (object)array('ItemID'=>(string)$value->ItemID,
							'Qty'=>(string)$value->Qty,
							'Price'=>(string)$value->Price);
					continue;
			}
			if($legoType == "Parts")
				$item = $itemInfoDB->CheckItemExists($legoType,$value->ItemID,$value->ColorID);
			else
				$item = $itemInfoDB->CheckItemExists($legoType,$value->ItemID);
			if($item == null) 
			{
				$errorArray[] = (object)array('ItemID'=>(string)$value->ItemID,
						'Qty'=>(string)$value->Qty,
						'Price'=>(string)$value->Price);
				continue;
			}
			$condition = (isset($value->Condition) && $value->Condition == 'N')?(string)2:(string)4;
			$history_info = $historyPriceDB->getPrice($item->id,$condition);
			if($history_info == null)
				$defaultPrice = "0.01";
			else
				$defaultPrice = $history_info->price;
			$newItem = array(
				'shop_id'=>$shop_id,
				'item_id'=>$item->id,
				'qty'=> isset($value->Qty)?(string)$value->Qty:(string)0,
				'price'=>floatval($value->Price)>0?(string)$value->Price:$defaultPrice,
				'bulk_qty'=>(isset($value->Bulk) && intval($value->Bulk) > 0)?(string)$value->Bulk:(string)1,
				'sale'=>isset($value->Sale)?(string)$value->Sale:(string)0,
				'condition'=>$condition,
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
			$num++;	
		}//End of foreach
	}//End of If
	return array('successAmount'=>$num,
		'failData'=>$errorArray);
}

function convertFileIntoDB($shopInfoDB,$itemInfoDB,$inventoryInfoDB,
	$historyPriceDB,$filename = null)
{
	$returnArray = array('shopID'=>'',
			'type'=>'import',
			'timestamp'=>'',
			'successAmount'=>0,
			'errorCode'=>0,
			'filename'=>$filename,
			'failData'=>null);
	if(!file_exists(S3PATH.$filename)) 
	{
		$returnArray['errorCode'] = -10;
		WebService::PostWebService(APIURL,$returnArray);
		return;
	}
	preg_match('/^([0-9]*)_([^_]*)_([^\.]*)\.(.*)$/',$filename,$matches);
	if(count($matches) != 5) 
	{
		$returnArray['errorCode'] = -11;
		WebService::PostWebService(APIURL,$returnArray);
		return;
	}
	$timestamp = $matches[1];
	$shop_id = $matches[2];
	$method = $matches[3];
	$filetype = $matches[4];
	$returnArray['timestamp'] = $timestamp;
	$returnArray['shopID'] = $shop_id;
	$shopInfo = $shopInfoDB->GetShopInfo($shop_id);
	if($shopInfo == null) {
		#echo json_encode($returnArray);
		WebService::PostWebService(APIURL,$returnArray);
		return; 
	}
	if($shopInfo->default_price == "avg")
		$defaultPrice = $inventoryInfoDB->GetAvgPrice($shop_id);
	else
		$defaultPrice = $shopInfo->min_default_price;

	if(strtolower($method) == 'clear')
	{
		$inventoryInfoDB->DeleteItem($shop_id);
	}
	if(strtolower($filetype) == 'bsx')
		$msgArray = BSXintoDB($shop_id,$defaultPrice,$timestamp,$itemInfoDB,$inventoryInfoDB,$historyPriceDB,$filename);
	else
		$msgArray = LIKintoDB($shop_id,$defaultPrice,$timestamp,$itemInfoDB,$inventoryInfoDB,$historyPriceDB,$filename);
	if($msgArray['failData'] == null or count($msgArray['failData']) == 0)
		$returnArray['errorCode'] = 0;
	else
		$returnArray['errorCode'] = -1;
	
	$returnArray['successAmount'] = $msgArray['successAmount'];
	$returnArray['failData'] = $msgArray['failData'];
	#echo json_encode($returnArray);
	WebService::PostWebService(APIURL,$returnArray);
	rename(S3PATH.$filename,BACKUPPATH.$filename);	
}

CheckLock($argv[0]);
$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
        array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_PERSISTENT => true));
$files = listdir_by_date(S3PATH);
$ItemInfoDB = new ItemInfo($dbh);
$ShopInfoDB = new ShopInfo($dbh);
$InventoryInfoDB = new InventoryInfo($dbh);
$HistoryPriceDB = new HistoryPrice($dbh);
foreach($files as $file)
{	
	error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$file.' is running'."\n",3,"./log/log.txt");
	#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$file.' is running'."\n");
	convertFileIntoDB($ShopInfoDB,$ItemInfoDB,$InventoryInfoDB,$HistoryPriceDB,basename($file));
	error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$file.' has finished'."\n",3,"./log/log.txt");
	#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$file.' has finished'."\n");
}
unset($HistoryPriceDB);
unset($InventoryInfoDB);
unset($ShopInfoDB);
unset($ItemInfoDB);
