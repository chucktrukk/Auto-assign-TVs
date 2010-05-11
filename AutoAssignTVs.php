<?php

/*
Plugin Name:    AutoAssignDefaultTVs
By:             ChuckTrukk www.prowebscape.com
Released on:    September 17, 2008
Version:        1.0
Description:    Automatically assign TVs from a specified category (or categories) to a new (or updated) template

Installation:
    Create a new plugin named AutoAssignDefaultTVs
    copy-paste this text into the Plugin code
    copy-paste the following into Plugin configuration
        &category=Categories;text;Default  &sortBy=Sort By;text;id ASC &doLog=Log Events to modx?;list;yes,no;yes &updateMode=Assign TVs on template update?;list;yes,no;yes
    System Events: [x] onTempFormSave

    Input:
        category    - Comma seperated list of categories your TVs are in
                    default:  'Default'
                    examples: 'Default'
                    examples: 'Default,Store'
                    
        sortBy      - Can be any column site_tmplvars such as rank ASC, rank DESC, id ASC, id DESC, etc.
                    default:  'id ASC'
                    examples: 'id DESC'
                    examples: 'rank ASC'
                    
        logResults  - Do you want to log the results to Reports -> System Events
                    default:  'no'
                    
        updateMode  - Do you want this plugin to run when an already created template is saved/updated?
                    default:  'no'
    Output:
        if logResults is yes, the results will be listed in the Manager at Reports -> System Events with the title 'PLUGIN: Automatically assign TVs to template'
*/
$e = &$modx->Event;

switch ($e->name) {
    case "OnTempFormSave":

        $category   = isset($category)   ? $category   : 'Default';
        $sortBy     = isset($sortBy)     ? $sortBy     : 'id ASC';    
        $logResults = isset($logResults) ? $logResults : 'yes';
        $updateMode = isset($updateMode) ? $updateMode : 'no';
        $templateID = isset($templateID) ? $templateID : $id;
            
        if (($updateMode == 'yes') || ($mode == 'new'))
        {
        
            $categories = explode(',', $category);
            
            foreach ($categories as $category)
            {
                $string .= " `category` RLIKE BINARY '$category' OR ";
            }
            
            $where =  substr($string, 0, -4);
            $result = $modx->db->select('id', $modx->getFullTableName("categories"),$where );
        
            if( $modx->db->getRecordCount( $result ) >= 1 ) {
                while( $row = $modx->db->getRow( $result ) ) {
                    $tvs_string .= " `category` = '" . $row['id'] . "' OR ";
                }
        
                $tvs_string = substr($tvs_string, 0, -4);;
            
                $default_tvs = getDefaultTVs($tvs_string, $sortBy);
                $message = insertDefaultTVs($default_tvs, $templateID);
        
            } else
            {
               $message .= 'None of the Categories exist';
            }
        
            if ($logResults == 'yes') {
                $modx->logEvent(20,1,$message,'PLUGIN: Automatically assign TVs to template');
            }
        }
        break;
        
    default:
        return;
        break;
}

function getDefaultTVs($tvs_string, $sortBy)
{
    global $modx;
    
    $result = $modx->db->select('id', $modx->getFullTableName("site_tmplvars"), $tvs_string, $sortBy);
    if( $modx->db->getRecordCount( $result ) >= 1 ) {
        while( $row = $modx->db->getRow( $result ) ) {
            $results[] = $row['id'];
        }
        return $results;
    } else
    {
       $message = 'None of the Categories exist';
       $modx->logEvent(20,1,$message,'Assign default TVs to new Template');
       exit();
    }
}   

    
function insertDefaultTVs($results, $templateID)
{
    global $modx;
    
    foreach ($results as $result)
    {
        $fields =   array( 'tmplvarid'  => $result,
                           'templateid' => $templateID,
                           );
        $check_if_exists = $modx->db->getValue( $modx->db->select( 'count(*)', $modx->getFullTableName("site_tmplvar_templates"), "`tmplvarid` = '$result' AND `templateid` = '$templateID'" ) );
        if ($check_if_exists < 1)
        {
            if($modx->db->insert( $fields, $modx->getFullTableName("site_tmplvar_templates")) == '1')
            {
                $insert_result = "<strong>successfully</strong>";
            } else
            {
                $insert_result = "<strong>NOT SUCCESSFUL</strong>";
            }
            
            $output .= "TV $result was $message_result assigned to the Template $templateID<br/><br/>";
        } else {
            $output .= "TV $result was <strong>ALREADY ASSIGNED and NOT INSERTED</strong> to the Template $templateID<br/><br/>";
        }
    }
    
    return $output;

}