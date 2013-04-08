<?php
/**
 * @author Jean-Lou Dupont
 * @package geshi
 */
//<source lang=php>

$wgExtensionCredits['other'][] = array( 
	'name'        	=> 'geshi', 
	'version'     	=> '1.0.0',
	'author'      	=> 'Jean-Lou Dupont', 
	'description' 	=> 'Handles generic syntax highlighting',
	'url' 			=> 'http://mediawiki.org/wiki/Extension:Geshi',			
);
StubManager::createStub(	'geshiClass', 
							dirname(__FILE__).'/geshi.body.php',
							null,
							array( 'SyntaxHighlight' ),
							false,	// no need for logging support
							array( 'geshi', 'source', 'php','js', 'css' ),	// tags
							array( 'source' ),	// parser functions
							null	// no magic words
						 );
//</source>