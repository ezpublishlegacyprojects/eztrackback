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

include_once( 'kernel/classes/ezworkflowtype.php' );

// For requiring the PEAR libraries
ini_set( 'include_path', ini_get( 'include_path' ) . PATH_SEPARATOR . 'extension/eztrackback/lib' );

require_once 'Services/Trackback.php';

define( 'EZ_WORKFLOW_TYPE_TRACKBACK', 'eztrackback' );

class eZTrackbackType extends eZWorkflowEventType
{

    function eZTrackbackType()
    {
        $this->eZWorkflowEventType( EZ_WORKFLOW_TYPE_TRACKBACK, ezi18n( 'kernel/workflow/event', 'Trackback' ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array ( 'after' ) ) ) );
    }

    function execute( &$process, &$event )
    {
    	$trackbackINI =& eZINI::instance( 'trackback.ini' );
		$ini =& eZINI::instance( 'site.ini' );
		
    	$trackbackContentClassAttribute = $trackbackINI->variable( 'TrackbackSettings', 'TrackbackContentClassAttribute' );
    	$fetchLines = (int)$trackbackINI->variable( 'TrackbackSettings', 'FetchLines' );
    	$siteURL = $ini->variable( 'SiteSettings','SiteURL' );
    	$weblogClassIdentifier = $trackbackINI->variable( 'TrackbackSettings', 'WeblogClassIdentifier' );
    	
        $parameters = $process->attribute( 'parameter_list' );
		$objectID = $parameters['object_id'];
		
        $object =& eZContentObject::fetch( $objectID );
        
       	$classIdentifier = $object->attribute( 'class_identifier' );
       	
        if ( $classIdentifier == $weblogClassIdentifier ) 
       	{
            $dataMap = & $object->attribute( 'data_map' );
            
            // Prepare data for trackback
            $data['id']         = $object->attribute( 'main_node_id' );
            $data['title']      = strip_tags( $dataMap['title']->DataText );
            $data['excerpt']    = preg_replace('/\s+/', ' ', strip_tags( $dataMap['message']->DataText ) );
            $data['excerpt']    = ( strlen( $data['excerpt'] ) > 200 ) ? substr( $data['excerpt'], 0, 197 ) . '...' : $data['excerpt'];
            $data['url']        = 'http://' . $siteURL . '/content/view/full/' . $object->attribute( 'main_node_id' );
            $data['blog_name']  = $trackbackINI->variable( 'TrackbackSettings', 'WeblogName' );

            $trackback = Services_Trackback::create( $data, array( 'fetchlines' => $fetchLines, 'httpRequest' => array( 'useragent' => 'eZ publish' ) ) );
            $regex = '/((http|https|ftp):\/\/|www)[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#;,]*[a-z0-9\/]{1}/si';
            
            // Match all URLs in the entry
            if (0 !== preg_match_all($regex, $dataMap[$trackbackContentClassAttribute]->DataText, $matches)) {
            	// Iterate through URLs from the entry

            	foreach ($matches[0] as $match) {

            		$trackback->set( 'url', $match );
            		
					if ( $trackback->autodiscover( ) === true ) 
					{
            			if ( PEAR::isError( $res = $trackback->send( $data ) ) )
            				eZDebug::writeError( $res->getMessage( ) );
					}
            	}
            }
        }
        return EZ_WORKFLOW_TYPE_STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerType( EZ_WORKFLOW_TYPE_TRACKBACK, 'eztrackbacktype' );

?>
