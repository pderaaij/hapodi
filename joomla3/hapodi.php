<?php

defined('_JEXEC') or die;
require_once __DIR__ . '/collector.php';

/**
 * Plugin that enables displaying scores from handbal.nl
 */
class PlgContentHadopi extends JPlugin {
    
    const TAG_PATTERN = '/{hapodi id="(.*)"}/s';
    
    /**
     *
     * @var Collector
     */
    private $collector;
    
    public function __construct(&$subject, $config = array()) {
        parent::__construct($subject, $config);
        
        $this->collector = new Collector();
    }

    /**
     *
     * @param   string   $context  The context of the content being passed to the plugin.
     * @param   mixed    &$row     An object with a "text" property or the string to be cloaked.
     * @param   mixed    &$params  Additional parameters. See {@see PlgContentEmailcloak()}.
     * @param   integer  $page     Optional page number. Unused. Defaults to zero.
     *
     * @return  boolean	True on success.
     */
    public function onContentPrepare($context, &$row, &$params, $page = 0) {
        if (is_object($row)) {
            return $this->_display($row->text, $row->id);
        }

        return $this->_display($row, $row->id);
    }

    /**
     * 
     * @param type $text
     * @param type $params
     * @param type $articleId
     */
    private function _display(&$text, $articleId) {
        if ($this->tagIsAvailable($text)) {
            $poules = $this->extractPouleId($text);
            
            if( is_array($poules) ) {
                foreach ($poules as $competitionId) {
                    $this->displayPoule($competitionId, $text, $articleId);
                }
            }
        }
        
    }
    
    /**
     * 
     * @param type $content
     * @return type
     */
    private function tagIsAvailable($content) {
        return preg_match_all(self::TAG_PATTERN, $content) === 1;
    }
    
    /**
     * 
     * @param type $content
     * @return null
     */
    private function extractPouleId($content) {
        preg_match_all(self::TAG_PATTERN, $content, $matches);
        
        if (isset($matches[1]) && count($matches[1]) > 0) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * 
     * @param type $competitionId
     * @param type $text
     * @param type $articleId
     */
    private function displayPoule($competitionId, &$text, $articleId) {
        $data = $this->fetchData($competitionId);
        $pouleBlock = $this->buildBlock($data, $articleId);
        
        $this->replaceTag($text, $pouleBlock, $competitionId);
    }
    
    /**
     * Build the block
     * 
     * @param type $externalContent
     * @param type $articleId
     * @return string
     */
    private function buildBlock($externalContent, $articleId) {
        $baseRoute = JURI::current();
        
        $data = '<h3>Programma, uitslagen en stand</h3>';
        $data .= '<div class="competitionLinks">
            <a href="'. JRoute::_($baseRoute . '?ha=standings') . '">Stand</a> |
            <a href="'. JRoute::_($baseRoute . '?ha=results') . '">Resultaten</a> |
            <a href="'. JRoute::_($baseRoute . '?ha=program') . '">Programma</a>
        </div>
        <div id="dataBlock">';
        $data .= $externalContent;
        $data .= '</div>';
        
        return $data;
    }
    
    /**
     * Replace the hapido tag with the created content
     * @param type $text
     * @param type $newContent
     * @param type $competitionId
     */
    private function replaceTag(&$text, $newContent, $competitionId) {
       $text = preg_replace('/{hapodi id="'.$competitionId .'"}/s', $newContent, $text);
    }
    
    /**
     * Fetch the right data from the collector based on the requested action
     * 
     * @param type $competitionId
     * @return type
     */
    private function fetchData($competitionId) {
        $action = JRequest::getWord('ha');
        
        switch($action) {
            case 'program': {
                return $this->collector->collectProgram($competitionId);
            }
            
            case 'results': {
                return $this->collector->collectResults($competitionId);
            }
        }
        
        return $this->collector->collectRanking($competitionId);
    }

}