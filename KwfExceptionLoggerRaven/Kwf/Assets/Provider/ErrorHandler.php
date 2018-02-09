<?php
class KwfExceptionLoggerRaven_Kwf_Assets_Provider_ErrorHandler extends Kwf_Assets_Provider_Abstract
{
    public function getDependency($dependencyName)
    {
        if ($dependencyName == 'KwfErrorHandler') {
            return new Kwf_Assets_Dependency_Dependencies(
                $this->_providerList,
                array(
                    new KwfExceptionLoggerRaven_Kwf_Assets_Dependency_Dynamic_RavenJsDsn($this->_providerList),
                    $this->_providerList->findDependency('KwfErrorHandlerRaven')
                ),
                $dependencyName
            );
        }
        return null;
    }
}
