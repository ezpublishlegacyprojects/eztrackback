<?php 
//
// Created on: <01-Jul-2005 10:55:01 ls@ez.no>
// Modified on: <10-Feb-2006 10:55:01 ls@ez.no>
//
// Copyright (C) 1999-2006 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'kernel/common/template.php' );
include_once( 'lib/ezutils/classes/ezoperationhandler.php' );
include_once( 'kernel/classes/ezcontentobjecttreenode.php' );
include_once( 'lib/ezutils/classes/ezdebug.php' );

// For requiring the PEAR libraries
ini_set( 'include_path', ini_get( 'include_path' ) . PATH_SEPARATOR . 'extension/eztrackback/lib' );
require_once 'Services/Trackback.php';

$res = false;

$Module =& $Params['Module'];

//Weblog entry ID, will be use as perent for new trackback object

if ( is_numeric( $Params['ID'] ) ) 
{
	$ID = $Params['ID'];	
}
else 
{
	$ID = 0;	
}

$trackback = Services_Trackback::create( array( 'id' => 'none' ) );
if ( PEAR::isError( $ret = $trackback->receive(  ) ) ) {
    // Trackback retrieval failed! Show an error.
    $res = $trackback->getResponseError( $ret->getMessage(  ), 1 );
}

if ( $ID == 0 )
    $res = $trackback->getResponseError( 'Trackbacks not possible here.', 1 );

if ( !$res ) {

    $title = $trackback->get( 'title' );
    $url = $trackback->get( 'url' );
    $excerpt = $trackback->get( 'excerpt' );
    $blogName = $trackback->get( 'blog_name' );
    
    $existingObject = eZPersistentObject::fetchObject( eZContentObject::definition(), null,
                                                        array( 'remote_id' => 'Trackback_'.$ID.'_'.md5( $url ) ) );
	$createNew = true;
	
	if ( $existingObject != null )
	{
		$createNew = false;
	}
    
	if ( $createNew )
	{
		
		$node =& eZContentObjectTreeNode::fetch( $ID );
		
		if ( $node )
			$object = $node->attribute( 'object' );
			
		if ( $object )
			$userID = $object->attribute( 'owner_id' );

		$parentNodeID = $ID;
		$class = eZContentClass::fetchByIdentifier( 'trackback' );
		$parentContentObjectTreeNode = eZContentObjectTreeNode::fetch( $parentNodeID );
		$parentContentObject = $parentContentObjectTreeNode->attribute( 'object' );
		$sectionID = $parentContentObject->attribute( 'section_id' );
    
		$db =& eZDB::instance();
    	$db->begin();
    	
		$contentObject =& $class->instantiate( $userID, $sectionID );
		$contentObjectID = $contentObject->attribute( 'id' );

		$nodeAssignment =& eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
		'contentobject_version' => $contentObject->attribute( 'current_version' ),
		'parent_node' => $parentContentObjectTreeNode->attribute( 'node_id' ),
		'is_main' => 1 ) );
		$nodeAssignment->store();

		$contentObjectAttributes =& $contentObject->contentObjectAttributes();

		$loopLenght = count( $contentObjectAttributes );

		for( $i = 0; $i < $loopLenght; $i++ )
		{
			switch( $contentObjectAttributes[$i]->attribute( 'contentclass_attribute_identifier' ) )
			{
				case 'title':
					$contentObjectAttributes[$i]->setAttribute( 'data_text', $title );
					$contentObjectAttributes[$i]->store();
					break;
				case 'blog_name':
					$contentObjectAttributes[$i]->setAttribute( 'data_text', $blogName );
					$contentObjectAttributes[$i]->store();
					break;
				case 'excerpt':
					$contentObjectAttributes[$i]->setAttribute( 'data_text', $excerpt );
					$contentObjectAttributes[$i]->store();
					break;
				case 'url':
					$linkID =& eZURL::registerURL( $url );
					$contentObjectAttributes[$i]->setAttribute( 'data_text', '' );
					$contentObjectAttributes[$i]->setAttribute( 'data_int', $linkID );
					$contentObjectAttributes[$i]->store();
					break;
			}
		}
		$contentObject->setAttribute( 'remote_id', 'Trackback_'.$ID.'_'.md5( $url ) );
		$contentObject->store();

		$contentObject->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
		$contentObject->store();
  		$db->commit();
  		  
		$operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID, 'version' => 1 ) );
	}
}

// Trackback stored successfully. Output success message.
if ( !$res ) {
    $res = $trackback->getResponseSuccess( );
}

echo $res;

eZExecution::cleanExit();

?>