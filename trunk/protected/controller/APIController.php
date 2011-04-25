<?php

require 'protected/controller/_db.php';
require 'protected/config/apiconsts.php';

// for now everything here in controller
// TODO refactor to make more DRY

class APIController extends DooController
{
    protected $dbh;

    public function  __construct() {
        $this->dbh = DBCXn::get();
		$st = $this->dbh->query('SET group_concat_max_len = '.MAX_CONCAT_LENGTH); # default is 1024 which gives only 341 UTF-8 chars
		$st->closeCursor();
    }

    public function  __destruct() {
    }

	protected function setContent()
	{
		$this->setContentType('json');
	}

	protected function parseArgs()
	// populate $args from properties and args
    // can exit if no appid specified
	{ 
        $args = $_GET; // copy
        
        if (!isset($args['appid']) ||
            preg_match(VALID_APPID, $args['appid']) == 0)
        {
            header("status: 401 Unauthorized");
            header("HTTP/1.0 401 Unauthorized");
		    $data['title'] = 'Error 401 Unauthorised</em>';
	        $data['content'] = 'Valid appid not provided. Please supply an appid in the querystring to identify the calling application.';
	        $data['baseurl'] = Doo::conf()->APP_URL;
	        $data['printr'] = null;
	        $this->view()->renderc('template', $data);
	        exit();
		}
		
		if (isset($this->params))
			foreach ($this->params as $key => $val)
				$args[$key] = urldecode($val);
			
		$args['name'] = isset($args['name']) ? str_replace('+', ' ', $args['name']) : '';
		$args['nametokens'] = split('[ \t]+', $args['name']);
		
		$args['page'] = (isset($args['page'])) ? (int)$args['page'] : 0;
		
		$args['pagesize'] = (isset($args['pagesize'])) ? (int)($args['pagesize']) : PAGESIZEDEF;

		$args['callback'] = (isset($args['callback'])) ? $args['callback'] : '';
		
		$this->args = $args;
	}
	
	protected function expandTags($tags)
	// convert list of tags to array of tag names with URLS
	{
		$input = explode(',', $tags);
		$out = array(); 
		foreach( $input as $v )
		{ 
			$out[$v] = APITAGSURLBASE."$v?appid={$this->args['appid']}"; 
		}
		return($out);
	}
	
	protected function expandMedia($media)
	// convert list of tags to array of tag names with URLS
	{
		$input = explode(',', $media);
		$out = array(); 
		foreach( $input as $v )
		{ 
			$out[$v] = APIMEDIAURLBASE."$v?appid={$this->args['appid']}"; 
		}
		return($out);
	}
	
	protected function json_encode($what)
	// fix issues and pretty print. Adds JSONP syntax if required
	{
        $result = json_encode( $what);
        $result = str_replace('\\/', '/', $result); // fix quoted solidus - http://bugs.php.net/bug.php?id=49366
        if ($this->args['callback'])
        	$result = "{$this->args['callback']}({$result});";
        return $result;
	}

    protected function query($sql, $args)
    {
        $st = $this->dbh->prepare($sql);
        $st->execute($args);
	    $result = $st->fetchAll(PDO::FETCH_ASSOC);
        $st->closeCursor();
        return $result;
     }

	protected function getPage($sql, $args, $start=0, $size=10)
	{
		//print $sql;exit;
		$sql = str_replace('SELECT  ','SELECT SQL_CALC_FOUND_ROWS ', $sql);
		$sql = $sql." LIMIT {$start}, {$size}";
		$rows = $this->query($sql, $args);
		$st2 = $this->dbh->query('SELECT FOUND_ROWS()');
		$totRows = $st2->fetch(PDO::FETCH_NUM); $totRows = (int)$totRows[0]; // hack for crap expression PHP parsing
        $st2->closeCursor();
		$more = ($start + $size) < $totRows;
		return array('rows' => $rows, 'more' => $more, 'totRows' => $totRows);
	}

    protected function logAccess()
    {
		$strQuery= <<<EOT
INSERT INTO t_api_log (clientip, appid, count) VALUES (?,?,1)
  ON DUPLICATE KEY UPDATE count=count+1;
EOT;
        $st = $this->dbh->prepare($strQuery);
        $st->execute(array($_SERVER['REMOTE_ADDR'], $this->args['appid']));
        $st->closeCursor();
    }
     
/*    public function admin(){
        $users['admin'] = '1234';
        $users['doophp'] = '1234';
        $users['demo'] = 'demo';

        Doo::loadCore('auth/DooDigestAuth');
        $username = DooDigestAuth::http_auth('Food Api Admin', $users, 'Failed to login!');
        echo 'You are login now. Welcome, ' . $username;
    }
*/
    public function api_fail(){
        switch($this->accept_type()){
            case 'json':
                $this->setContentType('json');
                $result = json_encode( array('error'=>$this->params['msg']) );
                break;
            case 'xml':
                $this->setContentType('xml');
                $result =  '<error>'.$this->params['msg'].'</error>';
                break;
        }
        echo $result;
    }
}

class SymbolController extends APIController
{
    public function listSymbol()
    {
		$this->parseArgs();
		$appid = $this->args['appid'];
	    $name = $this->args['name'];   
	
		$constHack = 'constHack'; // need this due to scoping
		$strQuery= <<<EOT
SELECT  m.name,
        m.rated,
        CONCAT('{$constHack(SYMBOLSENURLBASE)}thumb/t-', m.name, '.gif') AS thumbnailURL,
        CONCAT('{$constHack(SYMBOLSENURLBASE)}wmf/', m.name, '.wmf') AS imageWMFURL,
        CONCAT('{$constHack(SYMBOLSENURLBASE)}svg/', m.name, '.svg') AS imageSVGURL,
        t.tags

FROM 	t_media m
            INNER JOIN (SELECT	ms_m.id AS mid,
                            COALESCE(GROUP_CONCAT(DISTINCT ms_t.tag ORDER BY ms_t.tag SEPARATOR ','), '') AS tags
                            FROM    t_media AS ms_m
                                        LEFT JOIN t_media_tags AS ms_mt
                                            ON ms_m.id = ms_mt.mid
                                        LEFT JOIN t_tag AS ms_t
                                            ON ms_t.id = ms_mt.tid
                                        GROUP BY ms_m.id ) AS t
                ON (m.id = t.mid)
WHERE    m.status_id = 4
             AND (m.name = ?)
ORDER BY m.name	
EOT;
        $result = $this->query($strQuery, array($name));
        $this->logAccess();	
        
	    $this->setContent();
	    if (count($result))
	    {
	    	$result[0]['tags'] = $this->expandTags($result[0]['tags']);
		    $obj = (object)array('apiver' => VERSION, 
	    					                 // crappy hack to work around lack of obj literals
                                 'appid' => $appid, 
	    					     'symbol' => $result[0]
                                 );
	        $result = $this->json_encode($obj);
	        echo $result;
	        return;
	    }
	    echo '{}';
	}


    public function listSymbols()
    {
		$this->parseArgs();
	    $name = $this->args['name'];   
		$pageNo = $this->args['page'];
		$pageSize = $this->args['pagesize'];
		$appid = $this->args['appid'];

		$constHack = 'constHack'; // need this due to scoping
		$strQuery= <<<EOT
SELECT  m.name,
        m.rated,
        CONCAT('{$constHack(SYMBOLSENURLBASE)}thumb/t-', mp.basename, '.gif') AS thumbnailURL,
        CONCAT('{$constHack(SYMBOLSENURLBASE)}wmf/', mp.basename, '.wmf') AS imageWMFURL,
        CONCAT('{$constHack(SYMBOLSENURLBASE)}svg/', mp.basename, '.svg') AS imageSVGURL,
        t.tags

FROM 	t_media m
            INNER JOIN t_media_path mp
                ON (m.id = mp.mid AND mp.type = 0)
            INNER JOIN (SELECT	ms_m.id AS mid,
                            COALESCE(GROUP_CONCAT(DISTINCT ms_t.tag ORDER BY ms_t.tag SEPARATOR ','), '') AS tags
                            FROM    t_media AS ms_m
                                        LEFT JOIN t_media_tags AS ms_mt
                                            ON ms_m.id = ms_mt.mid
                                        LEFT JOIN t_tag AS ms_t
                                            ON ms_t.id = ms_mt.tid
                                        GROUP BY ms_m.id ) AS t
                ON (m.id = t.mid)
WHERE    m.status_id = 4
             AND ( ? = '' OR (FIND_IN_SET(?, CONCAT(REPLACE(m.name, '_', ','), ',', t.tags)) > 0))                          
ORDER BY m.name	
EOT;
	// TO DO what about search terms like 'lever arch file' ?
	
    $page = $this->getPage($strQuery, array($name, $name), $pageNo * $pageSize, $pageSize);
    $this->logAccess();
    $rows = $page['rows'];

    $this->setContent();
    if (count($rows))
    {
    	foreach ($rows as $k => $v)
    	{
    		$rows[$k]['tags'] = $this->expandTags($rows[$k]['tags']);
    		$rows[$k]['rated'] = (int)$rows[$k]['rated']; // PDO returns everythign as a string - doh!
    	}
 	    $obj = (object)array('apiver' => VERSION, 
	    					 'appid' => $appid, 
	    					 'totalItemCount' => $page['totRows'], 
	    					 'itemCount' => count($rows),
	    					 'page' => $pageNo, 
	    					 'pageCount' => (int)(ceil($page['totRows'] / $pageSize))); // crappy hack to work around lack of obj literals
        $nameClause = ($name <> '') ? "$name/" : '';
        if ($pageNo != 0)
        {
        	$prevPageNo = $pageNo - 1;
        	$obj->prevURL = APIURLBASE."symbols/EN/{$nameClause}{$prevPageNo}/{$pageSize}?appid={$appid}";
        }
        if ($page['more'])
        {
        	$nextPageNo = $pageNo + 1;
        	$obj->nextURL = APIURLBASE."symbols/EN/{$nameClause}{$nextPageNo}/{$pageSize}?appid={$appid}";
        }
	    $obj->match = $name;
 	    $obj->symbols = $rows; // is here to give nice order in JSON 
 	    
        $json = $this->json_encode($obj);
        echo $json;
        return;
    }
   
   echo '{}';
       
   }  
}


class TagController extends APIController
{
    public function listTag()
    {
		$this->parseArgs();
		$appid = $this->args['appid'];
	    $name = $this->args['name'];   
	
		$constHack = 'constHack'; // need this due to scoping
		$strQuery= <<<EOT
SELECT  t.tag AS name,
            tm.media AS symbols
FROM    t_tag t
            INNER JOIN (SELECT mt.tid, 
                                COALESCE(GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ','), '') AS media
                                FROM t_media_tags mt
                                INNER JOIN t_media as m
                                    ON m.id = mt.mid AND m.status_id = 4
                                GROUP BY mt.tid ) AS tm
                ON tm.tid = t.id 

WHERE    t.tag = ?
ORDER BY t.tag
EOT;
        $result = $this->query($strQuery, array($name));
        $this->logAccess();	
	
	    $this->setContent();
	    if (count($result))
	    {
	    	$result[0]['symbols'] = $this->expandMedia($result[0]['symbols']);
		    $obj = (object)array(                // crappy hack to work around lack of obj literals
                                 'apiver' => VERSION, 
	    					 	 'appid' => $appid, 
	    					     'tag' => $result[0]
                                 );
	        $result = $this->json_encode($obj);
	        echo $result;
	        return;
	    }
	    echo '{}';
	}

    public function listTags()
    {
		$this->parseArgs();
	    $name = $this->args['name'];   
		$pageNo = $this->args['page'];
		$pageSize = $this->args['pagesize'];
		$appid = $this->args['appid'];

		$constHack = 'constHack'; // need this due to scoping
		$strQuery= <<<EOT
SELECT  tms.tag AS name,
        tms.media AS symbols
FROM    (SELECT mt.tid, 
				t.tag,
          		COALESCE(GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ','), '') AS media,
          		COALESCE(GROUP_CONCAT(DISTINCT REPLACE(m.name,'_',',') SEPARATOR ','), '') AS media_tokens
         FROM t_media_tags mt
         INNER JOIN t_media as m
             ON m.id = mt.mid AND m.status_id = 4
         INNER JOIN t_tag t
         	ON t.id = mt.tid
         WHERE ? = '' OR (FIND_IN_SET(?, REPLACE(m.name,'_',',')) OR t.tag = ?)
         GROUP BY mt.tid
         ) AS tms
ORDER BY tms.tag	
EOT;
	// TO DO what about search terms like 'lever arch file' ?
	
    $page = $this->getPage($strQuery, array($name, $name, $name), $pageNo * $pageSize, $pageSize);
    $this->logAccess();
    $rows = $page['rows'];

    $this->setContent();
    if (count($rows))
    {
    	foreach ($rows as $k => $v)
    	{
    		$rows[$k]['symbols'] = $this->expandMedia($rows[$k]['symbols']);
    	}
 	    $obj = (object)array('apiver' => VERSION, 
	    					 'appid' => $appid, 
	    					 'totalItemCount' => $page['totRows'], 
	    					 'itemCount' => count($rows),
	    					 'page' => $pageNo, 
	    					 'pageCount' => (int)(ceil($page['totRows'] / $pageSize))); // crappy hack to work around lack of obj literals
        $nameClause = ($name <> '') ? "$name/" : '';
        if ($pageNo != 0)
        {
        	$prevPageNo = $pageNo - 1;
        	$obj->prevURL = APIURLBASE."tags/EN/{$nameClause}{$prevPageNo}/{$pageSize}?appid={$appid}";
        }
        if ($page['more'])
        {
        	$nextPageNo = $pageNo + 1;
        	$obj->nextURL = APIURLBASE."tags/EN/{$nameClause}{$nextPageNo}/{$pageSize}?appid={$appid}";
        }
	    $obj->match = $name;
 	    $obj->tags = $rows; // is here to give nice order in JSON 
 	    
        $json = $this->json_encode($obj);
        echo $json;
        return;
    }
   
   echo '{}';
       
   }  

}

?>