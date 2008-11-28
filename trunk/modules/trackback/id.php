<?php

// For requiring the PEAR libraries
ini_set( 'include_path', ini_get( 'include_path' ) . PATH_SEPARATOR . 'extension/eztrackback/lib' );

require_once 'Services/Trackback.php';

$res = false;
$parentNodeID = 0;

$Module = $Params['Module'];

//Blog entry ID, will be use as perent for new trackback object

if ( isset( $Params['ID'] ) && is_numeric( $Params['ID'] ) )
    $parentNodeID = $Params['ID'];

$trackback = Services_Trackback::create( array( 'id' => 'none' ) );

if ( PEAR::isError( $ret = $trackback->receive(  ) ) )
{
    // Trackback retrieval failed! Show an error.
    $res = $trackback->getResponseError( $ret->getMessage(  ), 1 );
}

if ( $parentNodeID == 0 )
    $res = $trackback->getResponseError( 'Trackbacks not possible here.', 1 );

if ( !$res ) {

    $title = $trackback->get( 'title' );
    $url = $trackback->get( 'url' );
    $excerpt = $trackback->get( 'excerpt' );
    $blogName = $trackback->get( 'blog_name' );
    
    $existingObject = eZPersistentObject::fetchObject( eZContentObject::definition(), null, array( 'remote_id' => 'Trackback_'.$parentNodeID.'_'.md5( $url ) ) );
    $createNew = true;
    
    if( $existingObject != null )
        $createNew = false;

    if( $createNew )
    {
        $parentNode = eZContentObjectTreeNode::fetch( $parentNodeID );

        if( $parentNode )
            $parentObject = $parentNode->attribute( 'object' );
            
        if( $parentObject )
        {
            $userID = $parentObject->attribute( 'owner_id' );
            $sectionID = $parentObject->attribute( 'section_id' );
        }

        $class = eZContentClass::fetchByIdentifier( 'trackback' );
    
        $db = eZDB::instance();
        $db->begin();
        
        $contentObject = $class->instantiate( $userID, $sectionID );
        $contentObjectID = $contentObject->attribute( 'id' );

        $nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                           'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                           'parent_node' => $parentNode->attribute( 'node_id' ),
                                                           'is_main' => 1 ) );
        $nodeAssignment->store();

        $dataMap = $contentObject->dataMap();

        $dataMap['title']->setAttribute( 'data_text', $title );
        $dataMap['title']->store();

        $dataMap['blog_name']->setAttribute( 'data_text', $blogName );
        $dataMap['blog_name']->store();

        $dataMap['excerpt']->setAttribute( 'data_text', $excerpt );
        $dataMap['excerpt']->store();

        $linkID = eZURL::registerURL( $url );
        $dataMap['url']->setAttribute( 'data_text', '' );
        $dataMap['url']->setAttribute( 'data_int', $linkID );
        $dataMap['url']->store();

        $contentObject->setAttribute( 'remote_id', 'Trackback_'.$parentNodeID.'_'.md5( $url ) );
        $contentObject->store();

        $contentObject->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
        $contentObject->store();
        $db->commit();
            
        $operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObjectID, 'version' => 1 ) );
    }
}

// Trackback stored successfully. Output success message.
if ( !$res )
    $res = $trackback->getResponseSuccess();

echo $res;

eZExecution::cleanExit();

?>