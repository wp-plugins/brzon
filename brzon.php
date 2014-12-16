	<?php
	/*
	Plugin Name: BRzon Plugin for Wordpress
	Description: Adicione Produtos da Amazon.com.br ao seu site.
	Version: 0.8
	Date: December 10th, 2014
	Author: Revokee
	/*
	Licence: GNU General Public License v3.0
	More info: http://www.gnu.org/copyleft/gpl.html
	*/

	function truncate($string, $limit, $break=".", $pad="") { 
	    if(strlen($string) <= $limit) return $string;
	    if (false !== ($breakpoint = strpos($string, $break, $limit))) {
	      if($breakpoint < strlen($string) - 1) {
		    $string = substr($string, 0, $breakpoint) . $pad;
		  }
	    }
	    return $string;
	  }

	function brzon_shortcode( $atts ) {
	 extract(shortcode_atts(array('keywords' => '', 'sindex' => '', 'snode' => '', 'listing' => '', 'sort' => '', 'page' => '',  'country' => 'com', 'spec' => '0', 'asin' => '', 'col' => '1', 'descr' => '0','aval' => '0','stitle' => '1',), $atts));
	add_option( 'az_public', '' );
	add_option( 'az_secret', '' );
	add_option( 'az_atagcom', '' );
	add_option( 'az_col', '' ); 
	add_option( 'az_pricecol', '' ); 
	add_option( 'az_titlecol', '');
	add_option('az_availability','');

	$publickey = get_option('az_public');
	$secretkey = get_option('az_secret');
	$atagcom = get_option('az_atagcom');
	$pricecol = get_option('az_pricecol');
	$titlecol = get_option('az_titlecol');
	$availability = get_option('az_availability');

	if ($country == "com" ){ $atag = $atagcom; }

	if ($pricecol == "") {$pricecol = "CD2323";}
	else {$pricecol = $pricecol;}

	if ($titlecol == "") {$titlecol = "41A62A";}
	else {$titlecol=$titlecol;}


	if ($spec == "0") {
		$time = gmdate("Y-m-d\TH:i:s\Z");
		$uri = 'Operation=ItemSearch&Condition=All&Availability=Available&ResponseGroup=Large,EditorialReview,ItemAttributes,OfferFull,Offers&Version=2011-08-01';
		$uri .= "&Keywords=" . urlencode($keywords);
		$uri .= "&SearchIndex=$sindex";
		if ($snode){
			$uri .= "&BrowseNode=$snode";
		}
		if($sort){
			$uri .= "&Sort=$sort";
		}
		if ($page){
			$uri .= "&ItemPage=$page";
		}
		$uri .= "&AWSAccessKeyId=$publickey";
		$uri .= "&AssociateTag=$atag";
		$uri .= "&Timestamp=$time";
		$uri .= "&Service=AWSECommerceService";
		$uri = str_replace(',','%2C', $uri);
		$uri = str_replace(':','%3A', $uri);
		$uri = str_replace('*','%2A', $uri);
		$uri = str_replace('~','%7E', $uri);
		$uri = str_replace('+','%20', $uri);
		$sign = explode('&',$uri);sort($sign);$host = implode("&", $sign);
		if ($country == "jp") {$host = "GET\necs.amazonaws.".$country.".br"."\n/onca/xml\n".$host;}
		else {$host = "GET\nwebservices.amazon.".$country.".br"."\n/onca/xml\n".$host;}
		$signed = urlencode(base64_encode(hash_hmac("sha256", $host, $secretkey, True)));
		$uri .= "&Signature=$signed";
		if ($country == "jp") {$uri = "http://ecs.amazonaws.".$country."/onca/xml?".$uri;}
		else {$uri = "http://webservices.amazon.".$country.".br"."/onca/xml?".$uri;}
	}

	elseif ($spec == "1") {
		$time = gmdate("Y-m-d\TH:i:s\Z");
		$uri = 'Operation=ItemLookup&Condition=All&Availability=Available&ResponseGroup=Large,EditorialReview,ItemAttributes,OfferFull,Offers&Version=2011-08-01';
		$uri .= "&ItemId=$asin";
		$uri .= "&AWSAccessKeyId=$publickey";
		$uri .= "&AssociateTag=$atag";
		$uri .= "&Timestamp=$time";
		$uri .= "&Service=AWSECommerceService";
		$uri = str_replace(',','%2C', $uri);
		$uri = str_replace(':','%3A', $uri);
		$uri = str_replace('*','%2A', $uri);
		$uri = str_replace('~','%7E', $uri);
		$uri = str_replace('+','%20', $uri);
		$sign = explode('&',$uri);sort($sign);$host = implode("&", $sign);
		if ($country == "jp") {$host = "GET\necs.amazonaws.".$country.".br"."\n/onca/xml\n".$host;}
		else {$host = "GET\nwebservices.amazon.".$country.".br"."\n/onca/xml\n".$host;}
		$signed = urlencode(base64_encode(hash_hmac("sha256", $host, $secretkey, True)));
		$uri .= "&Signature=$signed";
		if ($country == "jp") {$uri = "http://ecs.amazonaws.".$country."/onca/xml?".$uri;}
		else {$uri = "http://webservices.amazon.".$country.".br"."/onca/xml?".$uri;}
	}

	/*error_log(print_r($uri, TRUE));*/ 

	$ch = curl_init($uri); 
	curl_setopt($ch, CURLOPT_HEADER, false); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	$xml = curl_exec($ch); 
	curl_close($ch); 

	$pxml = simplexml_load_string($xml); 

	$breaklist=0;
	$all = &$pxml->Items->Item;
	$param = array();
	for($count = count($all)-1; $count >= 0; --$count) { 
	$param[(string)$all[$count]->ItemAttributes->Title] = &$all[$count];}

	foreach ($pxml->Items->Item as $item){

	$link = $item->DetailPageURL;
	$sprice = $item->Offers->Offer->OfferListing->Price->FormattedPrice;
	$sprice = str_replace("BRL","R$ ",$sprice);
	$lprice = $item->ItemAttributes->ListPrice->FormattedPrice;
	$lprice = str_replace("BRL","R$ ",$lprice);


	$avab =$item->Offers->Offer->OfferListing->Availability;
	$img = $item->MediumImage->URL;


	if ($country == "com" && $sprice == "")  {$seed = "Ver mais >>"; $sprice = "Preço"; } elseif ($country == "com" && $sprice == $sprice) {$sprice=$sprice; $seed = "Ver mais >>";}


	$title = $item->ItemAttributes->Title;
	$title = preg_replace('`\[[^\]]*\]`','',$title);
	$title = preg_replace('`\([^\]]*\)`','',$title);
	$title = str_replace("\"","",$title);
	$title = truncate($title, 25, " ");

	$titlel = $item->ItemAttributes->Title;
	$titlel = preg_replace('`\[[^\]]*\]`','',$titlel);
	$titlel = preg_replace('`\([^\]]*\)`','',$titlel);
	$titlel = str_replace("\"","",$titlel);
	$titlel = truncate($titlel, 60, " ");


	$desc = $item->EditorialReviews->EditorialReview->Content ;
	$desc = preg_replace("/<img[^>]+\>/i", " ", $desc); 
	$desc = strip_tags($desc);
	$desc = substr($desc, 0, 270);

	if ($desc == "") {$desc = "";} else { $desc = $desc . "...";}
	$avab = truncate($avab, 69, " ");


	if ($img == "") {
		$content .= '';
	}
	else{
		if ($col == "1") {
			if ($descr == "1") {
				if($aval=="1") {
					if($stitle=="1"){
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;">'.$titlel.'</a><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span><strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.' </span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
					else{
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span><strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.' </span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
				}
				else{
					if($stitle=="1"){
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;">'.$titlel.'</a><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span><strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.' </span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
					else{
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span><strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.' </span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
				}
			}
			else {
				if($aval=="1") {
					if($stitle=="1"){
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;color: #'.$titlecol.';">'.$titlel.'</a><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span> <strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.'</span></strike><br><div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
					else{
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span> <strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.'</span></strike><br><div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
				}
				else{
					if($stitle=="1"){
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;color: #'.$titlecol.';">'.$titlel.'</a><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span> <strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.'</span></strike><br><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					}
					else{
						$content .= '<div style="width:100%">
						<div style="float:left;width:40%;">
						<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'" style="margin:0;padding:0;float:left;border:none;" /></a></div>
						<div style="float:left;margin:0px 50px 90px 0;width:65%;"><br><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.' </span> <strike style="color:#444;"><span style="font-size:13px;text-decoration:none;font-weight:500;">'.$lprice.'</span></strike><br><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
					
					}
				}
			}
		}
		elseif ($col == "2") {
			if ($descr == "1") {
				if($aval=="1") {
					if($stitle=="1"){
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
				else{
					if($stitle=="1"){
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
			}
			else {
				if($aval=="1") {
					if($stitle=="1"){
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><div style="font-size:12px;clear:both;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
				else{
					if($stitle=="1"){
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if(($i % 2)==0){
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:4%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else {
							$content .= '<div style="margin-bottom:140px;float:left;width:48%;margin-right:0%;">
							<div style="width:250px;height:170px;" >
							<a href="'.$link.'" rel="nofollow" target="_blank"><img src="'.$img.'"  style="margin:0;padding:0 0 20px 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both"><span style="color: #'.$pricecol.';font-size:15px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:13px;">'.$lprice.'</span></strike><br><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
			}
			$i++;
			if(($i % 2)==0){
				$content .= '<div style="clear:both"></div>';
			}
		}
		elseif ($col == "3") {
			if ($descr == "1") {
				if($aval=="1") {
					if($stitle=="1"){
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					} 
					else{
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
				else{
					if($stitle=="1"){
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><br>'.$desc.'<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
			}
			else {
				if($aval=="1"){
					if($stitle=="1"){
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><div style="font-size:12px;clear:both;color:#777;">'.$avab.'</div><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
				else{
					if($stitle=="1"){
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;float:left;color: #'.$titlecol.';">'.$title.'</a>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
					else{
						if (($i % 3)==0) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						elseif (($i % 3)==1) {
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:6%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
						else{
							$content .= '<div style="margin-bottom:130px;float:left;width:29%;margin-right:0%;">
							<div style="width:200px;height:170px;" >
							<a href="'.$link.'" rel="nofollow"><img src="'.$img.'"  style="margin:0;padding:0px 0 0 30px;border:none;" /></a></div>
							<div style="float:left;margin:3px 0 0px 0;clear:both;"><span style="color: #'.$pricecol.';font-size:14px;text-decoration:none;font-weight:600;"> '.$sprice.'</span>  <strike style="color:#444;"><span style="font-size:12px;font-weight:500;;">'.$lprice.'</span></strike><a href="'.$link.'" rel="nofollow" style="text-decoration:none;font-weight:600;"><br><img src="http://g-ec2.images-amazon.com/images/G/32//associates-buy-amazon._V340566359_.png"></a></div></div>';
						}
					}
				}
			}
			$i++;		
			if(($i % 3) == 0 ){
				$content .= '<div style="clear:both"></div>';
			}
		}
		$breaklist++; 
		if ($breaklist >=$listing){
			break;
		}	
	}
	}
	return $content;
	}

	function add_brzon_panel() {
	if (function_exists('add_options_page')) {
	add_options_page('brzon', 'brzon', 8, 'brzon', 'brzon_admin_panel');
	}
	}

	function brzon_admin_panel() { if ($_POST["az_\165pda\x74e\144"]){
	update_option('az_public',$_POST['az_public']); 
	update_option('az_secret',$_POST['az_secret']); 
	update_option('az_atagcom',$_POST['az_atagcom']); 
	update_option('az_pricecol', $_POST['az_pricecol']); 
	update_option('az_titlecol', $_POST['az_titlecol']); 
	update_option('az_availability', $_POST['az_availability']);


	echo '<div id="message" style="padding:2px 2px 2px 4px; font-size:12px;" class="updated"><strong>' . Atualizado . '</strong></div>';}?>
	<div class="wrap">
		<div style="width:99%; height:800px;">
			<div style="float:left;width:65%;margin-right:4%;">
				<h3 style="color:#0066cc;">Opções do Plugin</h3>	
				<form method="post" id="cj_options">
					<table cellspacing="10" cellpadding="5" > 
						<tr valign="top">
							<td width="17%">
								<strong>AWS Access Key</strong>
							</td>
							<td>
								<input type="text" name="az_public" id="az_public" value="<?php echo get_option('az_public');?>" maxlength="300" style="width:400px;" />
							</td>
						</tr>					
						<tr valign="top">
							<td>
								<strong>AWS Secret Access Key</strong>
							</td>
							<td>
								<input type="text" name="az_secret" id="az_secret" value="<?php echo get_option('az_secret');?>" maxlength="300" style="width:400px;" />
								<p>Para obter seu "AWS Access Key" e o "AWS Secret Access Key" siga os passos a seguir: 
								<p>1. Vá até o site da <a href="http://aws.amazon.com" target="_blank">AWS</a> e faça o login.</p> 
								<p>2. No topo superior direito da página, após ter feito o login, clique no seu nome e vá até "Security Credentials".</p> 
								<p>3. Clique então em Users, e crie um novo usuário.</p>
								<p> 4. Ao criar um novo usuário, certifique-se que a opção "Generate an Access Key for each user" está marcada. Não é necessário criar mais que um usuário.</p>
								<p> 5. Clique em "Create". Na página seguinte serão mostradas seu Access Key ID e seu Secret Access Key. Anote ambos, você irá precisar deles. Copie aqui estes valores.
								<p><font style="color:#D3133E;">Certifique-se que você copiou espaços adicionais depois das credenciais.</font>
							</td>
						</tr>
						<tr valign="top">
							<td>
								<strong>ID do Associado<font style="font-size:10px; color:#555;"></font></strong>
							</td>
							<td>
								<input type="text" name="az_atagcom" id="az_atagcom" value="<?php echo get_option('az_atagcom');?>" maxlength="40"/>
							</td>
						</tr>
						<tr valign="top">
							<td>
								<strong>Cor do Preço</strong>
							</td>
							<td>
								<input type="text" name="az_pricecol" id="az_pricecol" value="<?php echo get_option('az_pricecol');?>" maxlength="6" style="width:100px;" />
							</td>
						</tr>
						<tr valign="top">
							<td>
								<strong>Cor do Título</strong>
							</td>
							<td>
								<input type="text" name="az_titlecol" id="az_titlecol" value="<?php echo get_option('az_titlecol');?>" maxlength="6" style="width:100px;" />
								<p>Coloque um código de cor de 6 caracteres. Por exemplo <strong style="color:#FFA500">FFA500</strong> para laranja ou  <strong>000000</strong> para preto. Você pode encontrar códigos de cores <a href="http://quackit.com/html/html_color_codes.cfm" target="_blank">aqui</a>.</p>
								<p><font style="color:#D3133E;">Se os campos de cores estiverem vazios, a cor padrão do preço é vermelho e o título é definido como verde.</font></br></p>
							</td>
						</tr>
						<tr valign="top">
							<td>
								<p class="submit"><input type="submit" name="az_updated" value="Atualizar Valores &raquo;" /></p>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>	
				
		<div style="padding:0px 15px 15px 15px; margin:10px 0 0 0;border:3px solid #ccc;width:90%;">
			<h3 style="color:#0066cc;">Como usar o BRzon</h3>
			<h4>Antes de tudo lembre-se, você deve inserir o código na aba HTML da sua página.</h4>
			<font style="font-size: 18px; color:brown;font-weight:bold;text-decoration:underline;">1. Mostre produtos baseados em palavras-chaves</font>
			<p style="margin:30px 0 0 0;">Formato do código: <strong>[brzon keywords="harry potter" sindex="Books" snode="6740748011" sort="relevancerank" listing="10"]</strong></p>
			<ul style="list-style:square;padding: 0 0 10px 30px;"><li><p style="margin:20px 0 0 0;"><strong>keywords</strong> <font style="color:red;font-size:12px; font-weight:bold;">(*necessário)</font> = A palavra chave que você deseja usar. Não use caracteres especiais como &, @ etc.</li>
				<li style="margin:10px 0 0 0;"><strong>sindex</strong> <font style="color:red;font-size:12px; font-weight:bold;">(*necessário)</font> = Categoria da Amazon em Inglês.</li>
				<li style="margin:10px 0 0 0;"><strong>snode</strong> <font style="color:#111;font-size:12px; font-weight:bold;">(*opcional)</font> = Subcategoria da Amazon (também conhecido como BrowseNode). Para encontrar o Browse node da subcategoria basta navegar pelo site da Amazon</li>
				<li style="margin:10px 0 0 0;"><strong>sort</strong> <font style="color:#111;font-size:12px; font-weight:bold;">(*opcional)</font> = Ordene os produtos por preço, mais vendidos e reviews. Para os valores que podem ser usados vá em <a href="http://docs.amazonwebservices.com/AWSECommerceService/2011-08-01/DG/APPNDX_SortValuesArticle.html" target="_blank">Amazon Docs</a></li>
				<li style="margin:10px 0 0 0;"><strong>listing</strong> <font style="color:#111;font-size:12px; font-weight:bold;">(*opcional)</font> = Quantidade de produtos que você deseja mostrar de 1 a 10. A API da Amazon Api retorna até 10 produtos por vez.</li>
				<li style="margin:10px 0 0 0;"><strong>descr</strong>  <font style="color:#111;font-size:12px; font-weight:bold;">(*opcional)</font> = Mostrar ou não a descrição do produto. Coloque em "1" para mostrar a descrição. O padrão é não mostrar a descrição.</li>
				<li style="margin:10px 0 0 0;"><strong>aval</strong> <font style="color:#111;font-size:12px; font-weight:bold;">(*opcional)</font>= Mostra a disponibilidade do produto. Coloque em "1" para mostrar a disponibilidade. O padrão é não mostrar.</li>
				<li style="margin:10px 0 0 0;"><strong>stitle</strong> <font style="color:#111;font-size:12px; font-weight:bold;">(*opcional)</font>= Mostra o título do produto. Coloque em "0" para <strong>NÃO</strong> para mostrar o título. O padrão é mostrar.</li>
			</ul>

			<br style="margin:20px 0 30px 0;"><font style="font-size: 18px; color:brown;font-weight:bold;text-decoration:underline;margin:10px 0 30px 0;">2. Mostrar produtos baseados no ASIN</font>
			<br style="margin:30px 0 30px 0;">Se você desejar mostrar os produtos baseados em ASIN. Nesse caso o código sera algo como: <strong>[brzon  spec="1" asin="B00006ISG6,B0000717AU" listing="2"]</strong>

			<ul style="list-style:square;padding: 0 0 10px 30px;">
				<li style="margin:10px 0 0 0;"><p style="margin:20px 0 0 0;"><strong>spec</strong> <font style="color:red;font-size:12px; font-weight:bold;">(*necessário)</font> = Deve ser mantido em "1"</li>
				<li style="margin:10px 0 0 0;"><strong>asin</strong> <font style="color:red;font-size:12px; font-weight:bold;">(*necessário)</font> = O ASIN do produto. Você encontra o ASIN de um produto em sua página de Detalhes. No caso de livros, utilize o ISBN. Você pode adicionar até 10 produtos, separados por vírgula e sem espaços entre eles. </li>
				<li style="margin:10px 0 0 0;"><strong>listing</strong> <font style="color:red;font-size:12px; font-weight:bold;">(*necessário)</font> = O número de produtos que você deseja mostrar. O máximo de produtos por chamada é 10.</li>
				<li style="margin:10px 0 0 0;"><font style="color:#111 !important;font-size:12px; font-weight:bold;">Todas as outras opções já listadas também podem ser usadas.</font></li>
			</ul>
		</div>
	</div>
	<?php
	}
	add_shortcode('brzon', 'brzon_shortcode');
	add_action('admin_menu', 'add_brzon_panel'); ?>