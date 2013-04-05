<?php
/**
 * @author Jean-Lou Dupont
 * @package StubManager
 * @version 1.3.2
 * @Id $Id: StubManager.php 1200 2008-10-10 02:13:37Z jeanlou.dupont $
 */
//<source lang=php>
class StubManager {
	// This version number must match that of 
	// the corresponding PEAR package.
	const version = '1.3.2';

	const thisType = 'other';
	const thisName = 'StubManager';
	const thisVersion = '$Id: StubManager.php 1200 2008-10-10 02:13:37Z jeanlou.dupont $';

	// pointer to extensions directory
	// whether be in a PEAR context or MW root installation
	static $edir = null;
	
	const MWbaseURI = 'http://www.mediawiki.org/wiki';
	
	/**
	 * Template (in the MediaWiki namespace)
	 * for the Special:Version page
	 */
	static $_templateName = "MediaWiki:ExtensionState";
	
	/**
	 * State constants
	 */
	const STATE_OK        = 0;	
	const STATE_ERROR     = 1;
	const STATE_ATTENTION = 2;
	const STATE_DISABLED  = 3;
	
	static $state_icons = array(
		self::STATE_OK			=> 'icon_ok.png',
		self::STATE_ERROR		=> 'icon_error.png',
		self::STATE_ATTENTION	=> 'icon_attention.png',
		self::STATE_DISABLED	=> 'icon_disabled.png',
	);
	
	static $state_messages = array(
		self::STATE_OK			=> 'ok',
		self::STATE_ERROR		=> 'error',
		self::STATE_ATTENTION	=> 'attention required',
		self::STATE_DISABLED	=> 'disabled',
	);
	
	/**
	 * Contains the list of registered extensions
	 * @private
	 */	
	static $stubList = array();
	
	static $paramsList = array(	'class',		// mandatory
								'classfilename',// mandatory
								'i18nfilename',
								'hooks',
								'logging',
								'tags',
								'mgs',
								'mws',
								'nss',
								'enss'
								);
	
	/**
	 * Create Stub
	 * Facility for extensions
	 */
	public static function createStub2( $params ) {
	
		if (!is_array( $params ))
			{ echo __METHOD__.' $params not an array.'; return; }

		// need to make sure we've got the mandatory parameters covered.		
		if (!isset( $params['class'] ))
			{ echo __METHOD__.' missing "class" parameter.'; return; }		

		if (!isset( $params['classfilename'] ))
			{ echo __METHOD__.' missing "classfilename" parameter.'; return; }		

		// pick up all the parameters that StubManager knows about directly;
		// the others will be passed to the 'Stub' class.
		foreach( self::$paramsList as $paramKey )
			if (isset( $params[$paramKey] ))
			{
				$liste[$paramKey] = $params[$paramKey];
				unset( $params[$paramKey] );
			}
			else
				$liste[$paramKey] = null;				
		
	
		// create a stub object.
		$cListe['object'] = new Stub( $liste['class'], 
								$liste['hooks'], 
								$liste['tags'],
								$liste['mgs'], 
								$liste['mws'], 
								$liste['nss'],
								$liste['enss'],
								$params			// pass along the remaining parameters
								);
 
		// merge with the other parameters.
		$dListe = array_merge( $liste, $cListe );

		self::$stubList[] = $dListe;
		
		// need to wait for the proper timing
		// to initialize things around.
		self::setupInit();

		global $wgAutoloadClasses;
		$wgAutoloadClasses[$liste['class']] = $liste['classfilename']; 
	}
	
	/**
	 * @deprecated
	 * $class: 		class of object to create when 'destubbing'
	 * $filename:		filename where class definition resides
	 * $i18nfilename:	filename where internationalisation messages reside
	 * $hooks:			array of hooks
	 * $logging:		if logging support is required
	 */
	public static function createStub(	$class, $filename, $i18nfilename = null, 
										$hooks, 
										$logging = false,
										$tags = null,		// parser 'tag' e.g. <php>
										$mgs  = null,		// parser function e.g. {{#addscriptcss}}
										$mws  = null,		// parser magic word e.g. {{CURRENTTIME}}
										$nss  = null		// namespaces as trigger
									)	{
									
		// need to wait for the proper timing
		// to initialize things around.
		self::setupInit();

		global $wgAutoloadClasses;
		$wgAutoloadClasses[$class] = $filename;
		
		self::$stubList[] = array(	'class'			=> $class, 
									'object' 		=> new Stub( $class, $hooks, $tags, $mgs, $mws, $nss ),
									'classfilename' => $filename,
									'i18nfilename'	=> $i18nfilename,
									'hooks'			=> $hooks,
									'logging'		=> $logging,
									'tags'			=> $tags,
									'mgs'			=> $mgs,
									'mws'			=> $mws,
									);
	}
	/**
	 * Register the state of an extension
	 * 
	 * @return void
	 * @param $classe string
	 * @param $state boolean
	 */	
	public static function registerState( $classe, $state )	 {
		foreach( self::$stubList as &$stub )
			if ( @$stub['class'] == $classe )
				$stub[ 'state' ] = $state;
	}
	/**
	 * Returns the current state of an extension
	 * 
	 * @return constant
	 * @param $classe string
	 */
	public static function getState( $classe ) {
		foreach( self::$stubList as &$stub )
			if ( @$stub['class'] == $classe )
				if ( isset( $stub['state'] ))
					return $stub[ 'state' ];
				
		return null;
	}
	/**
	 * Returns the icon corresponding to $state
	 * 
	 * @return $name string
	 * @param $state constant
	 */
	public static function getStateIcon( $state ) {
		if ( in_array( $state, self::$state_icons ))		
			return self::$state_icons[ $state ];
		return null;
	}
	/**
	 * 
	 * @return $message string
	 * @param $state constant
	 */
	public static function getStateMessage( $state ) {
		if ( in_array( $state, self::$state_messages ))		
			return self::$state_messages[ $state ];
		return null;
	}
	/**
	 * Configure Extension
	 * @return void
	 * @param $classe Object
	 * @param $parameter Object
	 * @param $value Object
	 */	
	public static function configureExtension( $classe, $parameter, $value ) {
		foreach( self::$stubList as &$stub )
			if (isset( $stub['class'] ))
				if ( $stub['class'] == $classe )
					if (isset( $stub[$parameter] ))
					{
						if (is_array($stub[ $parameter]) )
							$stub[$parameter][] = $value;
						else
							$stub[ $parameter ] = $value;
					}
					else
						$stub[ $parameter ] = $value;
	}
	public static function isExtensionRegistered( $classe ) {
		foreach( self::$stubList as &$stub )
			if (isset( $stub['class'] ))
				if ( $stub['class'] == $classe )
					return true;
					
		return false;
	}
	public static function version() {
		return self::version;
	}
	/**
	 * Returns ''true'' if the current version of StubManager
	 * is at least $version.
	 */
	public static function isAtLeast( $version ) {
		return version_compare( self::version, $version, ">=" );
	}	 
	public static function getRevision() {
		return self::getRevisionId( self::thisVersion );	
	}
	/**
		Create callback that will initialise all the stubs.
	 */
	private static function setupInit() {
		static $initHooked = false;
		if ($initHooked)
			return;
		$initHooked = true;
		
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = array( __CLASS__, 'setup' );		
	}
	public static function setup() {
		self::setupMessages();
		self::setupLogging();
		self::setupCreditsHook();
		self::callSetupMethods();
	}
	private static function callSetupMethods() {
		foreach( self::$stubList as $index => $e )
		{
			$obj = $e['object'];
			$obj->setup();
		}		
	}
	private static function setupLogging( )	{
		global $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActions;

		foreach( self::$stubList as $index => $e )
		{
			if ( !$e['logging'] )
				continue;
				
			$class = $e['class'];
			$log = $GLOBALS[ 'log'.$class ];
		
			$wgLogTypes  []     = $log;
			$wgLogNames  [$log] = $log.'logpage';
			$wgLogHeaders[$log] = $log.'logpagetext';

			$actions = null;
			if (isset( $GLOBALS[ 'act'.$class ]))
				$actions = $GLOBALS[ 'act'.$class ];
			if (!empty( $actions ))
				foreach( $actions as $action )
					$wgLogActions[$log.'/'.$action] = $log.'-'.$action.'-entry'; 
		}		
	}
	private static function setupMessages( ) {
		global $wgMessageCache;
		
		foreach( self::$stubList as $index => $e )
		{
			$i18nfilename = $e['i18nfilename'];
			if (!empty($i18nfilename))		
				require_once( $i18nfilename );
			else
				continue;
			
			$msg = $GLOBALS[ 'msg'.$e['class'] ];
	
			if (!empty( $msg ))
				foreach( $msg as $key => $value )
					$wgMessageCache->addMessages( $msg[$key], $key );		
		}
	}
	/**
	 * Special:Page hook setup
	 */
	private static function setupCreditsHook() {
	
		static $updateCreditsHooked = false;
		if ($updateCreditsHooked)
			return;
		$updateCreditsHooked = true;
		
		global $wgHooks;
		$wgHooks['SpecialVersionExtensionTypes'][] = __CLASS__.'::hUpdateExtensionCredits';
		
		// load all the extensions so they get a change to show their credits
		#foreach( self::$stubList as $index => $e )
			#echo $e['classfilename'].' ';
		#	require_once( $e['classfilename'] );
	}
	/**
	 * Lists all the extension registered through StubManager
	 * 
	 * @param $sp Object
	 * @param $ext Object
	 */
	public function hUpdateExtensionCredits( &$sp, &$ext ) {
	
		global $wgExtensionCredits;
		
		$result = null;
		
		// is sysop viewing the page?
		// alert if the template isn't available
		global $wgUser;
		$_groups = $wgUser->getGroups();
		$_isSysop = in_array( 'sysop', $_groups );
		
		// Template available?
		$tpl_present = false;
		$title_tpl = Title::newFromText( self::$_templateName );
		if ( is_object( $title_tpl ))
		{
			$article_tpl = new Article( $title_tpl );
			$tpl_present = $article_tpl->getId() != 0;
		}	
		
		$tpl_link = null;
		if ( $_isSysop && !$tpl_present )
			$tpl_link = "Customization template: [[".self::$_templateName."]]. ";
		
		// style formatting
		$first = true;
		
		if (!empty( self::$stubList ))
			foreach( self::$stubList as $index => $obj )
			{
				if ( !$first )
					$result .= ',  ';

				if ( $index % 4 == 0 && !$first )
					$result .= "<br/>";

				$state = self::getState( @$obj['class'] );
				$state_present = !is_null( $state );
				
				// extension state set && template present?
				// build the information
				if ($state_present && $tpl_present)
					$result .= "{{". self::$_templateName . "|$state}}";
					
				$result .= '['.self::MWbaseURI.'/Extension:'.$obj['class'].' '.$obj['class']."]";
					
				if ( $first === true )
					$first = false;
			}
				
		$result=trim($result);
		
		foreach ( $wgExtensionCredits[self::thisType] as $index => &$el )
			if (@isset($el['name']))
				if ($el['name']==self::thisName)
				{
					$desc = $el['description'];
					$desc = str_replace( '$1', $tpl_link, $desc );
					$el['description'] = $desc . $result.'.';
				}
		
		return true;
	}
	static function getRevisionId( $svnId=null ) {
		
		// fixed annoying warning about undefined offset.
		if ( $svnId === null || $svnId == ('$'.'Id'.'$' /* fool SVN */) )
			return null;
			
		// e.g. $Id: StubManager.php 1200 2008-10-10 02:13:37Z jeanlou.dupont $
		$data = explode( ' ', $svnId );
		return $data[2];
	}
	/**
	 * @deprecated
	 */
	static function getFullUrl( $filename )
	{ return 'http://www.bizzwiki.org/index.php?title=Filesystem:'.self::getRelativePath( $filename );	}

	static function getRelativePath( $filename ) {
		global $IP;
		$relPath = str_replace( $IP, '', $filename ); 
		return str_replace( '\\', '/', $relPath );    // at least windows & *nix agree on this!
	}
	/**
	 * @deprecated
	 */
	public static function processArgList( $liste, $getridoffirstparam=false )
	/*
	 * The resulting list contains:
	 * - The parameters extracted by 'key=value' whereby (key => value) entries in the list
	 * - The parameters extracted by 'index' whereby ( index = > value) entries in the list
	 */
	{
		if ($getridoffirstparam)   
			array_shift( $liste );
			
		// the parser sometimes includes a boggie
		// null parameter. get rid of it.
		if (count($liste) >0 )
			if (empty( $liste[count($liste)-1] ))
				unset( $liste[count($liste)-1] );
		
		$result = array();
		foreach ($liste as $index => $el )
		{
			$t = explode("=", $el);
			if (!isset($t[1])) 
				continue;
			$result[ "{$t[0]}" ] = trim( $t[1] );
			unset( $liste[$index] );
		}
		if (empty($result)) 
			return $liste;
			
		return array_merge( $result, $liste );	
	}
	public static function getParam( &$alist, $key, $index, $default )
	/*
	 *  Gets a parameter by 'key' if present
	 *  or fallback on getting the value by 'index' and
	 *  ultimately fallback on default if both previous attempts fail.
	 */
	{
		if (array_key_exists($key, $alist) )
			return $alist[$key];
		elseif (array_key_exists($index, $alist) && $index!==null )
			return $alist[$index];
		else
			return $default;
	}
	public static function initParams( &$alist, &$templateElements, $removeNotInTemplate = true )
	{
		// v1.92 feature.
		if ($removeNotInTemplate)
			foreach( $templateElements as $index => &$el )
				if ( !isset($alist[ $el['key'] ]) )
					unset( $alist[$el['key']] );
		
		foreach( $templateElements as $index => &$el )
			$alist[$el['key']] = self::getParam( $alist, $el['key'], $el['index'], $el['default'] );
	}
	/**
	 * @deprecated 
	 */
	public function formatParams( &$alist , &$template )
	// look at yuiPanel extension for usage example.
	// $alist = { 'key' => 'value' ... }
	{
		foreach ( $alist as $key => $value )
			// format the entry.
			self::formatParam( $key, $value, $template );
	}
	/**
	 * @deprecated
	 */
	private static function formatParam( &$key, &$value, &$template )
	{
		$format = self::getFormat( $key, $template );
		if ($format !== null )
		{
			switch ($format)
			{
				case 'bool':   $value = (bool) $value; break; 
				case 'int':    $value = (int) $value; break;
				default:
				case 'string': $value = (string) $value; break;					
			}			
		}
	}
	public static function getFormat( &$key, &$template )
	{
		$format = null;
		foreach( $template as $index => &$el )
			if ( $el['key'] == $key )
				$format  = $el['format'];
			
		return $format;
	}
	public static function checkPageEditRestriction( &$title )
	// v1.1 feature
	// where $title is a Mediawiki Title class object instance
	{
		$proceed = false;
  
		$state = $title->getRestrictions('edit');
		foreach ($state as $index => $group )
			if ( $group == 'sysop' )
				$proceed = true;

		return $proceed;		
	} 
	public static function getArticle( $article_title )
	{
		$title = Title::newFromText( $article_title );
		  
		// Can't load page if title is invalid.
		if ($title == null)	return null;
		$article = new Article($title);

		return $article;	
	}
	
	static function isSysop( $user = null ) // v1.5 feature
	{
		if ($user == null)
		{
			global $wgUser;
			$user = $wgUser;
		}	
		return in_array( 'sysop', $user->getGroups() );
	}
	
} // end class




// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// STUB class
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



class Stub
{
	static $hook_prefix	= 'h';
	static $tag_prefix	= 'tag_';
	static $mw_prefix	= 'MW_';
	static $mg_prefix	= 'mg_';

	static $done = false;
	
	var $classe;
	var $obj;
	
	var $hooks;
	var $tags;
	var $mgs;
	var $mws;
	var $nss;
	var $enss;

	public function __construct( &$class, 
								&$hooks, 
								&$tags = null, 
								&$mgs = null, 
								&$mws = null, 
								$nss = null,
								$enss = null,
								$params = null )
	{
		$this->setupHooks( $hooks );
		$this->tags = $tags;
		$this->mgs  = $mgs;
		$this->mws  = $mws;
		$this->nss  = $nss;
		$this->enss = $enss;		
		$this->params  = $params;
		
		if ( !empty( $mgs) || !empty( $mws) )
			$this->setupLanguageGetMagicHook();

		// don't create the object just yet!
		$this->classe = $class;
		$this->obj = null;
	}
	/**
		called in the same timing as $wgExtensionFunctions array is processed
	 */
	public function setup()
	{
		$this->setupTags( $this->tags );
		$this->setupMGs( $this->mgs );
		$this->setupMWs( $this->mws );
	}

	private function setupHooks( /*&*/$hooks )
	{
		if (empty( $hooks ))
			return;
	
		// get rid of the hook prefix if the user forgot about it.
/*		
		foreach( $hooks as &$hook )
			if ( strncmp( self::$hook_prefix , $hook, strlen(self::$hook_prefix) ) == 0)
				$hook = substr( $hook, strlen(self::$hook_prefix) );
*/
		
		global $wgHooks;
		foreach( $hooks as $hook )
			$wgHooks[ $hook ][] = array( &$this, self::$hook_prefix.$hook );
	}
	private function setupTags( /*&*/ $tags )
	{
		if (empty( $tags ))
			return;
			
		global $wgParser;
		foreach($tags as $index => $key)
			$wgParser->setHook( "$key", array( $this, self::$tag_prefix.$key ) );
	}
	private function setupMGs( /*&*/ $mgs )
	{
		if (empty( $mgs ))
			return;
			
		global $wgParser;
		foreach($mgs as $index => $key)
			$wgParser->setFunctionHook( "$key", array( $this, self::$mg_prefix.$key ) );			
	}
	private function setupMWs( /*&*/ $mws )
	{
		if (empty( $mws ))
			return;
			
		global $wgParser;
		foreach($mws as $index => $key)
			$wgParser->setFunctionHook( "$key", array( $this, self::$mw_prefix.$key ) );	
	}
	private function setupLanguageGetMagicHook()
	{
		global $wgHooks;				
		$wgHooks['LanguageGetMagic'            ][] = array( $this, 'hookLanguageGetMagic'             );
		$wgHooks['MagicWordMagicWords'         ][] = array( $this, 'hookMagicWordMagicWords'          );
		$wgHooks['MagicWordwgVariableIDs'      ][] = array( $this, 'hookMagicWordwgVariableIDs'       );
		$wgHooks['ParserGetVariableValueSwitch'][] = array( $this, 'hookParserGetVariableValueSwitch' );			
	}
	public function hookLanguageGetMagic( &$magicwords, $langCode )
	{
		// parser functions.
		if (!empty( $this->mgs ))		
			foreach($this->mgs as $index => $key )
				$magicwords [$key] = array( 0, $key );

		// magic words.
		if (!empty( $this->mws ))				
			foreach($this->mws as $index => $key )
				$magicwords [ defined($key) ? constant($key):$key ] = array( 0, $key );

		return true;
	}
	public function hookMagicWordMagicWords( &$mw )
	{
		if (!empty( $this->mws ))		
			foreach ( $this->mws as $index => $key )
				$mw[] = $key;

		return true;
	} 
	public function hookMagicWordwgVariableIDs( &$mw )
	{
		if (!empty( $this->mws ))
			foreach ( $this->mws as $index => $key )
				$mw[] = constant( $key  );

		return true;
	} 
	public function hookParserGetVariableValueSwitch( &$parser, &$varCache, &$id, &$ret )
	{
		if (empty( $this->mws )) 
			return true;

		// when called through {{magic word here}}
		// will call the method "MW_magic_word"
		if ( in_array( $id, $this->mws ) )
		{
			$method= self::$mw_prefix.$id;	
			$this->$method( $parser, $varCache, $ret );	
		}
		return true;
	}
	/**
		If the extension registered for 'namespace triggering',
		then check if we are asked to execute a hook that
		falls in the namespace list that the extension provided.
		
		Exclude namespace first		
	 */
	private function checkNss( &$method, &$args )
	{
		global $wgTitle;
		if (!is_object( $wgTitle ))
			return true;

		if ( !empty( $this->enss ))
			if ( in_array( $wgTitle->getNamespace(), $this->enss ) )
				return false; // stop processing
		
		if ( !empty($this->nss) )	// if none provided, act as normal
			if ( !in_array( $wgTitle->getNamespace(), $this->nss ) )
				return false; // stop processing
					
		#echo ' classe:'.$this->classe.' method:'.$method."\n";
		
		// means continue processing.
		return true;
	}
	
	/**
	 * intercept all methods called
	 * and instantiate the necessary object... only once. 
	 */
	function __call( $method, $args ) {
	
		// Check triggers
		if (!$this->checkNss( $method, $args ))
			return true;
			
		if ( $this->obj === null )
			$obj = $this->obj = new $this->classe( $this->params );  // un-stub
		else
			$obj = $this->obj;
		
		/*
		switch ( count($args) )
		{
			case 0:
				return $obj->$method( );
			case 1:
				return $obj->$method( $args[0] );
			case 2:
				return $obj->$method( $args[0], $args[1] );
			case 3:
				return $obj->$method( $args[0], $args[1], $args[2] );
			case 4:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3] );
			case 5:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4] );
			case 6:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5] );
			case 7:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6] );
			case 8:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7] );			
			case 9:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8] );			
			case 10:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9] );			
			case 11:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9], $args[10] );			
			case 12:
				return $obj->$method( $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9], $args[10], $args[11]   );			
		}
		throw new MWException( __CLASS__.": too many arguments to method called in ".__METHOD__ );
		*/
		return call_user_func_array( array( $obj, $method), $args );
	}

} // end class Stub



// Perform auto-discovery of [[Extension:ExtensionManager]]
// --------------------------------------------------------
StubManager::$edir = realpath( dirname( dirname(__FILE__) ) );
if (file_exists( StubManager::$edir.'/ExtensionManager/ExtensionManager.php'))
	include StubManager::$edir.'/ExtensionManager/ExtensionManager.php';



// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// ExtHelper Class
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




/**
 * Some helper functions for extensions
 */
class ExtHelper {
	/**
	 * Retrieves the specified list of parameters from the list.
	 * Uses the ''l'' parameter from the reference list.
	 */
	public static function buildList( &$liste, &$ref_liste ) {
		if (empty( $liste ))
			return null;
			
		$result = '';
		// only pick the key:value pairs that have been
		// explictly marked using the 'l' key in the
		// reference list.
		foreach( $liste as $key => &$value )
		{
			$key = trim( $key );
			$val = trim( $value );
			if ( isset( $ref_liste[ $key ] ) )
				if ( $ref_liste[ $key ]['l'] === true )
					$result .= " $key='$val'";
		}
		return $result;		
	}
	/**
	 * Sanitize the parameters list. 
	 * Just keeps the parameters defined in the reference list.
	 */
	public static function doListSanitization( &$liste, &$ref_liste ) {
		if (empty( $liste ))
			return array();

		// first, let's make sure we only have valid parameters
		$new_liste = array();
		foreach( $liste as $key => &$value )
			if (isset( $ref_liste[ $key ] ))
				$new_liste[ $key ] = $value;
				
		// then make sure we have all mandatory parameters
		foreach( $ref_liste as $key => &$instructions )
			if ( $instructions['m'] === true )
				if ( !isset( $liste[ $key ] ))
					return $key;
					
		// finally, initialize to default values the missing parameters
		foreach( $ref_liste as $key => &$instructions )
			if ( $instructions['d'] !== null )
				if ( !isset( $new_liste[ $key ] ))
					$new_liste[ $key ] = $instructions['d'];
				
		return $new_liste;
	}
	/**
	 * Performs various sanitization.
	 * Only valid parameters should end-up here.
	 */
	public static function doSanitization( &$liste, &$ref_liste ) {
		if (empty( $liste ))
			return null;
			
		foreach( $liste as $key => &$value )
		{
			// Remove leading & trailing double-quotes
			if (isset( $ref_liste[ $key ]['dq'] ))
					if ( $ref_liste[ $key ]['dq'] === true )
					{
						$value = ltrim( $value, "\" \t\n\r\0\x0B" );
						$value = rtrim( $value, "\" \t\n\r\0\x0B" );
					}

			// Remove leading & trailing single-quotes
			if (isset( $ref_liste[ $key ]['sq'] ))
					if ( $ref_liste[ $key ]['sq'] === true )
					{
						$value = ltrim( $value, "\' \t\n\r\0\x0B" );
						$value = rtrim( $value, "\' \t\n\r\0\x0B" );
					}
						

			// HTML sanitization
			if (isset( $ref_liste[ $key ]['s'] ))
				if ( $ref_liste[ $key ]['s'] === true )
					$value = htmlspecialchars( $value );
		}
	}
	/**
	 * Checks for if the $liste contains parameters marked as ''r'' (i.e. restricted)
	 *
	 * @return bool null for empty list
	 * @return string restricted key name
	 * @return bool false if no restricted parameter found
	 */
	public static function checkListForRestrictions( &$liste, &$ref_liste )	{
		if (empty( $liste ))
			return null;

		foreach( $liste as $key => &$value )
		{
			// HTML sanitization
			if (isset( $ref_liste[ $key ]['r'] ))
				if ( $ref_liste[ $key ]['r'] === true )
					return $key;							
			
		}
		
		return false;		
	}
}// end class ExtHelpers


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// ExtImages Class
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


class ExtImages {
	// Uses CoralCDN
	static $_baseURL = "http://mediawiki.googlecode.com.nyud.net/svn/resources/images/";
	
	/**
	 * Returns a formed HTML IMG tag
	 * 
	 * @return $html string
	 * @param $icon string
	 * @param $params mixed: array[optional] / string for title
	 */
	public function getIconImgTag( $icon, $params = array() ) {
		$path = self::$_baseURL . $icon;

		$paramsList = null;
		
		if ( is_array( $params ) )
			foreach( $params as $key => $value )
				$paramsList .= "$key='$value'";
		elseif ( is_string( $params ) && (!empty( $params )) )
			$paramsList = "title='$params'";
		
		return "<img src='$path' $paramsList />";
	}
	
} // end class ExtIcons

$wgExtensionCredits[StubManager::thisType][] = array( 
	'name'    		=> StubManager::thisName,
	'version' 		=> StubManager::version,
	'author'  		=> 'Jean-Lou Dupont',
	'description'	=> 'Provides stubbing facility for extensions handling rare events. $1Extensions registered:<br/>', 
	'url'			=> 'http://mediawiki.org/wiki/Extension:StubManager',				
);

//</source>
