# FB Group Gallery - A Gallery from Facebook Group

This Gallery is provide a interface to show facebook group feed photos.
It can filter message and no photo post by date and word.
And integrate file cache ability to prevent spending lots of time to fetch facebook feed.

# Using Compoments
* Bootstrap <http://getbootstrap.com>
* jQuery <http://jquery.com>
* Masonry <http://masonry.desandro.com>
* imagesloaded <https://github.com/desandro/imagesloaded>
* Magnific Popup <http://dimsemenov.com/plugins/magnific-popup>
* Autolinker.js <https://github.com/gregjacobs/Autolinker.js>


#Installion
###Composer
Using composer to update dependency
```
composer update
```

Edit follwing these files to retrive feeds
###class\fbgroup.php
```php
//Line 6~8
const FB_APPID = 'APP ID';
const FB_APPSECRET = 'APP SECRET';
const FB_GROUPID = 'GROUP ID'; 

//Line 18~19
const GROUP_PRIVATE = FALSE;	
const FB_DEVELOPERID = 'DEVELOPER ID';
```
if GROUP_PRIVATE is set to true, mean the facebook group is privacy.
You(Developer) must are one of group member to get feeds.
And this class will use your(developer's) access token to retrive feeds.

###class\conf.php
```php
const LOGIN_URL = 'YOUR WEB URL/?login';
```
Fill your web url to facebook redirect;

###(option) Google analysis pageView

#### GA
remove comments for Line 91~62 in view/template.php
and edit "GA ID";
```javascript
ga('create', 'GA ID', 'auto');
ga('send', 'pageview');
```

#### Using Sheet to get pageView
edit Line 124 in view/template.php
```javascript
counter.sheetID = "Google Sheet ID";
```

# Class: FB_Group

## Debug, Error Infomation
```php
$fb_group = new FB_Group();

//get Debug
$debugs = $fb_group->getDebug();

//set Debug(private)
$fb_group->setDebug("Identify Name", "DEBUG DATA");

//get Error
$debugs = $fb_group->getError();
```

## User

### Get User Infomation(Facebook Session)
```php
$userInfo = $fb_group->getUserInfo();
```

### Check User is Member
```php
(bool) $fb_group->checkisMember();
```

####Note: 
This method will use getCacheGroupUsers() and getGroupUsers() to get users in group, and Using cache, cache time default is 24 hours.

## Feed
```php
//parameter
$PARAM = array(
	'since' => date('Y-m-d 00:00'),		//time start
	'until' => date('Y-m-d 23:59'),		//time end
	'filter' => null,					//message filter word
	'limit' => 10						//max posts number
);
$feeds = getCacheFeed( $PARAM, $CACHE_FILENAME, $CACHE_TIME );

//return 
array(
	'error' => (bool),	//if error is true, you can use getError() to find out what happened
	'data'  => array(), //feeds data;
	'info'  => array()	//additional operation info
);

//feeds data example
array(
	'name' => 'User Name',
	'userID' => 'User Facebook ID',
	'message' => 'MESSAGE',
	'link' => 'POST LINK',	//type is status won't have this
	'pictures' => array(),	//store different photo size info, if has picture
	'updated_time' => 'post last update time',
	'likes' => 'likes summary'
);
```
####Note
- if $CACHE_FILENAME is null, it will auto generate by date. Likes: feedcache_20141215000000_20141221000000.dat

- Feeds will sorting by likes count

# Class: View
This is a very very simple template engine
Default View Dir is named 'view'.
Default Template is named 'template' inside View Dir.

## Initial
Using
```php
$view = new View( 'VIEW FILE', 'ARRAY DATA IN VIEW' );
$view->with( 'VARIABLE NAME', $VARIABLE );
```
or
```php
$view = View::make( 'VIEW FILE', 'ARRAY DATA IN VIEW' )
            ->with( $VARIABLE_ARRAY );

```

## OUTPUT
just echo or conver view to string
```php
echo $View::make('VIEW FILE');
```

## Option
### different template
Call render() at end
```php
echo View::make('VIEW FILE')->render('ANOTHER TEMPLATE');
```
