<?php
/*
    Project: StopForumSpam.SUBNETS.RU
    PHP client

    Versions: 
	0.1.2 (from 06.05.2018): Pull request: Implemented an categorical check email
	0.1.1 (from 12.11.2015): First version
	0.1.0 (from 05.11.2015): First version

    (c) 2015 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>
*/

define( 'StopForumSpam_API_CLIENT_VER','0.1.2' );
define( 'StopForumSpam_API_URL','http://stopforumspam.subnets.ru/api/query.php' );

error_reporting(E_ALL & ~E_STRICT);

$path = realpath( dirname(__FILE__) );
$path_config=$path."/stopforumspam_api.config.php";

if (is_file($path_config)){
    if (!@include $path_config){
	printf("[ERROR]: stopforumspam config file %s not included\n",$path_config);
    }
}else{
    printf("[ERROR]: stopforumspam config file %s not found\n",$path_config);
}

function stopForumSpamApi_check( $stopForumSpamRequestData ){
    //Request for data
    if (!defined('StopForumSpam_HIT_COUNTER')){
	define('StopForumSpam_HIT_COUNTER','0');
    }
    if (!defined('StopForumSpam_EMAIL_CATEGORICAL')){
	define('StopForumSpam_EMAIL_CATEGORICAL','0');
    }

    $stopForumSpamReturnData=0;
    if (!stopForumSpamApi_system_requirements_check()){
	if (isset($stopForumSpamRequestData) && is_array($stopForumSpamRequestData) && count($stopForumSpamRequestData)>0){
	    $stopForumSpamTotalData=array(
		"action"	=>	"check",
		"actionId"	=>	stopForumSpamApi_actionId(),
		"authMethod"	=>	"md5",
		"uid"		=>	StopForumSpam_API_UID,
	    );

	    foreach ($stopForumSpamRequestData as $k=>$v){
		$stopForumSpamTotalData[$k]=$v;
	    }
	    $request=stopForumSpamApi_request( $stopForumSpamTotalData );
	    if ($request[0]==1) {
		//Request is successfull
        	$stopForumSpamReturnData = 0; // default
        	if (isset($request[1]['rows']) && (int)$request[1]['rows'] > 0) {
        	    $stopForumSpamReturnData = (int)$request[1]['rows'];
            	    //если email в базе, то не учитываем StopForumSpam_HIT_COUNTER, а считаем это 100% совпадением
            	    if (StopForumSpam_HIT_COUNTER > 0 && StopForumSpam_EMAIL_CATEGORICAL === 1) {
                	foreach ($request[1]['data']['row'] as $v) {
                    	    if ($v['type'] == 'email') {
                        	$stopForumSpamReturnData = StopForumSpam_HIT_COUNTER;
                        	break;
                    	    }
                	}
            	    }
        	}
    	    } else {
		//Request return error
		stopForumSpam_logg("Request ERROR occur");
	    }
	}
    }
    stopForumSpam_logg(sprintf("Returning data: %s",$stopForumSpamReturnData));
 return $stopForumSpamReturnData;
}

function stopForumSpamApi_import( $stopForumSpamImportData ){
    //Import data
    if (!stopForumSpamApi_system_requirements_check()){
	if (isset($stopForumSpamImportData) && is_array($stopForumSpamImportData) && count($stopForumSpamImportData)>0){
	    $stopForumSpamTotalData=array(
		"action"	=>	"insert",
		"actionId"	=>	stopForumSpamApi_actionId(),
		"authMethod"	=>	"md5",
		"uid"		=>	StopForumSpam_API_UID,
	    );
	    foreach ($stopForumSpamImportData as $k=>$v){
		$stopForumSpamTotalData[$k]=$v;
	    }
	    $request=stopForumSpamApi_request( $stopForumSpamTotalData );

	    if ($request[0]==1){
		//Request is successfull
		stopForumSpam_logg("Inserted");
	    }else{
		//Request return error
		stopForumSpam_logg("Not inserted ERROR occur");
	    }
	}
    }
}

function stopForumSpamApi_request($data){
    if (is_array($data)){
	$tmp=stopForumSpamApi_send_request($data);
	return $tmp;
    }else{
	return array(0=>0,1=>"ERROR: No data for request");
    }
}

function stopForumSpamApi_send_request($data){
	$ret=array();
	/*
	    Request result: 
		ret[0]=0 - unsuccess 
		ret[0]=1 - success
	    Request data: 
		ret[1]="text" - if unsuccess
		$ret[1]= data - success
	*/

	$ret[0]=0;	//Set default
	$ret[1]="";	//Set default
	$err=array();
	
	if ( !defined( 'StopForumSpam_API_UID' ) ){
	    $err[]="API UID not set";
	}
	if ( !defined( 'StopForumSpam_API_PASSWORD' ) ){
	    $err[]="API password not set";
	}
	if ( !defined('StopForumSpam_API_METHOD') ){
	    define( 'StopForumSpam_API_METHOD', 'POST');
	}
	if (StopForumSpam_API_METHOD == "GET" || StopForumSpam_API_METHOD == "POST"){
	    $tmp=stopForumSpamApi_request2data($data);
	}elseif(StopForumSpam_API_METHOD == "JSON"){
	    $tmp=stopForumSpamApi_request2json($data);
	}elseif(StopForumSpam_API_METHOD == "XML"){
	    $tmp=stopForumSpamApi_request2xml($data);
	}else{
	    $err[]="API METHOD unknown";
	}

	if ( !$tmp ){
	    $err[]="DATA generation error";
	}

	if ( !defined( 'StopForumSpam_API_URL' ) ){
	    $err[]="API URL unknown";
	}

	stopForumSpam_logg("REQUEST: ".var_export($tmp,true));
	if (count($err)>0){
	    $ret[1]=implode(";",$err);
	    stopForumSpam_logg("ERROR: $ret[1]");
	    return $ret;
	}
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, StopForumSpam_API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, StopForumSpam_API_TIMEOUT);
	if (StopForumSpam_API_METHOD == "XML"){
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-length: ".strlen($tmp),'Content-Type: application/xml'));
	}elseif (StopForumSpam_API_METHOD == "JSON"){
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-length: ".strlen($tmp),'Content-Type: application/json'));
	}else{
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-length: ".strlen($tmp),'Content-Type: text/html'));
	}
	mb_internal_encoding('UTF-8');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $tmp);
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, sprintf("StopForumSpam API CLIENT v%s",StopForumSpam_API_CLIENT_VER));

	if ( StopForumSpam_API_DEBUG_CURL == 1 ){
	    curl_setopt( $ch, CURLOPT_STDERR, StopForumSpam_API_LOG );
	    curl_setopt( $ch, CURLOPT_VERBOSE, true );
	}

	$strAnswer = curl_exec($ch);
	//stopForumSpam_logg("ORIG REPLY: ".$strAnswer);

	if ( StopForumSpam_API_DEBUG_CURL == 1){
	    stopForumSpam_logg(curl_getinfo($ch));
	}

	if ( curl_errno( $ch ) ){
		$ret[1]=sprintf("API connection error: %s",curl_error( $ch ));
		return $ret;
	}else{
		curl_close( $ch );
		unset( $res );
		if (StopForumSpam_API_METHOD == "XML"){
		    $res=stopForumSpamApi_unserialize_xml($strAnswer);
		}elseif (StopForumSpam_API_METHOD == "JSON"){
		    $res=json_decode($strAnswer,true);
		}else{
		    $ptmp=explode(";",$strAnswer);
		    if (count($ptmp)>0){
			foreach ($ptmp as $val){
			    if (preg_match("/^(\S+)=(\S+)/",$val,$mtmp)){
				$res[urldecode($mtmp[1])]=urldecode($mtmp[2]);
			    }
			}
		    }else{
			$res['error']="API reply was emply";
		    }
		}

		if (StopForumSpam_API_DEBUG == 1){
		    stopForumSpam_logg("REPLY: ".var_export($res,true));
		}

		if ( $res['error'] > 0 ){
		    $ret[1]=sprintf("Code: %s Description: %s",isset($res['error']) ? $res['error'] : "unknown" , isset($res['errorDescription']) ? $res['errorDescription'] : "" );
		}else{
		    $validate=stopForumSpamApi_validate_response($res);
		    if ($validate){
			unset( $res['authMethod'], $res['error'] , $res['errorDescription'], $res['sign'] );
			if ( isset($res['rows']) ){
			    if ( $res['rows'] == 1){
				if ( isset($res['data']['row']) ){
				    $tmp=$res['data']['row'];
				    unset( $res['data']['row'] );
				    $res['data']['row']=array();
				    $res['data']['row'][0]=$tmp;
				}
			    }
			}
			$ret[0]=1;
			$ret[1]=$res;
		    }else{
			$ret[1]="Code: 800 Description: API response not valid";
		    }
		    stopForumSpam_logg("FINAL:");
		    stopForumSpam_logg($ret);
		    return $ret;
		}
	}
 return $ret;
}

function stopForumSpamApi_request2data($data){
	$res="";
	ksort( $data );
	$sing_vals=array();
	$data_len=count($data);
	$nn=1;
	foreach($data as $key => $val){
		$sing_vals[] = $val;
		$res .= sprintf("%s=%s%s",$key,$val,$data_len!=$nn?"&":"");
		$nn++;
	}
	$sign=sprintf("%s;%s",implode(";",$sing_vals),StopForumSpam_API_PASSWORD);
	$res.=sprintf("&sign=%s",md5($sign));
 return $res;
}

function stopForumSpamApi_request2json($data){
	$res="";
	ksort( $data );
	$sing_vals=array();
	$data_len=count($data);
	$nn=1;
	foreach($data as $key => $val){
		$sing_vals[] = $val;
		$nn++;
	}
	$sign=sprintf("%s;%s",implode(";",$sing_vals),StopForumSpam_API_PASSWORD);
	$data['sign']=md5($sign);
	$res=json_encode($data);
 return $res;
}

function stopForumSpamApi_request2xml($data){
	if(array_key_exists('XML', $data)){
		$res = $data['XML'];
	}else{
		$res = '<?xml version="1.0" encoding="UTF-8"?><request>'; 
	}
	
	ksort( $data );
	$sing_vals=array();
	foreach($data as $key => $val){
		$sing_vals[] = $val;
		$res .=sprintf("<%s>%s</%s>",$key,$val,$key);
	}

	$sign=sprintf("%s;%s",implode(";",$sing_vals),StopForumSpam_API_PASSWORD);
	$res.=sprintf("<sign>%s</sign>",md5($sign));

	$res .= '</request>';
 return $res;
}

function stopForumSpamApi_validate_response($data){
    $ret=0;
    if ( is_array($data) ){
	if ( isset($data['authMethod']) && $data['authMethod']=='NULL' ){
	    $ret=1;
	}elseif ( isset($data['authMethod']) && $data['authMethod']=='md5' ){
	    if ( isset($data['sign']) ){
		$server_sign=$data['sign'];
		unset( $data['sign'] , $tmp);
		if ( isset($data['data']) ){
		    $tmp = $data['data'];
		    unset( $data['data'] );
		}
		ksort( $data );
		$for_sign = array( implode( ";", $data ) );
		if( isset( $tmp ) && is_array( $tmp ) && count( $tmp ) ){
		    $data_for_sign = '';
		    if( isset($tmp['row']) ){
			if ( $data['rows'] == 1 ){
			    $rows[0]=$tmp['row'];
			}else{
			    $rows=$tmp['row'];
			}
			foreach( $rows as $k => $v ){
				ksort( $rows[$k] );
				$data_for_sign .= sprintf( "%s%s", $data_for_sign ? ";" : "", implode( ";", $rows[$k] ) );
			}
			if( $data_for_sign ){
			    $for_sign[] = $data_for_sign;
			}
		    }
		}else{
			unset( $tmp );
		}
		$for_sign[] = StopForumSpam_API_PASSWORD;
		$my_sign = md5( implode( ";", $for_sign ) );
		if( $server_sign === $my_sign ){
		    $ret=1;
		}
	    }
	}
    }
 return $ret;
}

function stopForumSpamApi_unserialize_xml($input, $callback = null, $recurse = false){
        if ((!$recurse) && is_string($input)){
	    $pre_data=preg_replace('/&/', '&amp;', $input);
    	    if( ( $result = @simplexml_load_string($pre_data) ) === false ){
    		$ret=array();
    		$ret['error'] = 800;
		$ret['errorDescription'] = 'CLIENT: Error during parse of XML';
		return $ret;
    	    }
        }else{
    	    $result=$input;
        }
        if ($result instanceof SimpleXMLElement){ 
	    if (count((array)$result)>0){
    		$result = (array) $result;
    	    }
    	}
        if (is_array($result)) foreach ($result as &$item) $item = stopForumSpamApi_unserialize_xml($item, $callback, true);
 return (!is_array($result) && is_callable($callback))? call_user_func($callback, $result): $result;
}

function stopForumSpamApi_actionId(){
    $mtime=explode(".",microtime(true));
    return sprintf("%s%02d",date("His",time()),isset($mtime[1])?$mtime[1]:"0");
}


function stopForumSpamApi_replace_html($text){
    $text=preg_replace("/\</","&lt;",$text);
    $text=preg_replace("/\>/","&gt;",$text);
 return $text;
}

function stopForumSpam_logg( $text ){
    if( StopForumSpam_API_DEBUG == 1){
	if (defined('StopForumSpam_API_LOG')){
	    if( is_resource( StopForumSpam_API_LOG ) ){
		$debug_string=sprintf( "[%s]: %s\n", date( "d.m.Y H:i:s", time( ) ), is_array($text) ? print_r($text,true) : $text );
		fputs( StopForumSpam_API_LOG, $debug_string );
	    }
	}
    }
}

function stopForumSpamApi_system_requirements_check(){
    $ret=0;
    if ( StopForumSpam_API_DEBUG == 1 || StopForumSpam_API_DEBUG_CURL == 1){
	if (defined('StopForumSpam_API_LOGFILEDIR')){
	    if (is_dir(StopForumSpam_API_LOGFILEDIR) && is_writable(StopForumSpam_API_LOGFILEDIR)){
		if (!defined('StopForumSpam_API_LOGFILE')){
		    define('StopForumSpam_API_LOGFILE', sprintf("%s/%s_stopforumspam_api.log",StopForumSpam_API_LOGFILEDIR,date("Y.m.d",time())));
		    define( 'StopForumSpam_API_LOG', @fopen( StopForumSpam_API_LOGFILE, 'a+' ) );
		}
	    }
	}
    }

    if ( !function_exists('curl_exec') ){
	print "API: <a href=\"http://www.php.net/manual/ru/book.curl.php\">CURL</a> not found...";
	$ret=1;
    }

    if ( !function_exists('mb_internal_encoding') ){
	print "API: <a href=\"http://php.net/manual/ru/book.mbstring.php\">Multibyte String</a> not found...";
	$ret=1;
    }

    if (defined('StopForumSpam_API_METHOD') && StopForumSpam_API_METHOD == "XML"){
	if ( !function_exists('simplexml_load_string') ){
	    print "API: <a href=\"http://php.net/manual/ru/function.simplexml-load-string.php\">XML</a> not found...";
	    $ret=1;
	}
    }

    if (!defined('StopForumSpam_API_UID') || !defined('StopForumSpam_API_PASSWORD')){
	print "stopforumspam API: No UID or password, check config... ";
	$ret=1;
    }
 return $ret;
}

?>