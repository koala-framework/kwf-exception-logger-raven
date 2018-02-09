<?php
class KwfExceptionLoggerRaven_Kwf_Assets_Dependency_Dynamic_RavenJsDsn extends Kwf_Assets_Dependency_Abstract
{
    public function getMimeType()
    {
        return 'text/javascript';
    }

    public function getContentsPacked()
    {
        $data = array(
            'dsn' => Kwf_Config::getValue('ravenJs.dsn')
        );
        $ret = "Kwf.RavenJsConfig = ".json_encode($data).";";
        return Kwf_SourceMaps_SourceMap::createEmptyMap($ret);
    }

    public function getIdentifier()
    {
        return 'RavenJsDsn';
    }
}
