<?php

// For requiring the PEAR libraries
ini_set( 'include_path', ini_get( 'include_path' ) . PATH_SEPARATOR . 'extension/eztrackback/lib' );

require_once 'Services/Trackback.php';

class eZTrackbackType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'eztrackback';

    public function __construct()
    {
        parent::__construct( self::WORKFLOW_TYPE_STRING, ezi18n( 'extension/eztrackback', 'Trackback' ) );
        $this->setTriggerTypes( array( 'content' => array( 'publish' => array ( 'after' ) ) ) );
    }

    public function execute( $process, $event )
    {
        $trackbackINI = eZINI::instance( 'trackback.ini' );
        $ini = eZINI::instance();
        
        $trackbackAttribute = $trackbackINI->variable( 'TrackbackSettings', 'TrackbackAttribute' );
        $fetchLines = (int)$trackbackINI->variable( 'TrackbackSettings', 'FetchLines' );
        $siteURL = $ini->variable( 'SiteSettings','SiteURL' );
        $blogClassIdentifier = $trackbackINI->variable( 'TrackbackSettings', 'BlogClassIdentifier' );
        
        $titleAttribute = $trackbackINI->variable( 'BlogClassSettings', 'TitleAttribute' );
        $excerptAttribute = $trackbackINI->variable( 'BlogClassSettings', 'ExcerptAttribute' );
        
        $parameters = $process->attribute( 'parameter_list' );
        $objectID = $parameters['object_id'];
        
        $object = eZContentObject::fetch( $objectID );
        $classIdentifier = $object->attribute( 'class_identifier' );
           
        if ( $classIdentifier === $blogClassIdentifier )
        {
            $dataMap = $object->dataMap();

            // Prepare data for trackback
            $data['id'] = $object->attribute( 'main_node_id' );
            $data['title'] = strip_tags( $dataMap[$titleAttribute]->DataText );
            $data['excerpt'] = preg_replace('/\s+/', ' ', strip_tags( $dataMap[$excerptAttribute]->DataText ) );
            $data['excerpt'] = ( strlen( $data['excerpt'] ) > 200 ) ? substr( $data['excerpt'], 0, 197 ) . '...' : $data['excerpt'];
            $data['blog_name'] = $trackbackINI->variable( 'TrackbackSettings', 'BlogName' );

            $trackback = Services_Trackback::create( $data, array( 'fetchlines' => $fetchLines, 
                                                                   'httprequest' => array( 'useragent' => 'eZ Publish' ) ) );

            $regex = '/((http|https|ftp):\/\/|www)[a-z0-9\-\._]+\/?[a-z0-9_\.\-\?\+\/~=&#;,]*[a-z0-9\/]{1}/si';
            
            // Match all URLs in the entry
            if( 0 !== preg_match_all( $regex, $dataMap[$trackbackAttribute]->DataText, $matches ) )
            {
                // Iterate through URLs from the entry
                foreach( $matches[0] as $match )
                {
                    $trackback->set( 'url', $match );

                    if ( $trackback->autodiscover( ) === true )
                    {
                        if ( PEAR::isError( $res = $trackback->send( $data ) ) )
                            eZDebug::writeError( $res->getMessage() );
                    }
                }
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType( eZTrackbackType::WORKFLOW_TYPE_STRING, 'eZTrackbackType' );

?>
