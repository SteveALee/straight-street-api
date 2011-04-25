#!/usr/bin/python

# note want something like following in .htacces
# so url gets redirected to this file
#
#RewriteEngine On
#
#RewriteBase /api/
#RewriteCond %{REQUEST_METHOD} ^GET$
#RewriteRule (^.*$) get.py [L]

PAGESIZEDEF = 15;
PAGESIZEMAX = 20;
APPIDMINCHARS = 5;
APPIDMAXCHARS = 25;
MAX_CONCAT_LENGTH = 2048 * 3 # maximum length of group_concant - increasee if media list for tags gets truncated (no utf8 chars will be 1/3 of this)

import cgi
#next line for debugg messages in output
import cgitb; cgitb.enable()
import MySQLdb
import os

def main(): # do this so get useful errors with cgitb

    def usage():
        header()
        print '''
 <h1>Straight-street.com API - Alpha</h1>
<p>This is the RESTful API for the <a href='http://straight-street.com'>straight-street.com</a> symbol set. It allows programs to search and access symbols as users can using the interactive <a href="../gallery.php">gallery web page</a>.
</p>
<p>
 A <a href='http://straight-street.com/apitest.html'>test web app</a> is available for exploring the api and provides an example of javascript client-side access.
 </p>
 <h2>Specification</h2>
<p>All symbols have a name and may have multiple tags associated with them. Names and tag names may contain spaces.</p>
<p>
	<table>
	 <tr><td>http://straight-street.com/api/symbols/EN</td><td>all symbols</td></tr>
	 <tr><td>http://straight-street.com/api/symbols/EN/{text}</d><td>all symbols with a name or tag that includes 'text' (space delimited tokens allowed)</td></tr>
	 <tr><td>http://straight-street.com/api/symbol/EN/{name}</td><td>single symbol with name of 'name'</td></tr>
	 <tr><td>http://straight-street.com/api/tags/EN</td><td>all tags</td></tr>
	 <tr><td>http://straight-street.com/api/tags/EN/{text}</td><td>all tags attached to symbols with 'text' in their name</td></tr>
	 <tr><td>http://straight-street.com/api/tag/EN/{name}</td><td>single tag of name</td></tr>
	 <tr><td>http://straight-street.com/api/usage</td><td>Usage information - this page</td></tr>
	 <tr><td colspan='2'></td></tr>
	 <tr><td>?appid=example.com</td><td>Required application id e.g. domain. ''' +str(APPIDMINCHARS)+''' to '''+str(APPIDMAXCHARS)+''' characters</td></tr>
	 <tr><td>?page=n</td><td>Optional page n if multiple results returned (1st page is 0) </td></tr>
	 <tr><td>?pagesize=n</td><td>Optional number of items in a page if multiple results returned. Max is ''' + str(PAGESIZEMAX) +''', default is ''' +str(PAGESIZEDEF)+'''</td></tr>
	 <tr><td>?callback=func</td><td>Optional <a href='http://developer.yahoo.com/common/json.html'>function call support</a> for cross domain access (AKA JSONP)</td></tr>
	 </table>
 </p>
 <p>
 Responses are JSON but the mime type is text/plain so they can be viewed in browser. They include the related symbol or tag names plus associated URLs into the api. The URLs for various version of the symbol image are also returned.
 </p>
 <h2>Examples</h2>
 <p>
 The following links demostrate the API (appid is not shown for simplicity).
 <ul>
 <li><a href='http://straight-street.com/api/symbols/EN?appid=SSApiUsage'>http://straight-street.com/api/symbols/EN</a></li>
 <li><a href='http://straight-street.com/api/symbols/EN/sweet?appid=SSApiUsage'>http://straight-street.com/api/symbols/EN/sweet</a></li>
 <li><a href='http://straight-street.com/api/symbols/EN/sweet?appid=SSApiUsage&pagesize=3&page=1'>http://straight-street.com/api/symbols/EN/sweet?pagesize=3&page1</a></li>
 <li><a href='http://straight-street.com/api/symbol/EN/sweet?appid=SSApiUsage'>http://straight-street.com/api/symbol/EN/sweet</a></li>
 <li><a href='http://straight-street.com/api/tags/EN?appid=SSApiUsage'>http://straight-street.com/api/tags/EN</a></li>
 <li><a href='http://straight-street.com/api/tags/EN/sweet?appid=SSApiUsage'>http://straight-street.com/api/tags/EN/sweet</a></li>
 <li><a href='http://straight-street.com/api/tag/EN/sweet?appid=SSApiUsage'>http://straight-street.com/api/tag/EN/sweet</a></li>
 <li><a href='http://straight-street.com/api/symbols/EN/sweet?appid=SSApiUsage&callback=jsonpcb'>http://straight-street.com/api/symbols/EN/sweet?callback=jsonpcb</a></li>
 <li><a href='http://straight-street.com/api/symbols/EN/lever%20arch%20file?appid=SSApiUsage'>http://straight-street.com/api/symbols/EN/lever arch file</a></li>
</ul>
</p>
 <h2>Known issues</h2>
 <p>Speed - this is being looked at.</p>
  '''
        footer()
        
    def error(status="Status: 400 Bad Request"): 
       print "Content-type: text/plain"
       print status
       print ""
       sys.exit()

    def errorNotFound():
        error(status="Status: 404 Not Found")
       
    def header():
        print "Content-type: text/html; charset=utf-8 " 
        print "Status: 200 Ok" 
        print "" 
        print """<html>
                    <head>
                    <META http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title>Straight-Street</title>
                    </head><body>"""

    def footer():
        print "</body></html>" 

    class DBConnection:
        def __init__(self):
            try:
                self.con = MySQLdb.connect(host = 'localhost',
                                                        user = '<USER>',
                                                        passwd = '<PWD>',
                                                        db = '<DB>',
                                                        unix_socket = '/var/lib/mysql/mysql.sock',
                                                        use_unicode=True)
                self.cursor=None
            except MySQLdb.Error, e:
                raise Exception( "Error %d: %s" % (e.args[0], e.args[1]))

        def __del__(self):
            self.closeCursor()
            if self.con:
                self.con.close()
            
        def closeCursor(self):
            if self.cursor:
                self.cursor.close()
                self.cursor = None
                
        def query(self, sql, args=None):
            try:
                self.closeCursor();
                self.cursor = self.con.cursor(MySQLdb.cursors.DictCursor)
                self.cursor.execute('SET group_concat_max_len = %u;' % MAX_CONCAT_LENGTH) # default is 1024 which gives 341 UTF chars
                self.cursor.execute(sql, args)
                rowSet = self.cursor.fetchall()
                return rowSet
            except MySQLdb.Error, e:
                raise Exception( "Error %d: %s" % (e.args[0], e.args[1]))

        def queryPage(self, sql, args=None, start=0, size=10):
            try:
                sql = sql.replace('SELECT  ','SELECT SQL_CALC_FOUND_ROWS ')
                sql = sql + ' LIMIT %d,%d' % (start,size)
                rs = self.query(sql, args)
                cursor = self.con.cursor()
                cursor.execute('SELECT FOUND_ROWS()');
                totRows = cursor.fetchone()[0];
                cursor.close()
                more = (start + size) < totRows
                return (rs, more, totRows)
            except MySQLdb.Error, e:
                raise Exception ( "Error %d: %s" % (e.args[0], e.args[1]))

    SQLLOG = u'''
INSERT INTO t_api_log (clientip, appid, count) VALUES (%s,%s,1)
  ON DUPLICATE KEY UPDATE count=count+1;
 '''

    SQLMEDIA = u'''
SELECT  m.name,
        m.rated,
            CONCAT('$symbolsENURLBase', 'thumb/t-', mp.basename, '.gif') AS thumbnailURL,
            CONCAT('$symbolsENURLBase', 'wmf/', mp.basename, '.wmf') AS imageWMFURL,
            CONCAT('$symbolsENURLBase', 'svg/', mp.basename, '.svg') AS imageSVGURL,
            #CONCAT('$symbolsENURLBase', 'png/', mp.basename, '.png') AS imagePNGURL,
             t.tags

FROM 	t_media m
            INNER JOIN t_media_path mp
                ON (m.id = mp.mid AND mp.type = 0)
            INNER JOIN (SELECT	ms_m.id AS mid,
                            CONCAT(REPLACE(ms_m.name, '_', ','), ',', COALESCE(GROUP_CONCAT(DISTINCT ms_t.tag ORDER BY ms_t.tag SEPARATOR ','), '')) AS name_tags
                            FROM    t_media AS ms_m
                                        LEFT JOIN t_media_tags AS ms_mt
                                            ON ms_m.id = ms_mt.mid
                                        LEFT JOIN t_tag AS ms_t
                                            ON ms_t.id = ms_mt.tid
                                        GROUP BY ms_m.id ) AS nt
                ON (m.id = nt.mid)
            INNER JOIN (SELECT	ms_m.id AS mid,
                            COALESCE(GROUP_CONCAT(DISTINCT ms_t.tag SEPARATOR ','), '') AS tags
                            FROM    t_media AS ms_m
                                        LEFT JOIN t_media_tags AS ms_mt
                                            ON ms_m.id = ms_mt.mid
                                        LEFT JOIN t_tag AS ms_t
                                            ON ms_t.id = ms_mt.tid
                                        GROUP BY ms_m.id ) AS t
                ON (m.id = t.mid)
            INNER JOIN t_media_path mp_t
                ON (m.id = mp_t.mid AND mp_t.type = 1)
            INNER JOIN t_media_path mp_p
                ON (m.id = mp_p.mid AND mp_p.type = 3)
WHERE    m.status_id = 4
             AND ( CASE %s WHEN 'symbol' THEN (m.name = %s)
                            WHEN 'symbols' THEN (%s = '' OR ($clauseTagMatch))
                            ELSE 0=1
                            END )
ORDER BY m.name	
'''  
    
    SQLTAGS = u'''
SELECT  t.tag AS name,
            tm.media
FROM    t_tag t
            INNER JOIN (SELECT mt.tid, 
                                COALESCE(GROUP_CONCAT(DISTINCT m.name ORDER BY m.name SEPARATOR ','), '') AS media
                                FROM t_media_tags mt
                                INNER JOIN t_media as m
                                    ON m.id = mt.mid AND m.status_id = 4
                                GROUP BY mt.tid ) AS tm
                ON tm.tid = t.id 
            INNER JOIN (SELECT mt.tid, 
                                COALESCE(GROUP_CONCAT(DISTINCT REPLACE(m.name,'_',',') SEPARATOR ','), '') AS media
                                FROM t_media_tags mt
                                INNER JOIN t_media as m
                                    ON m.id = mt.mid AND m.status_id = 4
                                GROUP BY mt.tid) AS tms
                ON tms.tid = t.id 

WHERE    
              CASE %s WHEN 'tag' THEN (t.tag = %s)
                        WHEN 'tags' THEN (%s = '' OR FIND_IN_SET(%s, tms.media))
                        ELSE 0=1
                        END 
ORDER BY t.tag	
'''

    class Dumper:
        def __init__(self, appid, what, find, lang, page, pageSize):
            URLBASE = 'http://straight-street.com/'
            APIURLBASE = 'http://straight-street.com/api/'
            APITAGSURLBASE = 'http://straight-street.com/api/tags/EN/'
            APIMEDIAURLBASE = 'http://straight-street.com/api/symbols/EN/'
            MEDIAURLBASE = 'http://straight-street.com/media/'
            SYMBOLSENURLBASE = 'http://straight-street.com/media/symbols/EN/'
            
            self.what = what
            
            if what in ['symbols', 'symbol']:
            
                SQL = SQLMEDIA
                if what == 'symbols':
                    find = find.replace('+', ' ') # for forms
                    args = tuple(find.split(' '))
                    argsPH = ('%s',) * len(args)
                    strTagMatchClause = "FIND_IN_SET(" + ", nt.name_tags) > 0 OR FIND_IN_SET(".join(argsPH) + ", nt.name_tags) > 0"
                else:
                    args = (find,)
                    strTagMatchClause = "%s = ''"

            elif what in ['tags', 'tag']:
                SQL = SQLTAGS
                args = (find,)
                strTagMatchClause = ''
            else:
                sys.exit();

            
            from string import Template
            t = Template(SQL)
            sql = t.safe_substitute({'mediaURLBase': MEDIAURLBASE
                                      , 'symbolsENURLBase': SYMBOLSENURLBASE
                                      , 'clauseTagMatch': strTagMatchClause})
            
            db = DBConnection()
            rowSet, more, totalRows = db.queryPage(sql, (what, find, find) + args, page * pageSize, pageSize)

            if what in ['symbols', 'symbol']:
                for row in rowSet:
                    if row['tags'] != '':
                        tagNames = row['tags'].split(',')
                        row['tags'] = dict([ (tagName, APITAGSURLBASE+tagName) for tagName in tagNames])
                    else:
                        row['tags'] = []
            elif what in ['tags', 'tag']:
                for row in rowSet:
                    if row['media'] != '':
                        mediaNames = row['media'].split(',')
                        row['media'] = dict([ (mediaName, APIMEDIAURLBASE+mediaName) for mediaName in mediaNames])
                    else:
                        row['media'] = []
            else:
                sys.exit();
            
            db.query(SQLLOG, (os.environ['REMOTE_ADDR'], appid))

            if what in ['symbol','tag'] and len(rowSet) > 0:
                self.obj = { what: rowSet[0] }
            elif what in ['symbols', 'tags']:
                from math import ceil
                self.obj = { what: rowSet, 'page': page, 'itemCount': len(rowSet), 'totalItemCount': totalRows, 'pageCount':  int(ceil(float(totalRows)/pageSize)) }
                if more:
                    self.obj['nextURL'] = APIURLBASE+'%s/%s/%s?page=%u' % (what,lang,find, page+1)
            else:
                self.obj = {}
        
        def dumpHeader(self):
            pass
            
        def dumpFooter(self):
            pass
            
        def dumpBody(self):
            pass
            
        def dump(self):
            self.dumpHeader()
            self.dumpBody()
            self.dumpFooter()
            

    class JSONDumper(Dumper):
        def __init__(self, appid, what, find, lang, page, pageSize, callback):
            Dumper.__init__(self, appid, what, find, lang, page, pageSize)
            self.callback = callback
            self.find = find

        def dumpHeader(self):
#            print "Content-type: application/json" 
            print "Content-type: text/plain; charset=utf-8" 
            print "Status: 200 Ok" 
            print "" 

        def dumpBody(self):
            import simplejson as json
            strJSON = json.dumps(self.obj, indent=4)
            if callback:
                strJSON = callback+'('+strJSON+');'
            print strJSON
    
#   class XMLDumper(Dumper):
#        def __init(self, rs):
#            Dumper.__init__(self, rs)
#
#        def dumpHeader(self):
#            print "Content-type: application/xml" 
#            print "Status: 200 Ok" 
#            print "" 
#            print '<?xml version="1.0" encoding="utf-8" ?>'
#            print '<'+self.what+' xblns="http://straight-street.com/api">'
#
#        def dumpFooter(self):
#            print "\n</"+what+">"
#
#        def dumpBody(self):
#            if 'itemCount' in self.obj:
#                for item in self.obj[self.what]:
#                    line = "<"+self.what
#                    line += [ "  "+k+"='"+v+"'" for k, v in item if not isinstance(v, list)]
#                    line += ">\n</"+self.what+">"
#                    print line

    if not os.environ['REQUEST_METHOD'] in ['GET', 'HEAD']: 
       error("Status: 405 Method Not Allowed")

    if os.environ['REQUEST_METHOD'] in ['HEAD']: 
        # to do check this - do we need to send content type?
        sys.exit()

    # default to UTF8 encoding
    import codecs, sys
    sys.stdout = codecs.getwriter('utf8')(sys.stdout)
    
    # how we were invoked - mod rewrite will play with this
    urlpath = os.environ['REQUEST_URI'].split('?')[0]
    urlpath  = urlpath.split('/')
    del urlpath[0]

    what = urlpath[1]
    if what in ['usage','']:
        usage()
        sys.exit()
    try:
        lang = urlpath[2]
    except IndexError:
        lang = ''
    if lang != 'EN':
        errorNotFound();
    try:
    	import urllib
        find = urllib.unquote(urlpath[3]).decode('utf-8')
    except IndexError:
        find = ''

    form = cgi.FieldStorage() 

    if what == 'get.py':
        what = form.getfirst('what', '')
        if what not in ['symbols', 'tags', 'symbol', 'tag']:
            what = 'symbols'                                # what are we querying  
        find = cgi.escape(form.getfirst('find', ''))                #single symbol name or a tag name
        lang = cgi.escape(form.getfirst('lang', ''))                
    elif what == 'symbols':
        pass
    elif what == 'symbol':
        if not find:
            errorNotFound();
    elif what == 'tags':
        pass
    elif what  == 'tag':
        if not find:
            errorNotFound()
    else:
            errorNotFound()

    # query string options
    appid = form.getfirst('appid', '')          # unique app id
    if len(appid) < APPIDMINCHARS or len(appid) > APPIDMAXCHARS:
        errorNotFound()
    try:
        page = int(form.getfirst('page', ''))   # page number for collection
    except ValueError:
        page = 0
    try:
        pageSize = int(form.getfirst('pagesize', ''))   # items per page for collection
    except ValueError:
        pageSize = PAGESIZEDEF
    if (pageSize > PAGESIZEMAX) :
        pageSize = PAGESIZEDEF
    callback = form.getfirst('callback', '')          # JSONP callback

    #f = XMLDumper(what, find, page)
    f = JSONDumper(appid, what, find, lang, page, pageSize, callback)
    f.dump()

try:
    main() 
except SystemExit:
    pass
except Exception, e:
    print "Content-type: text/plain; charset=utf-8" 
    print "Status: 200 Ok" 
    print "" 
    print e
