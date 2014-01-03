<?php

/**
 * Description of Collector
 *
 * @author Paul de Raaij <paul@paulderaaij.nl>
 */
class Collector {

    const BASE_URL = "http://www.handbal.nl/ajax/competition/";
    const CACHE_LIFETIME_MILLISECS = 3000;

    private $cachePath = null;

    public function __construct() {
        $this->cachePath = JPATH_SITE . '/cache/hapodi/competitions/';
    }

    public function collectRanking($competitionId) {
        return $this->collectDataFromExternalSite(self::BASE_URL . "show_standing/" . $competitionId . "/", $this->generateIdentifier($competitionId, 'show_standing'));
    }

    public function collectProgram($competitionId) {
        return $this->collectDataFromExternalSite(self::BASE_URL . "show_program/" . $competitionId . "/", $this->generateIdentifier($competitionId, 'show_program'));
    }

    public function collectResults($competitionId) {
        return $this->collectDataFromExternalSite(self::BASE_URL . "show_result/" . $competitionId . "/", $this->generateIdentifier($competitionId, 'show_result'));
    }

    private function generateIdentifier($competition, $action) {
        return $action . '_' . $competition;
    }

    private function collectDataFromExternalSite($url, $identifier) {
        $filename = $this->cachePath . '/' . $identifier . '.chc';

        if ($this->cacheFolderExists()) {
            if (!file_exists($filename) || $this->lastUpdatedDiff($filename) > self::CACHE_LIFETIME_MILLISECS) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                if (strpos($identifier, 'show_program') !== false) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, array('date_from' => date("d-m-Y"),
                        'date_to' => date("d-m-Y", strtotime("+1 year"))));
                }
                $returnData = curl_exec($ch);
                curl_close($ch);
                $result = $this->formatResultData($returnData, $identifier);

                if ($result == null) {
                    return file_get_contents($filename);
                }

                file_put_contents($filename, $result);
                return $result;
            }
        }

        return file_get_contents($filename);
    }

    private function formatResultData($data, $identifier) {
        if (strpos($identifier, 'show_standing') !== false) {
            preg_match_all('/<div id="content_standings_" class="cf clear">.*?<script type="text\/javascript">/s', $data, $matches, PREG_PATTERN_ORDER);
        } else {
            preg_match_all('/<div class="cf clear" id="content_(.*)">.*?<script type="text\/javascript">/s', $data, $matches, PREG_PATTERN_ORDER);
        }

        if (isset($matches[0][0])) {
            $html = strip_tags($matches[0][0], '<table><thead><th><tr><td><div><strong><br>');
        }

        return $html;
    }

    private function lastUpdatedDiff($path) {
        return time() - filemtime($path);
    }

    private function cacheFolderExists() {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }

        return true;
    }

}

?>