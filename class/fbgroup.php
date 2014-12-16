<?php
use Facebook\FacebookRequest;
use Facebook\FacebookSession;

Class FB_Group{
	const FB_APPID = 'APP ID';
	const FB_APPSECRET = 'APP SECRET';
	const FB_GROUPID = 'GROUP ID'; 

	const CACHE_DIR = 'dat/';
	const CACHE_USER_LIST = 'userlists.dat';
	const CACHE_DEVELOPER_TOKEN = 'token.dat';
	
	/* 
	   When GROUP_PRIVATE is true, mean the group needs 'user_group' premission to retrive feeds
	   BUT Facebook not allow to do that, so alternative using developer access token to retrive feeds
	*/
	const GROUP_PRIVATE = FALSE;	
	const FB_DEVELOPERID = 'DEVELOPER ID';

	private $debug = array();
	private $error = array();	
	private $session;
	private $userInfo = null;
	private $default_ret = array('error'=>false, 'data'=>array());

	function __construct($session) {
    	if( $session ){
    		$this->session = $session;
    	}else{
    		$error = 'Need Facebook Session to operate!';
   			throw new Exception($error);
    	}
    }

    private function saveCache($filename, $data, $plain=false){
    	if( !$plain ){
    		$data = json_encode($data);
    	}
    	try{
    		file_put_contents($filename, $data);
    	}catch(Exception $ex){
    		$this->error[] = array('saveCacheError', $ex->getMessage());
    		return false;
    	}
    	return true;
    	
    }
    private function loadCache($filename, $plain=false){
    	try{
    		$data = file_get_contents($filename, $data);
    	}catch(Exception $ex){
    		$this->error[] = array('loadCacheError', $ex->getMessage());
    		return false;
    	}
    	if( !$plain ){
    		$data = json_decode($data, true);
    	}
    	return $data;
    }

    public function getDebug(){
    	return $this->debug;
    }
    private function setDebug($name, $data){
    	if( is_array($data) ){
    		$debug = $data;
    		$debug['type'] = $name;
    	}else{
    		$debug['type'] = $data;
    	}
    	$this->debug[] = $debug;
    }
    public function getError(){
    	return $this->error;
    }
    private function setError($name, $data){
    	$this->error[] = array($name, $data);
    	$ret = $this->default_ret;
    	$ret['error'] = true;
    	return $ret;
    }
    private function setRet($data=null){
    	$ret = $this->default_ret;
    	if( !is_null($data) ){
    		$ret['data'] = $data;
    	}    	
    	return $ret;
    }
	
	private function getGraphResponse($requestUrl, $user=false){
		$ret = $this->default_ret;
		$session = $this->getSession($user);
		if( !$session ){
			$ret['error'] = true;
			return $ret;
		}
		try{
			$time_start = microtime(true);
			$request = new FacebookRequest($session, 'GET', $requestUrl);
			$response = $request->execute();
			$ret['data'] = json_decode($response->getRawResponse(), true);
		}catch(FacebookRequestException $ex) {
			$ret['error'] = true;
			$this->setError('FacebookRequestException', $ex->getMessage());
		}catch (\Exception $ex) {
			$ret['error'] = true;
			$this->setError('getGraphResponseException', $ex->getMessage());
		}finally{
			$e = new Exception();
			$debug['callFunc'] = $e->getTrace()[1];
			$debug['execTime'] = round( microtime(true) - $time_start, 3);
			$debug['arg'] = $requestUrl;
			$this->setDebug('getGraphResponse', $debug);
		}
		return $ret;
	}
	
	public function getUserInfo(){
		if( is_null($this->userInfo) ){
			$requestUrl = '/me';
			$res = $this->getGraphResponse($requestUrl,true);
			if( !$res['error'] ){
				$this->userInfo = $res['data'];
				return $res['data'];
			}else{
				return false;
			}
		}
		return $this->userInfo;
	}
	
	public function getCacheUpdateTime($filename){
		$cache_filename = $this::CACHE_DIR.$filename;
		if( file_exists($cache_filename) ){
			return date('Y-m-d H:i:s', filectime($cache_filename) );
		}else{
			return false;
		}
	}

	private function getSession($user=false){
		//$user = true : force to get Client Facebook Session
		if( !$user && $this::GROUP_PRIVATE && $this::FB_DEVELOPERID ){			
			return $this->getLongTermAccessToken();
		}else{
			return $this->session;
		}
	}
    private function getLongTermAccessToken(){
    	//using devloper account to retrive feed
		$userID = $this->getUserInfo()['id'];
    	$dat_filename = $this::CACHE_DIR.$this::CACHE_DEVELOPER_TOKEN;
    	$refreshToken = function() use ($dat_filename) {
    		$token = (string)$this->session->getAccessToken()->extend();
			$developerSession = new FacebookSession( $token );			
			$this->saveCache($dat_filename, $token, true);
			return $developerSession;
    	};
    	if( file_exists($dat_filename) ){
    		$token = $this->loadCache($dat_filename, true);
    		$developerSession = new FacebookSession( $token );
    		try{
    			$developerSession->validate();    			
    		}catch(Exception $e){
    			if( $userID == $this::FB_DEVELOPERID ){
    				$developerSession = $refreshToken();
    			}else{
    				return false;
    			}
    		}
    	}else{
    		if( $userID == $this::FB_DEVELOPERID ){
				$developerSession = $refreshToken();
			}else{
				return false;
			}
    	}
    	return $developerSession;
    }

	public function checkisMember(){
		$userID = $this->getUserInfo()['id'];	
		$res = $this->getCacheGroupUsers();
		if($res['error']){
			$this->setError('checkisMember', 'getCacheGroupUsers Error');
			return false;
		}else{
			$userLists = $res['data'];
			return in_array($userID, $userLists);
		}		
	}

	private function getCacheGroupUsers($cacheTime='1 day'){		
		$cache_filename = $this::CACHE_DIR.$this::CACHE_USER_LIST;
		if( file_exists($cache_filename) && (strtotime("now") <= strtotime($cacheTime, filectime($cache_filename))) ){	//fresh cache
			$userLists = $this->loadCache($cache_filename);			
		}else{
			$res = $this->getGroupUsers();
			if($res['error']){
				return $this->setError('getCacheGroupUsers', 'getGroupUsers Error');
			}else{
				$userLists = $res['data'];
				$this->saveCache($cache_filename, $userLists);
			}			
		}
		return $this->setRet($userLists);
	}

	private function getGroupUsers(){		
		$requestUrl = '/'.$this::FB_GROUPID.'/members?fields=id';
		$res = $this->getGraphResponse($requestUrl);
		if( !$res['error'] ){
			$response = $res['data'];
			if( !isset($response['data']) ){ return $this->setRet(); }
			
			$userLists = array();
			foreach($response['data'] as $row ){
				$userLists[] = $row['id'];
			}
			return $this->setRet($userLists);
		}else{
			return $this->setError('getGroupUsers','getGraphResponse Error');			
		}
	}

	public function getCacheFeed($param, $cache_filename=null, $cache_time='1 day'){
		$since 		= isset($param['since']) ? $param['since'] : null;
		$until 		= isset($param['until']) ? $param['until'] : null;
		$filter 	= isset($param['filter']) ? $param['filter'] : null;
		$limit 		= isset($param['limit']) ? $param['limit'] : 0;
		
		if( is_null($cache_filename) ){
			$cache_filename = $this::CACHE_DIR.'feedcache';
			if( is_null($since) && is_null($since) ){
				$cache_filename .= '_'.date('YmdHis');
			}else{
				if( !is_null($since) ){
					$cache_filename .= '_'.date('YmdHis', strtotime($since));
				}
				if( !is_null($until) ){
					$cache_filename .= '_'.date('YmdHis', strtotime($until));
				}
			}
			$cache_filename .= '.dat';
		}else{
			$foo = $cache_filename;
			$cache_filename = $this::CACHE_DIR.$foo;
		}

		$feeds = array();
		if( file_exists($cache_filename) && !is_null($cache_time) && (strtotime("now") <= strtotime($cache_time, filectime($cache_filename))) ){
			$feeds = $this->loadCache($cache_filename);
			if( file_exists($cache_filename.'.stat') ){
				$infos = $this->loadCache($cache_filename.'.stat');
			}
		}else{
			$res = $this->getGroupFeed($since, $until, $filter, $limit);
			if($res['error']){
				return $this->setError('getCacheFeed', 'getGroupFeed Error');
			}else{
				$feeds = $res['data'];
				if( count($feeds) ){
					$this->saveCache($cache_filename, $feeds);
				}

				$infos = $res['info'];
				if( count($infos) ){
					$this->saveCache($cache_filename.'.stat', $infos);
				}
			}
		}

		//append cache file info
		$infos['cacheFilename'] = $cache_filename;
		$infos['cacheChangeTime'] = filectime($cache_filename);
		$ret = $this->setRet($feeds);		
		$ret['info'] = $infos;
		return $ret;		
	}

	private function getGroupFeed($since=null, $until=null, $filter=null, $posts_limit=0){
		set_time_limit(30);
		$feed_limit = 1000;
		$posts = array();	//each post infomation
		$photos = array();	//for get large picture image url, use multi process save request
		$likes = array();	//for post order
		$counter = array(	//statistics recoreds
			'feeds'=>0, 'filtered'=>0, 'photoSkiped'=>0
		); 
		
		$groupID = $this::FB_GROUPID;
		$requestUrl = '/'.$groupID.'/feed?fields=from,message,link,picture,likes.limit(1).summary(true)&limit='.$feed_limit;
		if( !is_null($since) ){
			$date = new DateTime($since);
			$date->setTimezone(new DateTimeZone('UTC'));
			$since = $date->format('Y-m-d H:i');
			$requestUrl .= '&since='.$since;
		}
		if( !is_null($until) ){
			$date = new DateTime($until);
			$date->setTimezone(new DateTimeZone('UTC'));
			$until = $date->format('Y-m-d H:i');
			$requestUrl .= '&until='.$until;
		}
		
		do{
			$res = $this->getGraphResponse($requestUrl);
			if( $res['error'] ){				
				return $this->setError('getGroupFeed', 'getGraphResponse Error');
			}
			
			$response = $res['data'];
			if( !isset($response['data']) ){ $ret['error'] = true; return $ret;}
			if( !count($response['data']) ){break; }
			
			foreach( $response['data'] as $feed){
				$counter['feeds']++;

				//search message title
				if( !is_null($filter) && strlen($filter) && !mb_strpos($feed['message'], $filter) ){
					$counter['filtered']++;
					continue;
				}
				
				//photo process
				$photo_url = '';		
				if( strlen($feed['picture']) > 10 ){
					$object_id = explode('_', $feed['picture'])[1];
					$photos[ $feed['id'] ] = $object_id;
				}else{
					$counter['photoSkiped']++;
					continue;
				}

				$post = array(
					'name' => $feed['from']['name'],
					'userID' => $feed['from']['id'],
					'message' => htmlspecialchars($feed['message']),
					'link' => $feed['link'],
					'pictures' => array(),
					'updated_time' => $feed['updated_time'],
					'likes' => $feed['likes']['summary']['total_count']
				);
				$posts[ $feed['id'] ] = $post;
				$likes[ $feed['id'] ] = $post['likes'];

				if( (intval($posts_limit) != 0) && count($posts) >= $posts_limit ) break 2;
			}

			//search for next page
			$nextPage = parse_url($response['paging']['next']);
			$requestUrl = '/'.$groupID.'/feed?'.$nextPage['query'];
			if( !is_null($since) ){ $requestUrl .= '&since='.$since;}
		}while( strlen($requestUrl) );
		$counter['posts'] = count($posts);

		//do photos request to retrive higher resolution image url
		if( count($photos) ){
			$photosChunk = array_chunk($photos, 50, true);	//single request only allow 50 multi id
			foreach( $photosChunk as $requestData ){
				$requestPhotoUrl = '/?ids='.implode(',', $requestData).'&fields=images{source}';
				
				$resPhoto = $this->getGraphResponse($requestPhotoUrl);
				if($resPhoto['error']){
					continue;
				}else{
					foreach( $resPhoto['data'] as $id => $info ){
						if( !count($info['images']) ) continue;
						
						$pictures = array();
						foreach($info['images'] as $image){
							$pictures[] = $image['source'];
						}
						$feed_id = array_search($id, $photos);
						$posts[$feed_id]['pictures'] = $pictures;
					}
				}
			}		
		}
		
		//resort by likes
		arsort($likes);
		$tmp = $posts; 	$posts = array();
		foreach($likes as $id => $cnt){
			$posts[$id] = $tmp[$id];
		}

		$ret = $this->setRet($posts);
		$ret['info']['counter'] = $counter;
		return $ret;
	}
}